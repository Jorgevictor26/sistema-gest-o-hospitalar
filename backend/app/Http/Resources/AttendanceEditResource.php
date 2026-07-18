<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceEditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'edited_by' => [
                'id' => $this->editor->id,
                'name' => $this->editor->name,
            ],
            'edited_at' => $this->created_at,
        ];
    }
}
