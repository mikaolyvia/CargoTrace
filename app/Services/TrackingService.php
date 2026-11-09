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

    /**
     * Record a new immutable tracking event for a shipment.
     *
     * @param Shipment $shipment
     * @param array $data Event data (event_type, description, location_id, latitude, longitude, weather, event_time)
     * @param User $actor The user registering the event
     * @return TrackingEvent
     */
    public function recordEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        $eventType = strtoupper($data['event_type'] ?? '');
        if (! in_array($eventType, self::VALID_EVENT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid event type [{$eventType}]. Allowed types: " . implode(', ', self::VALID_EVENT_TYPES));
        }

        $eventTime = isset($data['event_time']) ? Carbon::parse($data['event_time']) : Carbon::now();

        $locationId = $data['location_id'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        // Auto-snapshot coordinates from master location if not explicitly provided
        if ($locationId && ($latitude === null || $longitude === null)) {
            $location = Location::find($locationId);
            if ($location) {
                $latitude = $location->latitude;
                $longitude = $location->longitude;
            }
        }

        return $shipment->trackingEvents()->create([
            'location_id' => $locationId,
            'event_type' => $eventType,
            'event_time' => $eventTime,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => $data['description'] ?? "Status logged: {$eventType}",
            'weather_condition' => $data['weather_condition'] ?? null,
            'weather_temp_c' => $data['weather_temp_c'] ?? null,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Record an intermediate event that does not alter macro shipment status.
     * Allowed types: IN_TRANSIT, ARRIVED, EXCEPTION.
     * Updates current_location_id if a valid location_id is supplied.
     */
    public function recordIntermediateEvent(Shipment $shipment, string $eventType, array $data, User $actor): TrackingEvent
    {
        $eventType = strtoupper($eventType);
        $allowedIntermediate = ['IN_TRANSIT', 'ARRIVED', 'EXCEPTION'];

        if (! in_array($eventType, $allowedIntermediate, true)) {
            throw new InvalidArgumentException("Event type [{$eventType}] cannot be recorded as intermediate event. Allowed: " . implode(', ', $allowedIntermediate));
        }

        return DB::transaction(function () use ($shipment, $eventType, $data, $actor) {
            $data['event_type'] = $eventType;
            $event = $this->recordEvent($shipment, $data, $actor);

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
        $shipment->loadMissing(['origin', 'destination', 'trackingEvents']);

        $points = [];

        // 1. Origin point
        if ($shipment->origin && $shipment->origin->latitude !== null) {
            $points[] = [
                'lat' => (float) $shipment->origin->latitude,
                'lng' => (float) $shipment->origin->longitude,
                'label' => 'Origin: ' . $shipment->origin->name,
                'type' => 'ORIGIN',
            ];
        }

        // 2. Intermediate valid checkpoint events in chronological order
        $checkpointEvents = $shipment->trackingEvents()
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

            $points[] = [
                'lat' => (float) $event->latitude,
                'lng' => (float) $event->longitude,
                'label' => $event->event_type . ' (' . $event->event_time->format('Y-m-d H:i') . ')',
                'type' => 'CHECKPOINT',
            ];
        }

        // 3. Destination point
        if ($shipment->destination && $shipment->destination->latitude !== null) {
            $points[] = [
                'lat' => (float) $shipment->destination->latitude,
                'lng' => (float) $shipment->destination->longitude,
                'label' => 'Destination: ' . $shipment->destination->name,
                'type' => 'DESTINATION',
            ];
        }

        return $points;
    }
}
