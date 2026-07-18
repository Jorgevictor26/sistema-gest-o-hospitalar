<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'amount_paid' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'attendance_date' => ['required', 'date'],
            'procedures' => ['required', 'array', 'min:1'],
            'procedures.*' => ['required', 'integer', 'distinct', 'exists:procedures,id'],
        ];
    }
}
