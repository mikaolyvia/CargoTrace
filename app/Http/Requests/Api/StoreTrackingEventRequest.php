<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreTrackingEventRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_type' => [
                'required',
                'string',
                Rule::in(['BOOKING_CONFIRMED', 'PICKED_UP', 'DEPARTED', 'IN_TRANSIT', 'ARRIVED', 'DELIVERED', 'EXCEPTION']),
            ],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:500'],
            'weather_condition' => ['nullable', 'string', 'max:50'],
            'weather_temp_c' => ['nullable', 'numeric', 'between:-50,60'],
            'event_time' => ['nullable', 'date'],

            // Prohibit direct injection of recorder
            'created_by' => ['prohibited'],
        ];
    }
}
