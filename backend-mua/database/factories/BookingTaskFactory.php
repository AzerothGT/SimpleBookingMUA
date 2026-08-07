<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingTask>
 */
class BookingTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'title' => fake()->sentence(3),
            'is_done' => false,
            'sort_order' => 0,
            'done_at' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_done' => true,
            'done_at' => now(),
        ]);
    }
}
