<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShipmentService
{
    /**
     * State Machine: Permitted forward status transitions.
     * CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED
     * DELIVERED is terminal.
     */
    public const ALLOWED_TRANSITIONS = [
        'CREATED' => ['BOOKED'],
        'BOOKED' => ['PICKED_UP'],
        'PICKED_UP' => ['IN_TRANSIT'],
        'IN_TRANSIT' => ['DELIVERED'],
        'DELIVERED' => [],
    ];

    /**
     * Macro status to primary tracking event type mapping.
     */
    public const STATUS_EVENT_MAP = [
        'BOOKED' => 'BOOKING_CONFIRMED',
        'PICKED_UP' => 'PICKED_UP',
        'IN_TRANSIT' => 'DEPARTED',
        'DELIVERED' => 'DELIVERED',
    ];

    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Generate a unique standardized shipment tracking number.
     * Format: CT-YYYYMMDD-XXXX (e.g. CT-20261109-0001)
     */
    public function generateShipmentNumber(): string
    {
        $todayPrefix = 'CT-' . Carbon::now()->format('Ymd') . '-';

        $lastShipment = Shipment::withTrashed()
            ->where('shipment_number', 'LIKE', $todayPrefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastShipment) {
            $lastSequence = (int) substr($lastShipment->shipment_number, -4);
            $newSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '0001';
        }

        return $todayPrefix . $newSequence;
    }

    /**
     * Create a new shipment with atomic cargo items creation and initial ORDER_CREATED tracking event.
     *
     * @param array $data Shipment data
     * @param User $creator The operator/admin user registering the shipment
     * @return Shipment
     * @throws InvalidArgumentException
     */
    public function createShipment(array $data, User $creator): Shipment
    {
        // 1. Business Validation: customer must exist and have role CUSTOMER
        $customer = User::findOrFail($data['customer_id']);
        if (! $customer->isCustomer()) {
            throw new InvalidArgumentException("User ID {$data['customer_id']} must have the CUSTOMER role to own a shipment.");
        }

        return DB::transaction(function () use ($data, $creator, $customer) {
            // 2. Prepare payload
            $shipmentNumber = $this->generateShipmentNumber();
            $expectedDeparture = Carbon::parse($data['expected_departure']);
            $expectedArrival = Carbon::parse($data['expected_arrival']);
            $estimatedArrival = isset($data['estimated_arrival'])
                ? Carbon::parse($data['estimated_arrival'])
                : $expectedArrival;

            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'customer_id' => $customer->id,
                'created_by' => $creator->id,
                'carrier_id' => $data['carrier_id'],
                'origin_id' => $data['origin_id'],
                'destination_id' => $data['destination_id'],
                'current_location_id' => $data['origin_id'],
                'cargo_type' => $data['cargo_type'],
                'current_status' => 'CREATED',
                'expected_departure' => $expectedDeparture,
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'total_weight_kg' => 0.00,
                'total_volume_cbm' => 0.00,
            ]);

            // 3. Attach itemized cargo if supplied
            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $shipment->items()->create([
                        'item_name' => $item['item_name'],
                        'hs_code' => $item['hs_code'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'weight_kg' => $item['weight_kg'] ?? 0.00,
                        'length_cm' => $item['length_cm'] ?? null,
                        'width_cm' => $item['width_cm'] ?? null,
                        'height_cm' => $item['height_cm'] ?? null,
                    ]);
                }

                $shipment->recalculateTotals();
            }

            // 4. Record initial ORDER_CREATED tracking event atomically
            $this->trackingService->recordEvent($shipment, [
                'event_type' => 'ORDER_CREATED',
                'event_time' => Carbon::now(),
                'location_id' => $data['origin_id'] ?? null,
                'description' => 'Shipment order registered in CargoTrace system.',
            ], $creator);

            return $shipment->fresh(['items', 'customer', 'carrier', 'origin', 'destination', 'trackingEvents']);
        });
    }

    /**
     * Single Source of Truth for Shipment Status Transitions.
     * Enforces linear lifecycle validation, records corresponding event, and updates shipment atomically.
     *
     * @param Shipment $shipment
     * @param string $newStatus The target status (BOOKED, PICKED_UP, IN_TRANSIT, DELIVERED)
     * @param array $eventData Event metadata (description, location_id, latitude, longitude, weather_condition, weather_temp_c, event_time)
     * @param User $actor The user authorizing the transition
     * @return Shipment
     * @throws DomainException
     */
    public function transitionTo(Shipment $shipment, string $newStatus, array $eventData, User $actor): Shipment
    {
        $newStatus = strtoupper($newStatus);
        $currentStatus = $shipment->current_status;

        // 1. Verify terminal status
        if ($currentStatus === 'DELIVERED') {
            throw new DomainException("Cannot transition shipment {$shipment->shipment_number}. DELIVERED is a terminal status and cannot be modified.");
        }

        // 2. Validate linear state transition
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        if (! in_array($newStatus, $allowed, true)) {
            $allowedList = empty($allowed) ? 'None' : implode(', ', $allowed);
            throw new DomainException("Invalid status transition from [{$currentStatus}] to [{$newStatus}]. Allowed next status: [{$allowedList}].");
        }

        return DB::transaction(function () use ($shipment, $newStatus, $eventData, $actor) {
            $eventTime = isset($eventData['event_time']) ? Carbon::parse($eventData['event_time']) : Carbon::now();

            $updateData = [
                'current_status' => $newStatus,
            ];

            // If DELIVERED, lock actual arrival time
            if ($newStatus === 'DELIVERED') {
                $updateData['actual_arrival'] = $eventTime;
                $updateData['current_location_id'] = $shipment->destination_id;
            } elseif (! empty($eventData['location_id'])) {
                $updateData['current_location_id'] = $eventData['location_id'];
            }

            $shipment->update($updateData);

            // Determine matching tracking event type
            $eventType = $eventData['event_type'] ?? (self::STATUS_EVENT_MAP[$newStatus] ?? 'IN_TRANSIT');

            $this->trackingService->recordEvent($shipment, [
                'event_type' => $eventType,
                'event_time' => $eventTime,
                'location_id' => $eventData['location_id'] ?? $shipment->current_location_id,
                'latitude' => $eventData['latitude'] ?? null,
                'longitude' => $eventData['longitude'] ?? null,
                'description' => $eventData['description'] ?? "Shipment status transitioned to {$newStatus}.",
                'weather_condition' => $eventData['weather_condition'] ?? null,
                'weather_temp_c' => $eventData['weather_temp_c'] ?? null,
            ], $actor);

            return $shipment->fresh(['trackingEvents', 'currentLocation']);
        });
    }

    /**
     * Add a line item to an existing shipment and update aggregates.
     */
    public function addItem(Shipment $shipment, array $itemData): ShipmentItem
    {
        $item = $shipment->items()->create($itemData);
        $shipment->recalculateTotals();

        return $item;
    }
}
