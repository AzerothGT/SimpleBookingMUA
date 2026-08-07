<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $requestedEndsAt = now()
            ->addDays(fake()->numberBetween(1, 90))
            ->setTime(fake()->numberBetween(8, 20), fake()->randomElement([0, 15, 30, 45]));
        $hasMapPin = fake()->boolean();

        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'client_name' => fake()->name(),
            'client_phone' => fake()->phoneNumber(),
            'client_address' => fake()->address(),
            'maps_url' => fake()->optional()->url(),
            'maps_lat' => $hasMapPin ? fake()->latitude() : null,
            'maps_lng' => $hasMapPin ? fake()->longitude() : null,
            'client_requested_date' => $requestedEndsAt->toDateString(),
            'client_requested_end_time' => $requestedEndsAt->format('H:i'),
            'client_requested_ends_at' => $requestedEndsAt,
            'starts_at' => null,
            'ends_at' => $requestedEndsAt,
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this
            ->afterMaking(function (Booking $booking) {
                $booking->status = 'confirmed';
                $booking->starts_at = $booking->client_requested_ends_at->copy()->subHours(fake()->numberBetween(1, 4));
                $booking->ends_at = $booking->client_requested_ends_at;
            })
            ->afterCreating(fn (Booking $booking) => Transaction::factory()->settled()->for($booking)->create());
    }

    public function done(): static
    {
        return $this
            ->afterMaking(function (Booking $booking) {
                $booking->status = 'done';
                $booking->starts_at = $booking->client_requested_ends_at->copy()->subHours(fake()->numberBetween(1, 4));
                $booking->ends_at = $booking->client_requested_ends_at;
            })
            ->afterCreating(fn (Booking $booking) => Transaction::factory()->settled()->for($booking)->create());
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
