<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->attendance_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at,
            'received_by' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
            ],
            'is_voided' => $this->voided_at !== null,
            'void' => $this->when($this->voided_at !== null, fn (): array => [
                'reason' => $this->void_reason,
                'voided_at' => $this->voided_at,
                'voided_by' => [
                    'id' => $this->voidedBy->id,
                    'name' => $this->voidedBy->name,
                ],
            ]),
        ];
    }
}
