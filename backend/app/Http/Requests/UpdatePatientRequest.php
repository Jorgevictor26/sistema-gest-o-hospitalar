<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'receptionist']) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:30'],
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'identity_card' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('patients', 'identity_card')->ignore($patient)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('patients', 'email')->ignore($patient)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->hasAny(['name', 'phone_number', 'gender', 'date_of_birth', 'identity_card', 'email', 'address'])
            ?: $validator->errors()->add('patient', 'Informe pelo menos um campo para alterar.')];
    }
}
