<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'patient' => ['id' => $this->patient->id, 'name' => $this->patient->name, 'phone_number' => $this->patient->phone_number],
            'doctor' => ['id' => $this->doctor->id, 'name' => $this->doctor->user->name, 'speciality' => $this->doctor->speciality],
            'scheduled_by' => ['id' => $this->scheduledBy->id, 'name' => $this->scheduledBy->name],
            'cancelled_by' => $this->whenLoaded('cancelledBy', fn () => $this->cancelledBy
                ? ['id' => $this->cancelledBy->id, 'name' => $this->cancelledBy->name]
                : null),
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }
}
