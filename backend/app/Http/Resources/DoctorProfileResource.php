<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone_number,
            'speciality' => $this->speciality,
            'professional_number' => $this->professional_number,
            'is_available' => $this->is_available,
            'commission_percentage' => $this->commission_percentage,
        ];
    }
}
