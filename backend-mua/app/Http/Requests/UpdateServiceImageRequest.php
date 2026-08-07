<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image_url' => ['sometimes', 'url', 'max:2048'],
            'image_source' => ['sometimes', Rule::in(['upload', 'external'])],
            'sort_order' => ['integer', 'min:0'],
            'is_cover' => ['boolean'],
            'service_id' => ['prohibited'],
        ];
    }
}
