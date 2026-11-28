<?php

namespace App\Http\Requests\Coaching;

use Illuminate\Foundation\Http\FormRequest;

class ProgressLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'log_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . today()->toDateString()],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'body_fat_pct' => ['nullable', 'numeric', 'between:3,60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'log_date.before_or_equal' => 'Tanggal pencatatan tidak boleh di masa depan.',
        ];
    }
}
