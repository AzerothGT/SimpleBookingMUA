<?php

use App\Models\Booking;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the full database and attaches services to every booking', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Booking::count())->toBe(4);

    Booking::with('bookingServices')->get()->each(function (Booking $booking) {
        expect($booking->bookingServices)->not->toBeEmpty();
        $booking->bookingServices->each(function ($pivot) {
            expect($pivot->qty)->toBeInt()->toBeGreaterThanOrEqual(1);
        });
    });
});
