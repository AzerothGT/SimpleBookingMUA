<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'user_id',
    'order_id',
    'snap_token',
    'redirect_url',
    'midtrans_transaction_id',
    'gross_amount',
    'refunded_amount',
    'type',
    'payment_type',
    'transaction_status',
    'fraud_status',
    'status_code',
    'paid_at',
])]
class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'refunded_amount' => 'integer',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('transaction_status', 'pending');
    }

    #[Scope]
    protected function settled(Builder $query): Builder
    {
        return $query->where('transaction_status', 'settlement');
    }

    public function isPending(): bool
    {
        return $this->transaction_status === 'pending';
    }

    public function isSettled(): bool
    {
        return $this->transaction_status === 'settlement' && $this->fraud_status === 'accept';
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Amount still held by the merchant: the paid amount net of any refund.
     */
    public function paidAmount(): int
    {
        if (! $this->isPaid()) {
            return 0;
        }

        return max(0, $this->gross_amount - (int) $this->refunded_amount);
    }
}
