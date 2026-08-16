<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStaffRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'staff' => ['required', 'array', 'min:1'],
            'staff.*.user_id' => [
                'required',
                'distinct',
                Rule::exists(User::class, 'id')->where(
                    fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->whereIn('role', ['owner', 'admin', 'staff']),
                ),
            ],
            'staff.*.starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
        ];
    }
}
