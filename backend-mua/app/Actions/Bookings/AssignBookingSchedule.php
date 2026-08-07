<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignBookingSchedule
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Booking $booking, User $actor, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $data): Booking {
            $staff = User::query()->lockForUpdate()->findOrFail($data['user_id']);
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            $overlaps = Booking::query()
                ->where('id', '!=', $booking->id)
                ->where('user_id', $staff->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNotNull('starts_at')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'starts_at' => 'The staff member already has an overlapping booking.',
                ]);
            }

            $before = $booking->only(['user_id', 'starts_at', 'ends_at']);

            $booking->update([
                'user_id' => $staff->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $this->recordActivity->handle(
                $actor,
                $booking,
                'booking.schedule_adjusted',
                booking: $booking,
                meta: [
                    'before' => $before,
                    'after' => $booking->only(['user_id', 'starts_at', 'ends_at']),
                ],
                detail: 'Booking schedule adjusted.',
            );

            return $booking->refresh();
        });
    }
}
