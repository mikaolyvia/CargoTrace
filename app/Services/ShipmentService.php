<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShipmentService
{
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
     * Create a new shipment with atomic cargo items creation.
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

            return $shipment->fresh(['items', 'customer', 'carrier', 'origin', 'destination']);
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
