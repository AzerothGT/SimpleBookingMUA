<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user, Booking $booking): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->is_active;
    }

    public function create(User $user, Booking $booking): bool
    {
        return $user->is_active;
    }
}
