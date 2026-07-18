<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOwnDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('doctor') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'speciality' => ['prohibited'],
            'professional_number' => ['prohibited'],
            'commission_percentage' => ['prohibited'],
            'is_available' => ['prohibited'],
            'roles' => ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->hasAny(['name', 'email', 'phone_number'])
            ?: $validator->errors()->add('profile', 'Informe pelo menos um dado pessoal para alterar.')];
    }
}
