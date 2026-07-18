<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'receptionist', 'doctor']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::in(['daily', 'monthly', 'annual', 'custom'])],
            'date' => ['sometimes', 'date'],
            'month' => ['sometimes', 'date_format:Y-m'],
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'doctor_id' => ['sometimes', 'integer', 'exists:doctors,id'],
            'patient_id' => ['sometimes', 'integer', 'exists:patients,id'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('period', 'daily') === 'custom') {
                    if (! $this->filled('date_from')) {
                        $validator->errors()->add('date_from', 'Informe a data inicial do relatório personalizado.');
                    }

                    if (! $this->filled('date_to')) {
                        $validator->errors()->add('date_to', 'Informe a data final do relatório personalizado.');
                    }
                }
            },
        ];
    }
}
