<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'booking_code',
    'client_name',
    'client_phone',
    'client_address',
    'maps_url',
    'maps_lat',
    'maps_lng',
    'client_requested_date',
    'client_requested_end_time',
    'client_requested_ends_at',
    'starts_at',
    'ends_at',
    'status',
    'notes',
    'payment_access_token_hash',
    'payment_access_token_expires_at',
])]
class Booking extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Resolve a booking by UUID or by its public 8-character booking code.
     *
     * The two are matched exclusively rather than with an `orWhere` so the
     * clause cannot leak past any other constraint on the query, and so a
     * booking code is never compared against a native uuid column.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        if ($field !== $this->getKeyName()) {
            return $query->where($this->qualifyColumn($field), $value);
        }

        return Str::isUuid($value)
            ? $query->where($this->qualifyColumn($field), $value)
            : $query->where($this->qualifyColumn('booking_code'), $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    public function staffSchedules(): HasMany
    {
        return $this->hasMany(BookingStaffSchedule::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function entityActivityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'entity');
    }

    protected function casts(): array
    {
        return [
            'maps_lat' => 'decimal:8',
            'maps_lng' => 'decimal:8',
            'client_requested_date' => 'date',
            'client_requested_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'payment_access_token_expires_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    #[Scope]
    protected function confirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    #[Scope]
    protected function done(Builder $query): Builder
    {
        return $query->where('status', 'done');
    }

    #[Scope]
    protected function cancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasValidPublicPaymentToken(string $token): bool
    {
        return $this->payment_access_token_expires_at?->isFuture() === true
            && Hash::check($token, $this->payment_access_token_hash ?? '');
    }
}
