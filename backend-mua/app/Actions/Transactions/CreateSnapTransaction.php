<?php

namespace App\Actions\Transactions;

use App\Actions\ActivityLogs\RecordActivity;
use App\Contracts\PaymentGateway;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSnapTransaction
{
    public function __construct(
        private PaymentGateway $gateway,
        private RecordActivity $recordActivity,
    ) {}

    public function handle(Booking $booking, User $actor): Transaction
    {
        return DB::transaction(function () use ($booking, $actor): Transaction {
            $booking = Booking::query()->with('service')->lockForUpdate()->findOrFail($booking->id);

            if (in_array($booking->status, ['cancelled', 'done'], true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Snap cannot be created for a terminal booking.',
                ]);
            }

            $grossAmount = (int) round((float) $booking->service->price);
            if ($grossAmount <= 0) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'The gross amount must be greater than zero.',
                ]);
            }

            $orderId = 'MUA-'.Str::uuid();
            $snap = $this->gateway->createSnap($booking, $orderId, $grossAmount);

            $transaction = Transaction::create([
                'booking_id' => $booking->id,
                'user_id' => $actor->id,
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
                'type' => 'dp',
                'snap_token' => $snap['token'],
                'redirect_url' => $snap['redirect_url'],
                'transaction_status' => 'pending',
            ]);

            $this->recordActivity->handle(
                $actor,
                $transaction,
                'transaction.created',
                booking: $booking,
                meta: [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                detail: 'Midtrans Snap transaction created.',
            );

            return $transaction;
        });
    }
}
