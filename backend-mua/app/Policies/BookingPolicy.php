<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->viewAny($user);
    }

    public function assignStaff(User $user, Booking $booking): bool
    {
        return $this->viewAny($user);
    }
}
