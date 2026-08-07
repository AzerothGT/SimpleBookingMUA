<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'order_id' => Str::random(40),
            'snap_token' => null,
            'redirect_url' => null,
            'midtrans_transaction_id' => null,
            'gross_amount' => fake()->randomElement([500000, 1000000, 2000000, 5000000]),
            'type' => fake()->randomElement(['dp', 'pelunasan', 'refund']),
            'payment_type' => null,
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'status_code' => null,
            'paid_at' => null,
        ];
    }

    public function settled(): static
    {
        return $this->afterMaking(function (Transaction $transaction) {
            $transaction->transaction_status = 'settlement';
            $transaction->fraud_status = 'accept';
            $transaction->paid_at = now();
        })->state([]);
    }

    public function dp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'dp',
        ]);
    }

    public function fullPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pelunasan',
        ]);
    }
}
