<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an active user with a valid sanctum token helper', function () {
    ['user' => $user, 'token' => $token] = authenticatedSession();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->is_active->toBeTrue()
        ->and($token)->toBeString()->not->toBeEmpty();
});

it('creates a valid future booking helper', function () {
    $booking = validBooking();
    $booking->load('bookingServices.service');

    expect($booking)
        ->toBeInstanceOf(Booking::class)
        ->and($booking->bookingServices->first()->service->is_active)->toBeTrue()
        ->and($booking->client_requested_ends_at->isFuture())->toBeTrue()
        ->and($booking->starts_at)->toBeNull()
        ->and($booking->ends_at->equalTo($booking->client_requested_ends_at))->toBeTrue()
        ->and($booking->status)->toBe('pending');
});

it('keeps booking coordinates paired and schedule windows valid', function () {
    $bookings = Booking::factory()->count(100)->make();

    foreach ($bookings as $booking) {
        expect($booking->client_requested_ends_at->isFuture())->toBeTrue();
        expect($booking->maps_lat === null)->toBe($booking->maps_lng === null);
    }

    $confirmed = Booking::factory()->confirmed()->make();
    $done = Booking::factory()->done()->make();

    expect($confirmed->ends_at->greaterThan($confirmed->starts_at))->toBeTrue()
        ->and($done->ends_at->greaterThan($done->starts_at))->toBeTrue();
});

it('creates paid confirmed and done bookings', function (string $state) {
    $booking = Booking::factory()->{$state}()->create();

    expect($booking->transactions()
        ->where('transaction_status', 'settlement')
        ->where('fraud_status', 'accept')
        ->exists())->toBeTrue();
})->with(['confirmed', 'done']);

it('creates consistent booking-related activity logs', function () {
    $log = ActivityLog::factory()->bookingRelated()->make();

    expect($log->entity_type)->toBe('booking')
        ->and($log->entity_id)->not->toBeNull()
        ->and($log->booking_id)->toBe($log->entity_id);
});

it('keeps service image source consistent with its URL', function () {
    $images = ServiceImage::factory()->count(50)->make();

    foreach ($images as $image) {
        if ($image->image_source === 'external') {
            expect($image->image_url)->toStartWith('http');
        } else {
            expect($image->image_url)->toStartWith('services/');
        }
    }
});
