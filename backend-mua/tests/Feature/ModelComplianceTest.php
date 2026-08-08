<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers every activity log alias in the morph map', function () {
    expect(Relation::getMorphedModel('booking'))->toBe(Booking::class)
        ->and(Relation::getMorphedModel('transaction'))->toBe(Transaction::class)
        ->and(Relation::getMorphedModel('service'))->toBe(Service::class)
        ->and(Relation::getMorphedModel('user'))->toBe(User::class)
        ->and(Relation::getMorphedModel('task'))->toBe(BookingTask::class);
});

it('resolves activity log aliases through the morph map', function () {
    $booking = Booking::factory()->create();
    $log = ActivityLog::factory()->create([
        'entity_type' => 'booking',
        'entity_id' => $booking->id,
        'booking_id' => $booking->id,
    ]);

    expect($log->entity)->toBeInstanceOf(Booking::class);
});

it('uses polymorphic service activity logs and keeps booking context logs separate', function () {
    $service = Service::factory()->create();
    $booking = Booking::factory()->for($service)->create();

    ActivityLog::factory()->create([
        'entity_type' => 'service',
        'entity_id' => $service->id,
        'booking_id' => $booking->id,
    ]);
    ActivityLog::factory()->create([
        'entity_type' => 'booking',
        'entity_id' => $booking->id,
        'booking_id' => null,
    ]);

    expect($service->activityLogs())->toBeInstanceOf(MorphMany::class)
        ->and($service->activityLogs)->toHaveCount(1)
        ->and($booking->activityLogs)->toHaveCount(1)
        ->and($booking->entityActivityLogs)->toHaveCount(1);
});

it('blocks inactive users from authenticating via sanctum', function () {
    $activeUser = User::factory()->create(['is_active' => true]);
    $inactiveUser = User::factory()->inactive()->create();

    $activeToken = $activeUser->createToken('test', ['*'], now()->addDays(30))->plainTextToken;
    $inactiveToken = $inactiveUser->createToken('test', ['*'], now()->addDays(30))->plainTextToken;

    $this->withToken($activeToken)->getJson('/api/user')->assertSuccessful();
    $this->withToken($inactiveToken)->getJson('/api/user')->assertUnauthorized();
});

it('keeps task completion timestamp synchronized', function () {
    $task = BookingTask::factory()->create(['is_done' => false]);
    $doneAt = now();

    $task->update(['is_done' => true, 'done_at' => $doneAt]);
    expect($task->fresh()->done_at)->not->toBeNull();

    $task->update(['is_done' => false, 'done_at' => null]);
    expect($task->fresh()->done_at)->toBeNull();
});

it('uses password_hash for Laravel authentication and hashes plaintext values', function () {
    $user = User::factory()->create(['password_hash' => 'secret-password']);

    expect($user->getAuthPasswordName())->toBe('password_hash')
        ->and(Hash::check('secret-password', $user->fresh()->password_hash))->toBeTrue()
        ->and($user->fresh()->password_hash)->not->toBe('secret-password');
});
