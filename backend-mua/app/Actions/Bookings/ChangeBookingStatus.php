<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeBookingStatus
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Booking $booking, User $actor, string $status): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $status): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $this->validateTransition($booking, $status);

            $before = ['status' => $booking->status];
            $booking->update(['status' => $status]);

            $this->recordActivity->handle(
                $actor,
                $booking,
                'booking.status_changed',
                booking: $booking,
                meta: [
                    'before' => $before,
                    'after' => ['status' => $status],
                ],
                detail: "Booking status changed from {$before['status']} to {$status}.",
            );

            return $booking->refresh();
        });
    }

    private function validateTransition(Booking $booking, string $status): void
    {
        $allowed = match ($booking->status) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['done', 'cancelled'],
            default => [],
        };

        if (! in_array($status, $allowed, true)) {
            $this->reject();
        }

        if ($status === 'confirmed' && (
            $booking->user_id === null
            || $booking->starts_at === null
            || $booking->ends_at === null
            || ! $booking->transactions()
                ->whereIn('transaction_status', ['capture', 'settlement'])
                ->where('fraud_status', 'accept')
                ->exists()
        )) {
            $this->reject('A complete schedule and accepted settlement are required to confirm a booking.');
        }
    }

    private function reject(string $message = 'The booking status transition is not allowed.'): never
    {
        throw ValidationException::withMessages(['status' => $message]);
    }
}
