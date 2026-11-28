<?php

namespace App\Http\Resources;

use App\Services\NutritionCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->relationLoaded('member') ? $this->member : null;

        return [
            'id' => $this->id,
            'member' => $member ? [
                'id' => $member->id,
                'member_code' => $member->member_code,
                'name' => $member->name,
                'gender' => $member->gender,
                'age' => $member->birth_date?->age,
            ] : null,
            'trainer' => $this->whenLoaded('trainer', fn() => [
                'id' => $this->trainer->id,
                'name' => $this->trainer->name,
            ]),
            'goal_type' => $this->goal_type,
            'height_cm' => $this->height_cm,
            'initial_weight_kg' => $this->initial_weight_kg,
            'target_weight_kg' => $this->target_weight_kg,
            'activity_level' => $this->activity_level,
            'medical_notes' => $this->medical_notes,
            'preferred_days' => $this->preferred_days ?? [],
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'requested_by' => $this->whenLoaded('requester', fn() => $this->requester?->name),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'program_id' => $this->whenLoaded('program', fn() => $this->program?->id),
            // estimasi referensi; null kalau gender/tanggal lahir member belum diisi
            'estimate' => $member
                ? NutritionCalculator::estimate(
                    $member->gender,
                    $member->birth_date,
                    (float) $this->initial_weight_kg,
                    (int) $this->height_cm,
                    $this->activity_level
                )
                : null,
        ];
    }
}
