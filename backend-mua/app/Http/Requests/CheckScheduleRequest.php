<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_requested_date' => ['required', 'date'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
