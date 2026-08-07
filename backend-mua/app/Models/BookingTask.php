<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'title', 'is_done', 'sort_order', 'done_at'])]
class BookingTask extends Model
{
    public const UPDATED_AT = null;

    use HasFactory;
    use HasUuids;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function booted(): void
    {
        static::saving(function (BookingTask $task): void {
            if ($task->isDirty('is_done')) {
                $task->done_at = $task->is_done ? ($task->done_at ?? now()) : null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'sort_order' => 'integer',
            'done_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
