<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Doctor $doctor */
        $doctor = $this->route('doctor');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->user_id)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'speciality' => ['sometimes', 'required', 'string', 'max:255'],
            'professional_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('doctors', 'professional_number')->ignore($doctor)],
            'commission_percentage' => ['sometimes', 'required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->hasAny(['name', 'email', 'phone_number', 'speciality', 'professional_number', 'commission_percentage', 'is_available'])
            ?: $validator->errors()->add('doctor', 'Informe pelo menos um campo para alterar.')];
    }
}
