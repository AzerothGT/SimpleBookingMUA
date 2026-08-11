<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ScheduleCalendarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /** @return array<int, Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to'])) {
                    return;
                }

                $maximum = Carbon::createFromFormat('Y-m-d', $this->string('from'))
                    ->endOfMonth();
                $to = Carbon::createFromFormat('Y-m-d', $this->string('to'));

                if ($to->greaterThan($maximum)) {
                    $validator->errors()->add(
                        'to',
                        'The to field must be within the same calendar month as from.',
                    );
                }
            },
        ];
    }
}
