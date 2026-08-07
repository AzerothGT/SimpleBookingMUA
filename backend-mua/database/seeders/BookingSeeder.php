<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();
        $staff = User::where('role', 'staff')->get();

        $bookings = [
            [
                'service_id' => $services[0]->id,
                'user_id' => $staff[0]->id,
                'client_name' => 'Siti Nurhaliza',
                'client_phone' => '081234567890',
                'client_address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
                'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
                'maps_lat' => -6.2088,
                'maps_lng' => 106.8456,
                'client_requested_date' => now()->addDays(2)->format('Y-m-d'),
                'client_requested_end_time' => '14:00',
                'client_requested_ends_at' => now()->addDays(2)->setTime(14, 0),
                'status' => 'confirmed',
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(14, 0),
            ],
            [
                'service_id' => $services[1]->id,
                'user_id' => $staff[1]->id,
                'client_name' => 'Rina Nose',
                'client_phone' => '081234567891',
                'client_address' => 'Jl. Sudirman No. 45, Jakarta Selatan',
                'maps_url' => 'https://maps.google.com/?q=-6.2250,106.8100',
                'maps_lat' => -6.2250,
                'maps_lng' => 106.8100,
                'client_requested_date' => now()->addDays(3)->format('Y-m-d'),
                'client_requested_end_time' => '16:00',
                'client_requested_ends_at' => now()->addDays(3)->setTime(16, 0),
                'status' => 'pending',
                'starts_at' => null,
                'ends_at' => now()->addDays(3)->setTime(16, 0),
            ],
            [
                'service_id' => $services[2]->id,
                'user_id' => $staff[0]->id,
                'client_name' => 'Dewi Persik',
                'client_phone' => '081234567892',
                'client_address' => 'Jl. Gatot Subroto No. 88, Jakarta Selatan',
                'maps_url' => 'https://maps.google.com/?q=-6.2297,106.8200',
                'maps_lat' => -6.2297,
                'maps_lng' => 106.8200,
                'client_requested_date' => now()->addDays(5)->format('Y-m-d'),
                'client_requested_end_time' => '18:00',
                'client_requested_ends_at' => now()->addDays(5)->setTime(18, 0),
                'status' => 'pending',
                'starts_at' => null,
                'ends_at' => now()->addDays(5)->setTime(18, 0),
            ],
            [
                'service_id' => $services[3]->id,
                'user_id' => $staff[1]->id,
                'client_name' => 'Ayu Ting Ting',
                'client_phone' => '081234567893',
                'client_address' => 'Jl. Thamrin No. 10, Jakarta Pusat',
                'maps_url' => 'https://maps.google.com/?q=-6.1944,106.8229',
                'maps_lat' => -6.1944,
                'maps_lng' => 106.8229,
                'client_requested_date' => now()->addDays(1)->format('Y-m-d'),
                'client_requested_end_time' => '12:00',
                'client_requested_ends_at' => now()->addDays(1)->setTime(12, 0),
                'status' => 'done',
                'starts_at' => now()->addDays(1)->setTime(8, 0),
                'ends_at' => now()->addDays(1)->setTime(12, 0),
            ],
        ];

        foreach ($bookings as $booking) {
            Booking::create($booking);
        }
    }
}
