<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
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
            'shipment_id' => $this->shipment_id,
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'event_type' => $this->event_type,
            'event_time' => $this->event_time?->toISOString(),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'description' => $this->description,
            'weather_condition' => $this->weather_condition,
            'weather_temp_c' => $this->weather_temp_c !== null ? (float) $this->weather_temp_c : null,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'role' => $this->creator->role,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
