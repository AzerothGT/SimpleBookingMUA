<?php

namespace App\Http\Resources;

use App\Models\ServiceImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceImage */
class ServiceImageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'image_url' => $this->image_url,
            'image_source' => $this->image_source,
            'sort_order' => $this->sort_order,
            'is_cover' => $this->is_cover,
            'created_at' => $this->created_at,
        ];
    }
}
