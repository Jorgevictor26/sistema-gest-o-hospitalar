<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'receptionist']) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'doctor_id' => [
                'sometimes',
                'integer',
                Rule::exists('doctors', 'id')->where(fn (Builder $query) => $query
                    ->where('is_available', true)
                    ->whereExists(fn (Builder $users) => $users
                        ->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'doctors.user_id')
                        ->where('users.is_active', true))),
            ],
            'reason' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
