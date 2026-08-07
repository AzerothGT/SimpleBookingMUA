<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image_url' => ['required', 'url', 'max:2048'],
            'image_source' => ['required', Rule::in(['upload', 'external'])],
            'sort_order' => ['integer', 'min:0'],
            'is_cover' => ['boolean'],
            'service_id' => ['prohibited'],
        ];
    }
}
