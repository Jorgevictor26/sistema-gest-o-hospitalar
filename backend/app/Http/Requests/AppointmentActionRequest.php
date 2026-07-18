<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

abstract class AppointmentActionRequest extends FormRequest
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
}
