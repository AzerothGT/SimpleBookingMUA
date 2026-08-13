<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('serializes concurrent schedule assignments for the same staff', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrent row-lock verification requires MySQL.');
    }

    $actor = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();
    $bookings = Booking::factory()->unassigned()->count(2)->create();
    $bookingIds = $bookings->pluck('id')->all();
    $serviceIds = DB::table('booking_service')->whereIn('booking_id', $bookingIds)->pluck('service_id')->all();
    $payload = [
        'user_id' => $staff->id,
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ];

    try {
        $worker = base_path('tests/Support/assign-booking-schedule.php');
        $processes = collect($bookingIds)->map(fn (string $bookingId) => new Process([
            PHP_BINARY,
            $worker,
            $bookingId,
            $actor->id,
            $staff->id,
            $payload['starts_at'],
            $payload['ends_at'],
        ], base_path()));

        $processes->each->start();
        $processes->each->wait();

        $results = $processes->map->getOutput()->all();
        sort($results);

        expect($results)->toBe(['accepted', 'rejected']);
    } finally {
        ActivityLog::whereIn('booking_id', $bookingIds)->delete();
        Booking::whereIn('id', $bookingIds)->delete();
        Service::whereIn('id', $serviceIds)->delete();
        User::whereIn('id', [$actor->id, $staff->id])->delete();
    }
});
