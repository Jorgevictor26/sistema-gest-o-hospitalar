<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'receptionist']) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identity_card' => ['required', 'string', 'max:100', 'unique:patients,identity_card'],
            'email' => ['nullable', 'email', 'max:255', 'unique:patients,email'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
