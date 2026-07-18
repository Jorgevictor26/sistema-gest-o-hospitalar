<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyClosureResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'is_closed' => $this->reopened_at === null,
            'summary' => $this->summary,
            'closed_by' => ['id' => $this->closedBy->id, 'name' => $this->closedBy->name],
            'closed_at' => $this->closed_at,
            'reopened_by' => $this->when($this->reopened_at !== null, fn () => [
                'id' => $this->reopenedBy->id,
                'name' => $this->reopenedBy->name,
            ]),
            'reopened_at' => $this->reopened_at,
            'reopen_reason' => $this->reopen_reason,
        ];
    }
}
