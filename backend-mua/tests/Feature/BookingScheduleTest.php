<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schedulePayload(User $staff, array $overrides = []): array
{
    return array_merge([
        'user_id' => $staff->id,
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ], $overrides);
}

it('requires a complete final schedule', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', [
            'user_id' => $staff->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_at', 'ends_at']);
});

it('rejects inactive staff', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->inactive()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('assigns a valid schedule and records the adjustment', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->create();

    $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertSuccessful()
        ->assertJsonPath('user_id', $staff->id);

    $booking->refresh();
    $log = ActivityLog::where('action', 'booking.schedule_adjusted')->firstOrFail();

    expect($booking->starts_at->toDateTimeString())->toBe('2026-08-10 12:00:00')
        ->and($booking->ends_at->toDateTimeString())->toBe('2026-08-10 15:00:00')
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('booking')
        ->and($log->entity_id)->toBe($booking->id)
        ->and($log->booking_id)->toBe($booking->id);
});

it('rejects overlapping active bookings for the same staff', function () {
    $staff = User::factory()->staff()->create();
    Booking::factory()->for($staff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 13:00:00',
    ]);
    $booking = Booking::factory()->unassigned()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff, [
            'starts_at' => '2026-08-10 12:00:00',
            'ends_at' => '2026-08-10 15:00:00',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('starts_at');

    expect($booking->fresh()->user_id)->toBeNull();
});

it('accepts a schedule starting exactly when another booking ends', function () {
    $staff = User::factory()->staff()->create();
    Booking::factory()->for($staff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 12:00:00',
    ]);
    $booking = Booking::factory()->unassigned()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertSuccessful();
});

it('excludes the current booking when its schedule is edited', function () {
    $staff = User::factory()->staff()->create();
    $booking = Booking::factory()->for($staff)->create([
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff, [
            'starts_at' => '2026-08-10 11:00:00',
            'ends_at' => '2026-08-10 14:00:00',
        ]))
        ->assertSuccessful();
});
