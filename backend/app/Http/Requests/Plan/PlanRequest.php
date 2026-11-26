<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'duration_days' => [$required, 'integer', 'min:1', 'max:3650'],
            'price' => [$required, 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
