<?php

namespace App\Actions\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordActivity
{
    public function handle(
        ?User $actor,
        Model $entity,
        string $action,
        ?Booking $booking = null,
        ?array $meta = null,
        ?string $detail = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $actor?->id,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'booking_id' => $booking?->id,
            'action' => $action,
            'detail' => $detail ?? $action,
            'meta' => $meta,
        ]);
    }
}
