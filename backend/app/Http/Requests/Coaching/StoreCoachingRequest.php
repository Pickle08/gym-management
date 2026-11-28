<?php

namespace App\Http\Requests\Coaching;

use App\Models\CoachingRequest;
use App\Models\User;
use App\Services\NutritionCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_TRAINER)->where('is_active', true),
            ],
            'goal_type' => ['required', Rule::in(CoachingRequest::GOALS)],
            'height_cm' => ['required', 'integer', 'between:100,250'],
            'initial_weight_kg' => ['required', 'numeric', 'between:30,300'],
            'target_weight_kg' => ['nullable', 'numeric', 'between:30,300'],
            'activity_level' => ['required', Rule::in(array_keys(NutritionCalculator::ACTIVITY_FACTORS))],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'preferred_days' => ['nullable', 'array', 'max:7'],
            'preferred_days.*' => ['distinct', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
        ];
    }
}
