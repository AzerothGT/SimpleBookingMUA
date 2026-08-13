<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'services' => ['required', 'array', 'min:1'],
            'services.*.id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'services.*.qty' => ['required', 'integer', 'min:1'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:20'],
            'client_address' => ['required', 'string'],
            'maps_url' => ['nullable', 'url', 'max:2048'],
            'maps_lat' => ['required_with:maps_lng', 'nullable', 'numeric', 'between:-90,90'],
            'maps_lng' => ['required_with:maps_lat', 'nullable', 'numeric', 'between:-180,180'],
            'client_requested_date' => ['required', 'date', 'after_or_equal:today'],
            'client_requested_end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['prohibited'],
            'starts_at' => ['prohibited'],
            'ends_at' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    /** @return array<int, Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'client_requested_date',
                    'client_requested_end_time',
                ])) {
                    return;
                }

                $requestedEnd = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $this->string('client_requested_date').' '.$this->string('client_requested_end_time'),
                );

                if ($requestedEnd->lessThanOrEqualTo(now())) {
                    $validator->errors()->add(
                        'client_requested_end_time',
                        'The requested end time must be in the future.',
                    );
                }
            },
        ];
    }
}
