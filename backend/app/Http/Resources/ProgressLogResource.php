<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_program_id' => $this->training_program_id,
            'log_date' => $this->log_date->toDateString(),
            'weight_kg' => $this->weight_kg,
            'body_fat_pct' => $this->body_fat_pct,
            'notes' => $this->notes,
            'recorded_by' => $this->whenLoaded('recorder', fn() => $this->recorder?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
