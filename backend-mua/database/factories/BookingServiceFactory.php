<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingService>
 */
class BookingServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'service_id' => Service::factory(),
            'qty' => fake()->numberBetween(1, 5),
        ];
    }
}