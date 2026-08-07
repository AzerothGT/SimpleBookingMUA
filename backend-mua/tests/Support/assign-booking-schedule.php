<?php

use App\Actions\Bookings\AssignBookingSchedule;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    app(AssignBookingSchedule::class)->handle(
        Booking::findOrFail($argv[1]),
        User::findOrFail($argv[2]),
        [
            'user_id' => $argv[3],
            'starts_at' => $argv[4],
            'ends_at' => $argv[5],
        ],
    );

    echo 'accepted';
} catch (ValidationException) {
    echo 'rejected';
}
