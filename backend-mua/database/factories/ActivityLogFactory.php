<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entity_type' => fake()->randomElement(['booking', 'transaction', 'service', 'user']),
            'entity_id' => null,
            'booking_id' => null,
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'status_changed', 'schedule_adjusted']),
            'detail' => fake()->sentence(),
            'meta' => null,
        ];
    }

    public function bookingRelated(?Booking $booking = null): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'entity_type' => 'booking',
            ])
            ->afterMaking(function (ActivityLog $log) use ($booking) {
                $booking ??= Booking::factory()->create();
                $log->entity_id = $booking->id;
                $log->booking_id = $booking->id;
            });
    }

    public function withMeta(array $meta): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => $meta,
        ]);
    }
}
