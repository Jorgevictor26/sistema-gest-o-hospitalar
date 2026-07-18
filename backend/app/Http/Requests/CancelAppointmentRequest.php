<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class CancelAppointmentRequest extends AppointmentActionRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
