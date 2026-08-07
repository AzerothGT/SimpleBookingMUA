<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scheduledPendingBooking(): Booking
{
    $staff = User::factory()->staff()->create();

    return Booking::factory()->for($staff)->create([
        'status' => 'pending',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);
}

it('rejects status and immutable proposal fields on generic update', function () {
    $booking = Booking::factory()->create();

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/bookings/'.$booking->id, [
            'status' => 'done',
            'client_requested_date' => '2026-09-01',
            'client_requested_end_time' => '18:00',
            'client_requested_ends_at' => '2026-09-01 18:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'status',
            'client_requested_date',
            'client_requested_end_time',
            'client_requested_ends_at',
        ]);

    expect($booking->fresh()->status)->toBe('pending');
});

it('updates editable fields and records before and after metadata', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = Booking::factory()->create(['notes' => 'Old note']);

    $this->withToken($token)
        ->patchJson('/api/bookings/'.$booking->id, [
            'notes' => 'New note',
            'client_address' => 'New address',
        ])
        ->assertSuccessful();

    $log = ActivityLog::where('action', 'booking.updated')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->booking_id)->toBe($booking->id)
        ->and($log->meta['before']['notes'])->toBe('Old note')
        ->and($log->meta['after']['notes'])->toBe('New note');
});

it('requires a complete schedule and accepted settlement before confirmation', function (string $case) {
    $booking = $case === 'schedule'
        ? Booking::factory()->unassigned()->create()
        : scheduledPendingBooking();

    if ($case === 'payment') {
        Transaction::factory()->for($booking)->create([
            'transaction_status' => 'pending',
        ]);
    }

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/bookings/'.$booking->id.'/status', ['status' => 'confirmed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
})->with(['schedule', 'payment']);

it('confirms a scheduled paid booking and records the transition', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = scheduledPendingBooking();
    Transaction::factory()->settled()->for($booking)->create();

    $this->withToken($token)
        ->patchJson('/api/bookings/'.$booking->id.'/status', ['status' => 'confirmed'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'confirmed');

    $log = ActivityLog::where('action', 'booking.status_changed')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->meta)->toMatchArray([
            'before' => ['status' => 'pending'],
            'after' => ['status' => 'confirmed'],
        ]);
});

it('allows confirmed to done', function () {
    $booking = Booking::factory()->confirmed()->create();

    $this->withToken(tokenForRole('staff'))
        ->patchJson('/api/bookings/'.$booking->id.'/status', ['status' => 'done'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'done');
});

it('rejects illegal status transitions', function (string $from, string $to) {
    $booking = match ($from) {
        'pending' => Booking::factory()->create(['status' => 'pending']),
        'done' => Booking::factory()->done()->create(),
        'cancelled' => Booking::factory()->cancelled()->create(),
    };

    $this->withToken(tokenForRole('admin'))
        ->patchJson('/api/bookings/'.$booking->id.'/status', ['status' => $to])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
})->with([
    'pending to done' => ['pending', 'done'],
    'done to pending' => ['done', 'pending'],
    'cancelled to pending' => ['cancelled', 'pending'],
]);

it('allows pending and confirmed bookings to be cancelled', function (string $from) {
    $booking = $from === 'pending'
        ? Booking::factory()->create(['status' => 'pending'])
        : Booking::factory()->confirmed()->create();

    $this->withToken(tokenForRole('admin'))
        ->deleteJson('/api/bookings/'.$booking->id)
        ->assertNoContent();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and(ActivityLog::where('action', 'booking.status_changed')->exists())->toBeTrue();
})->with(['pending', 'confirmed']);
