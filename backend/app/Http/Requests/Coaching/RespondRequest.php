<?php

namespace App\Http\Requests\Coaching;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['accept', 'reject'])],
            'reject_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
            'duration_weeks' => ['required_if:action,accept', 'nullable', 'integer', 'between:1,52'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
