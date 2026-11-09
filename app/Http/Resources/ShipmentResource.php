<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_number' => $this->shipment_number,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }),
            'customer_id' => $this->customer_id,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'role' => $this->creator->role,
                ];
            }),
            'carrier' => new CarrierResource($this->whenLoaded('carrier')),
            'carrier_id' => $this->carrier_id,
            'origin' => new LocationResource($this->whenLoaded('origin')),
            'origin_id' => $this->origin_id,
            'destination' => new LocationResource($this->whenLoaded('destination')),
            'destination_id' => $this->destination_id,
            'current_location' => new LocationResource($this->whenLoaded('currentLocation')),
            'current_location_id' => $this->current_location_id,
            'cargo_type' => $this->cargo_type,
            'total_weight_kg' => (float) $this->total_weight_kg,
            'total_volume_cbm' => (float) $this->total_volume_cbm,
            'current_status' => $this->current_status,
            'expected_departure' => $this->expected_departure?->toISOString(),
            'expected_arrival' => $this->expected_arrival?->toISOString(),
            'estimated_arrival' => $this->estimated_arrival?->toISOString(),
            'actual_arrival' => $this->actual_arrival?->toISOString(),
            'is_delayed' => (bool) $this->is_delayed,
            'delay_hours' => (float) $this->delay_hours,
            'delay_severity' => $this->delay_severity,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'weight_kg' => (float) $item->weight_kg,
                        'length_cm' => $item->length_cm !== null ? (float) $item->length_cm : null,
                        'width_cm' => $item->width_cm !== null ? (float) $item->width_cm : null,
                        'height_cm' => $item->height_cm !== null ? (float) $item->height_cm : null,
                        'is_hazardous' => (bool) $item->is_hazardous,
                        'hs_code' => $item->hs_code,
                    ];
                });
            }),
            'tracking_events' => TrackingEventResource::collection($this->whenLoaded('trackingEvents')),
            'exceptions' => ShipmentExceptionResource::collection($this->whenLoaded('exceptions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
