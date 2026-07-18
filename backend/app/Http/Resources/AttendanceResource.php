<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewCommission = $request->user()?->hasRole('admin')
            || $request->user()?->id === $this->doctor->user_id;

        return [
            'id' => $this->id,
            'attendance_date' => $this->attendance_date->format('Y-m-d'),
            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'phone_number' => $this->patient->phone_number,
            ],
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'speciality' => $this->doctor->speciality,
            ],
            'procedures' => $this->procedures->map(fn ($procedure): array => [
                'id' => $procedure->id,
                'procedure' => $procedure->procedure,
                'price' => $procedure->pivot->price,
            ]),
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'pending_amount' => Money::format($this->pendingAmount()),
            'payment_status' => $this->paymentStatus(),
            'commission_percentage' => $this->when($canViewCommission, $this->commission_percentage),
            'commission_amount' => $this->when(
                $canViewCommission,
                Money::format($this->commissionAmount())
            ),
            'registered_by' => [
                'id' => $this->registeredBy->id,
                'name' => $this->registeredBy->name,
            ],
            'payments' => $this->when(
                $this->relationLoaded('payments'),
                fn () => PaymentResource::collection($this->payments)
            ),
            'edits' => $this->when(
                $request->user()?->hasRole('admin') && $this->relationLoaded('edits'),
                fn () => AttendanceEditResource::collection($this->edits)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
