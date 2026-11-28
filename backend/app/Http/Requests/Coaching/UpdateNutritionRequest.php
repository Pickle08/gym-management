<?php

namespace App\Http\Requests\Coaching;

use App\Services\NutritionCalculator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNutritionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daily_calories' => ['required', 'integer', 'between:800,6000'],
            'protein_g' => ['required', 'integer', 'between:0,500'],
            'carbs_g' => ['required', 'integer', 'between:0,1000'],
            'fats_g' => ['required', 'integer', 'between:0,300'],
            'diet_guidelines' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $macro = NutritionCalculator::macroCalories(
                (int) $this->protein_g,
                (int) $this->carbs_g,
                (int) $this->fats_g
            );
            $target = (int) $this->daily_calories;

            if (abs($macro - $target) / $target > 0.10) {
                $validator->errors()->add(
                    'daily_calories',
                    "Kalori dari makro ({$macro} kkal) selisihnya lebih dari 10% dari target ({$target} kkal)."
                );
            }
        });
    }
}
