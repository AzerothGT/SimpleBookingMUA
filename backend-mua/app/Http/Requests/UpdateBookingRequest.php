<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['prohibited'],
            'user_id' => ['prohibited'],
            'starts_at' => ['prohibited'],
            'ends_at' => ['prohibited'],
            'client_requested_date' => ['prohibited'],
            'client_requested_end_time' => ['prohibited'],
            'client_requested_ends_at' => ['prohibited'],
            'notes' => ['nullable', 'string'],
            'client_address' => ['sometimes', 'string'],
            'maps_url' => ['nullable', 'url', 'max:2048'],
            'maps_lat' => ['required_with:maps_lng', 'nullable', 'numeric', 'between:-90,90'],
            'maps_lng' => ['required_with:maps_lat', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
