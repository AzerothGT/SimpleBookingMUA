<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateBooking
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Booking $booking, User $actor, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $data): Booking {
            $before = $booking->only(array_keys($data));
            $booking->update($data);

            $this->recordActivity->handle(
                $actor,
                $booking,
                'booking.updated',
                booking: $booking,
                meta: [
                    'before' => $before,
                    'after' => $booking->only(array_keys($data)),
                ],
                detail: 'Booking details updated.',
            );

            return $booking->refresh();
        });
    }
}
