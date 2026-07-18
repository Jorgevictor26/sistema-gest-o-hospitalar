<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure' => $this->procedure,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'usage_count' => $this->whenCounted('attendances'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
