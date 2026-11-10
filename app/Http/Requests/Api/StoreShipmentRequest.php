<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreShipmentRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'CUSTOMER');
                }),
            ],
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'vessel_id' => ['nullable', 'integer', 'exists:vessels,id'],
            'container_number' => ['nullable', 'string', 'max:50'],
            'origin_id' => ['required', 'integer', 'exists:locations,id'],
            'destination_id' => ['required', 'integer', 'exists:locations,id', 'different:origin_id'],
            'cargo_type' => ['required', 'string', Rule::in(['GENERAL', 'PERISHABLE', 'HAZARDOUS', 'ELECTRONICS', 'OVERSIZED'])],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],

            // Cargo items manifest (at least one item required)
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.length_cm' => ['nullable', 'numeric', 'min:0'],
            'items.*.width_cm' => ['nullable', 'numeric', 'min:0'],
            'items.*.height_cm' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_hazardous' => ['nullable', 'boolean'],
            'items.*.hs_code' => ['nullable', 'string', 'max:20'],

            // Prohibit direct injection of server-managed fields
            'created_by' => ['prohibited'],
            'current_status' => ['prohibited'],
            'status' => ['prohibited'],
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as &$item) {
                if (is_array($item) && ! isset($item['item_name']) && isset($item['item_description'])) {
                    $item['item_name'] = $item['item_description'];
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'The selected customer is invalid or does not have the CUSTOMER role.',
            'destination_id.different' => 'The destination location must be different from the origin location.',
            'expected_arrival.after' => 'The expected arrival date must be after the expected departure date.',
        ];
    }
}
