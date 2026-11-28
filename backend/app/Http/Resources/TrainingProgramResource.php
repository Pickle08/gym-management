<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coaching_request_id' => $this->coaching_request_id,
            'member' => $this->whenLoaded('member', fn() => [
                'id' => $this->member->id,
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
            ]),
            'trainer' => $this->whenLoaded('trainer', fn() => [
                'id' => $this->trainer->id,
                'name' => $this->trainer->name,
            ]),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'status' => $this->status, // yang disimpan
            'state' => $this->state,   // yang dihitung: active/upcoming/expired/completed/cancelled
            'nutrition' => [
                'daily_calories' => $this->daily_calories,
                'protein_g' => $this->protein_g,
                'carbs_g' => $this->carbs_g,
                'fats_g' => $this->fats_g,
                'macro_calories' => $this->macroCalories(),
                'diet_guidelines' => $this->diet_guidelines,
            ],
            'request' => $this->whenLoaded(
                'coachingRequest',
                fn() => new CoachingRequestResource($this->coachingRequest)
            ),
        ];
    }
}
