<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::whereIn('status', ['confirmed', 'done'])->get();
        $admin = User::where('role', 'admin')->first();

        foreach ($bookings as $booking) {
            Transaction::create([
                'booking_id' => $booking->id,
                'user_id' => $admin->id,
                'order_id' => Str::random(40),
                'gross_amount' => (int) $booking->service->price,
                'type' => 'dp',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'payment_type' => 'bank_transfer',
                'status_code' => '200',
                'paid_at' => now()->subDays(2),
            ]);
        }
    }
}
