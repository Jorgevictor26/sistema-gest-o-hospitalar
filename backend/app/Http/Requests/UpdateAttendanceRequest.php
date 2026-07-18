<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'required', 'integer', Rule::exists('patients', 'id')->where('is_active', true)],
            'doctor_id' => ['sometimes', 'required', 'integer', 'exists:doctors,id'],
            'attendance_date' => ['sometimes', 'required', 'date'],
            'procedures' => ['sometimes', 'required', 'array', 'min:1'],
            'procedures.*' => ['required', 'integer', 'distinct', Rule::exists('procedures', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'commission_percentage' => ['prohibited'],
            'commission_amount' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['patient_id', 'doctor_id', 'attendance_date', 'procedures'])) {
                    $validator->errors()->add('attendance', 'Informe pelo menos um campo para alterar.');
                }
            },
        ];
    }
}
