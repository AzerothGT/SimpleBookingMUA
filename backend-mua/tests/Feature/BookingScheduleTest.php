<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingStaffSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schedulePayload(User $staff, array $overrides = []): array
{
    return array_merge([
        'staff' => [[
            'user_id' => $staff->id,
            'starts_at' => '2026-08-10 12:00:00',
        ]],
        'ends_at' => '2026-08-10 15:00:00',
    ], $overrides);
}

function multiStaffSchedulePayload(User $firstStaff, User $secondStaff, array $overrides = []): array
{
    return array_merge([
        'staff' => [
            ['user_id' => $firstStaff->id, 'starts_at' => '2026-08-10 12:00:00'],
            ['user_id' => $secondStaff->id, 'starts_at' => '2026-08-10 13:00:00'],
        ],
        'ends_at' => '2026-08-10 15:00:00',
    ], $overrides);
}

it('requires a complete final schedule', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['staff', 'ends_at']);
});

it('prevents staff actors from assigning schedules', function () {
    $actor = User::factory()->staff()->create();
    $staff = User::factory()->staff()->create();
    $booking = Booking::factory()->unassigned()->create();

    $this->withToken($actor->createToken('test', ['*'], now()->addDays(30))->plainTextToken)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertForbidden();
});

it('rejects inactive staff', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->inactive()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('staff.0.user_id');
});

it('assigns a valid schedule and records the adjustment', function () {
    $actor = User::factory()->admin()->create();
    $token = $actor->createToken('test', ['*'], now()->addDays(30))->plainTextToken;
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

it('assigns multiple staff with independent starts and a shared end', function () {
    $booking = Booking::factory()->unassigned()->create();
    $firstStaff = User::factory()->staff()->create();
    $secondStaff = User::factory()->staff()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', multiStaffSchedulePayload($firstStaff, $secondStaff))
        ->assertSuccessful()
        ->assertJsonCount(2, 'staff_schedules');

    $booking->refresh();

    expect($booking->staffSchedules)->toHaveCount(2)
        ->and($booking->user_id)->toBe($firstStaff->id)
        ->and($booking->starts_at->toDateTimeString())->toBe('2026-08-10 12:00:00')
        ->and($booking->ends_at->toDateTimeString())->toBe('2026-08-10 15:00:00')
        ->and($booking->staffSchedules->pluck('user_id')->all())
        ->toContain($firstStaff->id, $secondStaff->id);
});

it('keeps one schedule when the same staff assignment is adjusted', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->create();
    $token = tokenForRole('admin');

    $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff))
        ->assertSuccessful();

    $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($staff, [
            'staff' => [['user_id' => $staff->id, 'starts_at' => '2026-08-10 13:00:00']],
        ]))
        ->assertSuccessful();

    expect(BookingStaffSchedule::where('booking_id', $booking->id)->count())->toBe(1)
        ->and(BookingStaffSchedule::where('booking_id', $booking->id)->first()->starts_at->toDateTimeString())
        ->toBe('2026-08-10 13:00:00');
});

it('rejects duplicate staff entries in one request', function () {
    $booking = Booking::factory()->unassigned()->create();
    $staff = User::factory()->staff()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', [
            'staff' => [
                ['user_id' => $staff->id, 'starts_at' => '2026-08-10 12:00:00'],
                ['user_id' => $staff->id, 'starts_at' => '2026-08-10 13:00:00'],
            ],
            'ends_at' => '2026-08-10 15:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('staff.1.user_id');
});

it('requires every assignment to use the booking shared end', function () {
    $booking = Booking::factory()->unassigned()->create();
    $firstStaff = User::factory()->staff()->create();
    $secondStaff = User::factory()->staff()->create();
    $token = tokenForRole('admin');

    $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($firstStaff))
        ->assertSuccessful();

    $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', multiStaffSchedulePayload($firstStaff, $secondStaff, [
            'ends_at' => '2026-08-10 16:00:00',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ends_at');
});

it('allows overlapping times for different staff', function () {
    $firstStaff = User::factory()->staff()->create();
    $secondStaff = User::factory()->staff()->create();
    Booking::factory()->for($firstStaff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 13:00:00',
    ]);
    $booking = Booking::factory()->unassigned()->create();

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/assign-staff', schedulePayload($secondStaff, [
            'staff' => [['user_id' => $secondStaff->id, 'starts_at' => '2026-08-10 12:00:00']],
        ]))
        ->assertSuccessful();
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
            'staff' => [['user_id' => $staff->id, 'starts_at' => '2026-08-10 12:00:00']],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('staff');

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
            'staff' => [['user_id' => $staff->id, 'starts_at' => '2026-08-10 11:00:00']],
        ]))
        ->assertSuccessful();
});
