<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn() => [
                'id' => $this->member->id,
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
            ]),
            'membership_id' => $this->membership_id,
            'membership_end_date' => $this->whenLoaded('membership', fn() => $this->membership->end_date->toDateString()),
            'attendance_date' => $this->attendance_date->toDateString(),
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
            'checked_in_by' => $this->whenLoaded('checkedInBy', fn() => $this->checkedInBy?->name),
        ];
    }
}
