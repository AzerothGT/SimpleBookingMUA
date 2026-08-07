<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'role' => ['sometimes', Rule::in(['owner', 'admin', 'staff'])],
            'is_active' => ['boolean'],
        ];
    }
}
