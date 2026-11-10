<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TrackingService
{
    /**
     * Permitted milestone and operational event types.
     */
    public const VALID_EVENT_TYPES = [
        'ORDER_CREATED',
        'BOOKING_CONFIRMED',
        'PICKED_UP',
        'DEPARTED',
        'IN_TRANSIT',
        'ARRIVED',
        'DELIVERED',
        'EXCEPTION',
    ];

    public function __construct(
        protected ?WeatherService $weatherService = null
    ) {
        $this->weatherService = $weatherService ?? app(WeatherService::class);
    }

    /**
     * Resolve coordinates from master location (if not provided) and fetch weather data
     * without overriding any manually supplied weather condition or temperature.
     * Guaranteed to never execute external HTTP requests inside open DB transactions.
     */
    public function prepareWeatherAndCoordinates(array $data): array
    {
        $locationId = $data['location_id'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        // 1. Auto-snapshot coordinates from master location if not explicitly provided
        if ($locationId && ($latitude === null || $longitude === null)) {
            $location = Location::find($locationId);
            if ($location) {
                $latitude = $location->latitude;
                $longitude = $location->longitude;
                $data['latitude'] = $latitude;
                $data['longitude'] = $longitude;
            }
        }

        // 2. Identify if any weather attribute needs to be auto-populated
        $hasManualCondition = array_key_exists('weather_condition', $data) && $data['weather_condition'] !== null && $data['weather_condition'] !== '';
        $hasManualTemp = array_key_exists('weather_temp_c', $data) && $data['weather_temp_c'] !== null;

        // 3. Auto-fetch weather if coordinates are present, valid, and at least one weather metric is missing
        if ((! $hasManualCondition || ! $hasManualTemp) && $latitude !== null && $longitude !== null) {
            $lat = (float) $latitude;
            $lng = (float) $longitude;

            if ($this->weatherService && $this->weatherService->isValidCoordinate($lat, $lng)) {
                $weather = $this->weatherService->getCurrentWeather($lat, $lng);

                if ($weather) {
                    if (! $hasManualCondition && isset($weather['weather_condition'])) {
                        $data['weather_condition'] = $weather['weather_condition'];
                    }
                    if (! $hasManualTemp && isset($weather['weather_temp_c'])) {
                        $data['weather_temp_c'] = $weather['weather_temp_c'];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Persist tracking event record to database.
     * Pure storage method: does not perform any HTTP or external weather service calls.
     */
    protected function createEventRecord(Shipment $shipment, array $data, ?User $actor = null): TrackingEvent
    {
        $eventType = strtoupper($data['event_type'] ?? '');
        $eventTime = isset($data['event_time']) ? Carbon::parse($data['event_time']) : Carbon::now();

        return $shipment->trackingEvents()->create([
            'location_id' => $data['location_id'] ?? null,
            'event_type' => $eventType,
            'event_time' => $eventTime,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'description' => $data['description'] ?? "Status logged: {$eventType}",
            'weather_condition' => $data['weather_condition'] ?? null,
            'weather_temp_c' => $data['weather_temp_c'] ?? null,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * Record a new immutable tracking event for a shipment.
     *
     * @param Shipment $shipment
     * @param array $data Event data (event_type, description, location_id, latitude, longitude, weather, event_time)
     * @param User|null $actor The user registering the event
     * @return TrackingEvent
     */
    public function recordEvent(Shipment $shipment, array $data, ?User $actor = null): TrackingEvent
    {
        $eventType = strtoupper($data['event_type'] ?? '');
        if (! in_array($eventType, self::VALID_EVENT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid event type [{$eventType}]. Allowed types: " . implode(', ', self::VALID_EVENT_TYPES));
        }

        $data['event_type'] = $eventType;

        $data = $this->prepareWeatherAndCoordinates($data);

        return $this->createEventRecord($shipment, $data, $actor);
    }

    /**
     * Record an intermediate event that does not alter macro shipment status.
     * Allowed types: IN_TRANSIT, ARRIVED, EXCEPTION.
     * Updates current_location_id if a valid location_id is supplied.
     */
    public function recordIntermediateEvent(Shipment $shipment, string $eventType, array $data, ?User $actor = null): TrackingEvent
    {
        $eventType = strtoupper($eventType);
        $allowedIntermediate = ['IN_TRANSIT', 'ARRIVED', 'EXCEPTION'];

        if (! in_array($eventType, $allowedIntermediate, true)) {
            throw new InvalidArgumentException("Event type [{$eventType}] cannot be recorded as intermediate event. Allowed: " . implode(', ', $allowedIntermediate));
        }

        $data['event_type'] = $eventType;

        // 1. Resolve coordinates and weather once outside DB transaction
        $data = $this->prepareWeatherAndCoordinates($data);

        // 2. Persist event and update current_location_id atomically inside DB transaction
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $event = $this->createEventRecord($shipment, $data, $actor);

            if (! empty($data['location_id'])) {
                // Determine whether this newly logged event is chronologically the latest location event
                $latestLocationEvent = $shipment->trackingEvents()
                    ->whereNotNull('location_id')
                    ->where('id', '!=', $event->id)
                    ->orderByDesc('event_time')
                    ->orderByDesc('id')
                    ->first();

                $isLatest = false;
                if (! $latestLocationEvent) {
                    $isLatest = true;
                } else {
                    if ($event->event_time->gt($latestLocationEvent->event_time)) {
                        $isLatest = true;
                    } elseif ($event->event_time->equalTo($latestLocationEvent->event_time)) {
                        $isLatest = $event->id >= $latestLocationEvent->id;
                    }
                }

                // Only update current_location_id if this event is latest (prevents backdated events from overwriting)
                if ($isLatest) {
                    $shipment->update([
                        'current_location_id' => $data['location_id'],
                    ]);
                }
            }

            return $event;
        });
    }

    /**
     * Retrieve chronological tracking timeline for a shipment.
     *
     * @param Shipment $shipment
     * @param string $order 'asc' for oldest first, 'desc' for latest first
     * @return Collection<int, TrackingEvent>
     */
    public function getTimeline(Shipment $shipment, string $order = 'asc'): Collection
    {
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        return $shipment->trackingEvents()
            ->with(['location', 'creator'])
            ->orderBy('event_time', $order)
            ->orderBy('id', $order)
            ->get();
    }

    /**
     * Generate an ordered list of coordinate pairs [lat, lng] for rendering
     * polyline journeys on Leaflet.js maps.
     *
     * Structure: Origin -> Checkpoint 1 -> Checkpoint 2 -> ... -> Destination
     */
    public function getMapRoutePoints(Shipment $shipment): array
    {
        $shipment->loadMissing(['origin', 'destination', 'trackingEvents.location']);

        $points = [];

        // 1. Origin point
        if ($shipment->origin && $shipment->origin->latitude !== null) {
            $points[] = [
                'lat' => (float) $shipment->origin->latitude,
                'lng' => (float) $shipment->origin->longitude,
                'label' => 'Origin: ' . $shipment->origin->name,
                'type' => 'ORIGIN',
                'status' => 'ORIGIN',
                'location_name' => $shipment->origin->name,
                'event_time' => $shipment->expected_departure?->format('Y-m-d H:i:s'),
                'weather_condition' => null,
                'weather_temp_c' => null,
            ];
        }

        // 2. Intermediate valid checkpoint events in chronological order
        $checkpointEvents = $shipment->trackingEvents()
            ->with('location')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('event_time', 'asc')
            ->get();

        foreach ($checkpointEvents as $event) {
            // Avoid duplicate plotting if checkpoint matches exact origin coordinates
            if ($shipment->origin &&
                abs((float) $event->latitude - (float) $shipment->origin->latitude) < 0.0001 &&
                abs((float) $event->longitude - (float) $shipment->origin->longitude) < 0.0001) {
                continue;
            }

            $formattedTime = $event->event_time ? $event->event_time->format('Y-m-d H:i') : null;
            $locationLabel = $event->location?->name ?? 'Checkpoint (' . round((float) $event->latitude, 3) . ', ' . round((float) $event->longitude, 3) . ')';

            $points[] = [
                'lat' => (float) $event->latitude,
                'lng' => (float) $event->longitude,
                'label' => $event->event_type . ($formattedTime ? ' (' . $formattedTime . ')' : ''),
                'type' => $event->event_type === 'EXCEPTION' ? 'EXCEPTION' : 'CHECKPOINT',
                'status' => $event->event_type,
                'location_name' => $locationLabel,
                'event_time' => $event->event_time?->format('Y-m-d H:i:s'),
                'weather_condition' => $event->weather_condition,
                'weather_temp_c' => $event->weather_temp_c !== null ? (float) $event->weather_temp_c : null,
            ];
        }

        // 3. Destination point
        if ($shipment->destination && $shipment->destination->latitude !== null) {
            $points[] = [
                'lat' => (float) $shipment->destination->latitude,
                'lng' => (float) $shipment->destination->longitude,
                'label' => 'Destination: ' . $shipment->destination->name,
                'type' => 'DESTINATION',
                'status' => 'DESTINATION',
                'location_name' => $shipment->destination->name,
                'event_time' => $shipment->expected_arrival?->format('Y-m-d H:i:s'),
                'weather_condition' => null,
                'weather_temp_c' => null,
            ];
        }

        return $points;
    }
}
