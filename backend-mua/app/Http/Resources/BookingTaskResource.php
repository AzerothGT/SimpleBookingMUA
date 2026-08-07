<?php

namespace App\Http\Resources;

use App\Models\BookingTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingTask */
class BookingTaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'title' => $this->title,
            'is_done' => $this->is_done,
            'sort_order' => $this->sort_order,
            'done_at' => $this->done_at,
            'created_at' => $this->created_at,
        ];
    }
}
