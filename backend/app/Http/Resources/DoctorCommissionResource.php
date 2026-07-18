<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorCommissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'attendance_id' => $this->id,
            'attendance_date' => $this->attendance_date->format('Y-m-d'),
            'patient' => ['id' => $this->patient->id, 'name' => $this->patient->name],
            'total_amount' => Money::format($this->total_amount),
            'commission_percentage' => Money::format($this->commission_percentage),
            'commission_amount' => Money::format($this->commissionAmount()),
        ];
    }
}
