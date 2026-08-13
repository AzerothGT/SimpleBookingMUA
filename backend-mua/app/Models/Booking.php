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

#[Fillable([
    'user_id',
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
])]
class Booking extends Model
{
    use HasFactory;
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    public function bookingTasks(): HasMany
    {
        return $this->hasMany(BookingTask::class);
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
}
