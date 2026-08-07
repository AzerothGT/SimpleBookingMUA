<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'role' => ['required', Rule::in(['owner', 'admin', 'staff'])],
            'is_active' => ['boolean'],
        ];
    }
}
