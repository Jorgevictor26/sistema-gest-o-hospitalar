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
            'status' => $this->status,
            'notes' => $this->notes,
            'patient' => ['id' => $this->patient->id, 'name' => $this->patient->name, 'phone_number' => $this->patient->phone_number],
            'doctor' => ['id' => $this->doctor->id, 'name' => $this->doctor->user->name, 'speciality' => $this->doctor->speciality],
            'created_by' => ['id' => $this->createdBy->id, 'name' => $this->createdBy->name],
        ];
    }
}
