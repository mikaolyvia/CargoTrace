<?php

namespace App\Http\Requests\Api;

use App\Models\ShipmentException;
use Illuminate\Validation\Rule;

class ReportExceptionRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exception_type' => ['required', 'string', Rule::in(ShipmentException::VALID_TYPES)],
            'severity' => ['required', 'string', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
            'impact_hours' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:1000'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'detected_at' => ['nullable', 'date'],

            // Prohibit direct injection of reporter or resolution fields
            'reported_by' => ['prohibited'],
            'resolved_by' => ['prohibited'],
            'resolved_at' => ['prohibited'],
            'resolution_notes' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
