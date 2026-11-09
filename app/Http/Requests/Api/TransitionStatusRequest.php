<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class TransitionStatusRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'])],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'event_time' => ['nullable', 'date'],
        ];
    }
}
