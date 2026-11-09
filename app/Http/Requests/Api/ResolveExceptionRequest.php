<?php

namespace App\Http\Requests\Api;

class ResolveExceptionRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'max:1000'],
            'eta_correction_hours' => ['nullable', 'numeric'],

            // Prohibit direct injection of resolver or status
            'resolved_by' => ['prohibited'],
            'resolved_at' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
