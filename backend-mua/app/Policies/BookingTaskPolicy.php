<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\User;

class BookingTaskPolicy
{
    public function create(User $user, Booking $booking): bool
    {
        return $user->is_active;
    }

    public function update(User $user, BookingTask $bookingTask): bool
    {
        return $user->is_active;
    }

    public function delete(User $user, BookingTask $bookingTask): bool
    {
        return $user->is_active;
    }
}
