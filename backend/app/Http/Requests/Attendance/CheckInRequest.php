<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => [
                'required_without:qr_token',
                'prohibits:qr_token',
                'integer',
                Rule::exists('members', 'id')->whereNull('deleted_at'),
            ],
            'qr_token' => ['required_without:member_id', 'prohibits:member_id', 'string', 'size:40'],
        ];
    }
}
