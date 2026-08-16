<?php

namespace App\Actions\Bookings;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateBooking
{
    public function __construct(private RecordActivity $recordActivity) {}

    /** @return array{booking: Booking, payment_access_token: string} */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $services = $data['services'];
            unset($data['services']);

            $data['client_requested_ends_at'] = Carbon::createFromFormat(
                'Y-m-d H:i',
                $data['client_requested_date'].' '.$data['client_requested_end_time'],
            );
            $data['starts_at'] = null;
            $data['ends_at'] = $data['client_requested_ends_at'];
            $data['status'] = 'pending';
            do {
                $bookingCode = Str::upper(Str::random(8));
            } while (Booking::query()->where('booking_code', $bookingCode)->exists());
            $data['booking_code'] = $bookingCode;
            $paymentAccessToken = Str::random(64);
            $data['payment_access_token_hash'] = Hash::make($paymentAccessToken);
            $data['payment_access_token_expires_at'] = now()->addDays(30);

            $booking = Booking::create($data);

            foreach ($services as $service) {
                $booking->bookingServices()->create([
                    'service_id' => $service['id'],
                    'qty' => $service['qty'],
                ]);
            }

            $this->recordActivity->handle(
                null,
                $booking,
                'booking.created',
                booking: $booking,
            );

            return [
                'booking' => $booking,
                'payment_access_token' => $paymentAccessToken,
            ];
        });
    }
}
