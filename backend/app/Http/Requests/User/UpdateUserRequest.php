<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')->id),
            ],
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'role' => ['sometimes', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_TRAINER])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
