<?php

namespace App\Actions\Transactions;

use App\Actions\ActivityLogs\RecordActivity;
use App\Actions\Bookings\ChangeBookingStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandleMidtransWebhook
{
    public function __construct(
        private ChangeBookingStatus $changeBookingStatus,
        private RecordActivity $recordActivity,
    ) {}

    public function handle(array $payload): Transaction
    {
        return DB::transaction(function () use ($payload): Transaction {
            $this->validateSignature($payload);

            $transaction = Transaction::query()
                ->with(['booking', 'user'])
                ->where('order_id', $payload['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateAmount($transaction, $payload['gross_amount']);

            $status = $payload['transaction_status'];
            if ($this->shouldIgnore($transaction->transaction_status, $status)) {
                return $transaction;
            }

            $transaction->update([
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $transaction->midtrans_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $transaction->payment_type,
                'transaction_status' => $status,
                'fraud_status' => $payload['fraud_status'] ?? null,
                'status_code' => $payload['status_code'],
                'paid_at' => $this->paidAt(
                    $transaction,
                    $status,
                    $payload['fraud_status'] ?? null,
                ),
            ]);

            $this->recordActivity->handle(
                null,
                $transaction,
                'transaction.webhook',
                booking: $transaction->booking,
                meta: [
                    'order_id' => $transaction->order_id,
                    'transaction_status' => $status,
                    'fraud_status' => $payload['fraud_status'] ?? null,
                    'status_code' => $payload['status_code'],
                ],
                detail: 'Midtrans webhook processed.',
            );

            if (in_array($transaction->transaction_status, ['capture', 'settlement'], true)
                && $transaction->fraud_status === 'accept'
                && $transaction->booking->isPending()
                && $transaction->booking->starts_at !== null
                && $transaction->booking->ends_at !== null) {
                $this->changeBookingStatus->handle(
                    $transaction->booking,
                    $transaction->user,
                    'confirmed',
                );
            }

            return $transaction->refresh();
        });
    }

    private function validateSignature(array $payload): void
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new \RuntimeException('Midtrans server key is not configured.');
        }

        $expected = hash('sha512',
            $payload['order_id']
            .$payload['status_code']
            .$payload['gross_amount']
            .$serverKey
        );

        if (! hash_equals($expected, $payload['signature_key'])) {
            throw ValidationException::withMessages([
                'signature_key' => 'The Midtrans signature is invalid.',
            ]);
        }
    }

    private function validateAmount(Transaction $transaction, int|float|string $grossAmount): void
    {
        if ((int) round((float) $grossAmount) !== $transaction->gross_amount) {
            throw ValidationException::withMessages([
                'gross_amount' => 'The gross amount does not match the transaction.',
            ]);
        }
    }

    private function shouldIgnore(string $current, string $incoming): bool
    {
        if ($current === $incoming || $current === 'refund') {
            return true;
        }

        return match ($current) {
            'settlement' => $incoming !== 'refund',
            'capture' => ! in_array($incoming, ['settlement', 'refund'], true),
            'deny', 'cancel', 'expire', 'failure' => true,
            default => false,
        };
    }

    private function paidAt(Transaction $transaction, string $status, ?string $fraudStatus): mixed
    {
        if (in_array($status, ['capture', 'settlement'], true) && $fraudStatus === 'accept') {
            return $transaction->paid_at ?? now();
        }

        return $status === 'refund' ? $transaction->paid_at : null;
    }
}
