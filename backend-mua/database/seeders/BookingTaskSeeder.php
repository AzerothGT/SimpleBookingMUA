<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingTask;
use Illuminate\Database\Seeder;

class BookingTaskSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::all();

        $defaultTasks = [
            'Konfirmasi ketersediaan',
            'Siapkan peralatan makeup',
            'Hubungi client H-1',
            'Datang ke lokasi',
            'Lakukan makeup',
            'Dokumentasi hasil',
        ];

        foreach ($bookings as $booking) {
            foreach ($defaultTasks as $index => $title) {
                BookingTask::create([
                    'booking_id' => $booking->id,
                    'title' => $title,
                    'sort_order' => $index,
                    'is_done' => in_array($booking->status, ['done', 'confirmed']) && $index < 3,
                ]);
            }
        }
    }
}
