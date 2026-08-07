<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(array $data): Booking
    {
        return DB::transaction(function () use ($data): Booking {
            $data['client_requested_ends_at'] = Carbon::createFromFormat(
                'Y-m-d H:i',
                $data['client_requested_date'].' '.$data['client_requested_end_time'],
            );
            $data['starts_at'] = null;
            $data['ends_at'] = $data['client_requested_ends_at'];
            $data['status'] = 'pending';

            $booking = Booking::create($data);
            $this->recordActivity->handle(
                null,
                $booking,
                'booking.created',
                booking: $booking,
            );

            return $booking;
        });
    }
}
