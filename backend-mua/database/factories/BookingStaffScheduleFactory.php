<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStaffSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingStaffSchedule>
 */
class BookingStaffScheduleFactory extends Factory
{
    protected $model = BookingStaffSchedule::class;

    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 90))->setTime(fake()->numberBetween(8, 18), 0);

        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory()->staff(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
        ];
    }
}
