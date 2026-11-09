<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentExceptionResource extends JsonResource
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
            'tracking_event_id' => $this->tracking_event_id,
            'exception_type' => $this->exception_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'impact_hours' => (float) $this->impact_hours,
            'description' => $this->description,
            'detected_at' => $this->detected_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolution_notes' => $this->resolution_notes,
            'reported_by' => $this->reported_by,
            'reporter' => $this->whenLoaded('reporter', function () {
                return [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                    'role' => $this->reporter->role,
                ];
            }),
            'resolved_by' => $this->resolved_by,
            'resolver' => $this->whenLoaded('resolver', function () {
                return [
                    'id' => $this->resolver->id,
                    'name' => $this->resolver->name,
                    'role' => $this->resolver->role,
                ];
            }),
            'tracking_event' => new TrackingEventResource($this->whenLoaded('trackingEvent')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
