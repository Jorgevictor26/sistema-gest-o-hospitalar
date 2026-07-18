<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DoctorCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('doctor') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'date' => ['sometimes', 'date'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('period', 'monthly') === 'custom'
                && (! $this->filled('date_from') || ! $this->filled('date_to'))) {
                $validator->errors()->add('date_from', 'O período personalizado exige data inicial e final.');
            }
        }];
    }
}
