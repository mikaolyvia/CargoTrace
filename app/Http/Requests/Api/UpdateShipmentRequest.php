<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carrier_id' => ['sometimes', 'required', 'integer', 'exists:carriers,id'],
            'vessel_id' => ['sometimes', 'nullable', 'integer', 'exists:vessels,id'],
            'container_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'origin_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'destination_id' => ['sometimes', 'required', 'integer', 'exists:locations,id', 'different:origin_id'],
            'cargo_type' => ['sometimes', 'required', 'string', Rule::in(['GENERAL', 'PERISHABLE', 'HAZARDOUS', 'ELECTRONICS', 'OVERSIZED'])],
            'expected_departure' => ['sometimes', 'required', 'date'],
            'expected_arrival' => ['sometimes', 'required', 'date', 'after:expected_departure'],

            // Strictly prohibit direct status modifications or identifier changes via update
            'current_status' => ['prohibited'],
            'status' => ['prohibited'],
            'customer_id' => ['prohibited'],
            'created_by' => ['prohibited'],
            'shipment_number' => ['prohibited'],
            'total_weight_kg' => ['prohibited'],
            'total_volume_cbm' => ['prohibited'],
            'is_delayed' => ['prohibited'],
            'delay_hours' => ['prohibited'],
            'delay_severity' => ['prohibited'],
            'estimated_arrival' => ['prohibited'],
            'actual_arrival' => ['prohibited'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'current_status.prohibited' => 'Shipment status cannot be updated directly. Use the transition endpoint instead.',
            'status.prohibited' => 'Shipment status cannot be updated directly. Use the transition endpoint instead.',
            'destination_id.different' => 'The destination location must be different from the origin location.',
            'expected_arrival.after' => 'The expected arrival date must be after the expected departure date.',
        ];
    }
}
