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

    public function handle(array $payload): ?Transaction
    {
        return DB::transaction(function () use ($payload): ?Transaction {
            $this->validateSignature($payload);

            $transaction = Transaction::query()
                ->with(['booking', 'user'])
                ->where('order_id', $payload['order_id'])
                ->lockForUpdate()
                ->first();

            // Midtrans dashboard URL tests use synthetic order IDs. The signature
            // is already verified, so acknowledge notifications for other orders.
            if (! $transaction) {
                return null;
            }

            $this->validateAmount($transaction, $payload['gross_amount']);

            $status = $payload['transaction_status'];
            if ($this->shouldIgnore($transaction, $status, $payload)) {
                return $transaction;
            }

            $refundedAmount = $this->refundedAmount($transaction, $status, $payload);

            $transaction->update([
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $transaction->midtrans_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $transaction->payment_type,
                // A partial refund leaves the payment intact, so the status is preserved.
                'transaction_status' => $this->isPartial($status) ? $transaction->transaction_status : $status,
                'refunded_amount' => $refundedAmount,
                'fraud_status' => $payload['fraud_status']
                    ?? ($this->isRefund($status) ? $transaction->fraud_status : null),
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
                    'refunded_amount' => $refundedAmount,
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

        $signature = $payload['signature_key'] ?? null;
        if (! is_string($signature)) {
            throw ValidationException::withMessages([
                'signature_key' => 'The Midtrans signature is missing.',
            ]);
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$serverKey
        );

        if (! hash_equals($expected, $signature)) {
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

    private function shouldIgnore(Transaction $transaction, string $incoming, array $payload): bool
    {
        $current = $transaction->transaction_status;

        // Repeated partial refunds share the same status, so replays are detected
        // by the cumulative amount instead.
        if ($this->isPartial($incoming)) {
            return ! in_array($current, ['capture', 'settlement'], true)
                || $this->payloadRefundAmount($payload) <= (int) $transaction->refunded_amount;
        }

        if ($current === $incoming || $this->isFullRefund($current)) {
            return true;
        }

        return match ($current) {
            'settlement' => ! $this->isRefund($incoming),
            'capture' => $incoming !== 'settlement' && ! $this->isRefund($incoming),
            'authorize' => ! in_array($incoming, ['capture', 'settlement', 'cancel', 'expire', 'deny', 'failure'], true),
            'deny', 'cancel', 'expire', 'failure' => true,
            default => false,
        };
    }

    private function isPartial(string $status): bool
    {
        return in_array($status, ['partial_refund', 'partial_chargeback'], true);
    }

    private function isFullRefund(string $status): bool
    {
        return in_array($status, ['refund', 'chargeback'], true);
    }

    private function isRefund(string $status): bool
    {
        return $this->isPartial($status) || $this->isFullRefund($status);
    }

    private function payloadRefundAmount(array $payload): int
    {
        return (int) round((float) ($payload['refund_amount'] ?? 0));
    }

    /**
     * Midtrans reports `refund_amount` as the cumulative refunded total, so it is
     * stored as-is rather than accumulated.
     */
    private function refundedAmount(Transaction $transaction, string $status, array $payload): int
    {
        if (! $this->isRefund($status)) {
            return (int) $transaction->refunded_amount;
        }

        $amount = $this->payloadRefundAmount($payload);

        if ($amount === 0 && $this->isFullRefund($status)) {
            $amount = $transaction->gross_amount;
        }

        if ($amount > $transaction->gross_amount) {
            throw ValidationException::withMessages([
                'refund_amount' => 'The refund amount exceeds the transaction amount.',
            ]);
        }

        return max($amount, (int) $transaction->refunded_amount);
    }

    private function paidAt(Transaction $transaction, string $status, ?string $fraudStatus): mixed
    {
        if (in_array($status, ['capture', 'settlement'], true) && $fraudStatus === 'accept') {
            return $transaction->paid_at ?? now();
        }

        return $this->isRefund($status) ? $transaction->paid_at : null;
    }
}
