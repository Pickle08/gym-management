<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $current = $this->whenLoaded('currentMembership');

        return [
            'id' => $this->id,
            'member_code' => $this->member_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'current_membership' => $this->whenLoaded('currentMembership', fn() => $this->currentMembership ? [
                'id' => $this->currentMembership->id,
                'start_date' => $this->currentMembership->start_date->toDateString(),
                'end_date' => $this->currentMembership->end_date->toDateString(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
