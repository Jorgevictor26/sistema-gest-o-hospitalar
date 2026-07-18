<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $appointment = $this->route('appointment');

        if (! $user || ! $appointment instanceof Appointment) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'receptionist'])
            || ($user->hasRole('doctor') && $appointment->doctor()->where('user_id', $user->id)->exists());
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['sometimes', 'required', 'integer', 'exists:doctors,id'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(['patient_id', 'doctor_id', 'scheduled_at', 'status', 'notes'])) {
                $validator->errors()->add('appointment', 'Informe pelo menos um campo para alterar.');
            }

            if ($this->user()?->hasRole('doctor') && ! $this->user()?->hasAnyRole(['admin', 'receptionist'])) {
                $forbidden = array_intersect(array_keys($this->all()), ['patient_id', 'doctor_id', 'scheduled_at']);

                if ($forbidden !== []) {
                    $validator->errors()->add('appointment', 'O médico só pode alterar o estado e as observações.');
                }
            }
        }];
    }
}
