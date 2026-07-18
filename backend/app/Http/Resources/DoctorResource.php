<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'speciality' => $this->speciality,
            'professional_number' => $this->professional_number,
            'is_available' => $this->is_available,
            'commission_percentage' => $this->when(
                $request->user()?->hasRole('admin') || $request->user()?->id === $this->user_id,
                $this->commission_percentage,
            ),
            'is_active' => $this->user->is_active,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
                'roles' => $this->when(
                    $request->user()?->hasRole('admin') || $request->user()?->id === $this->user_id,
                    fn () => $this->user->roles->pluck('name')->values(),
                ),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
