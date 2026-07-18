<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'identity_card' => $this->identity_card,
            'email' => $this->email,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'attendances_count' => $this->whenCounted('attendances'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
