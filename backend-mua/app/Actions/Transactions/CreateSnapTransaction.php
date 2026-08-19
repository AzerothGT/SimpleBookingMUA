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

    public function handle(Booking $booking, ?User $actor, string $type = 'dp', ?int $grossAmount = null): Transaction
    {
        return DB::transaction(function () use ($booking, $actor, $type, $grossAmount): Transaction {
            $booking = Booking::query()->with('bookingServices.service')->lockForUpdate()->findOrFail($booking->id);

            if (in_array($booking->status, ['cancelled', 'done'], true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Snap cannot be created for a terminal booking.',
                ]);
            }

            if (! in_array($type, ['dp', 'pelunasan'], true)) {
                throw ValidationException::withMessages([
                    'type' => 'The payment type is invalid.',
                ]);
            }

            $existingTransaction = $booking->transactions()
                ->where('type', $type)
                ->whereIn('transaction_status', ['pending', 'capture', 'settlement'])
                ->latest()
                ->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $grossAmount ??= (int) round($booking->bookingServices->sum(fn ($bs) => (float) $bs->service->price * $bs->qty));
            if ($grossAmount <= 0) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'The gross amount must be greater than zero.',
                ]);
            }

            $orderId = 'MUA-'.Str::uuid();
            $snap = $this->gateway->createSnap($booking, $orderId, $grossAmount);

            $transaction = Transaction::create([
                'booking_id' => $booking->id,
                'user_id' => $actor?->id,
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
                'type' => $type,
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
