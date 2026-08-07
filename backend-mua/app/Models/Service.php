<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'price', 'is_active'])]
class Service extends Model
{
    public const UPDATED_AT = null;

    use HasFactory;
    use HasUuids;

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function serviceImages(): HasMany
    {
        return $this->hasMany(ServiceImage::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'entity');
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function coverImage(): ?ServiceImage
    {
        return $this->serviceImages()->where('is_cover', true)->first();
    }
}
