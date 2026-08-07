<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingTask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a task and activity log', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = Booking::factory()->create();

    $response = $this->withToken($token)
        ->postJson("/api/bookings/{$booking->id}/bookingTasks", [
            'title' => 'Prepare makeup kit',
            'sort_order' => 1,
        ])
        ->assertCreated();

    $task = BookingTask::findOrFail($response->json('id'));
    $log = ActivityLog::where('action', 'task.created')->firstOrFail();

    expect($task->done_at)->toBeNull()
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('task')
        ->and($log->entity_id)->toBe($task->id)
        ->and($log->booking_id)->toBe($booking->id);
});

it('synchronizes done_at and logs each toggle', function () {
    ['token' => $token] = authenticatedSession();
    $booking = Booking::factory()->create();
    $task = BookingTask::factory()->for($booking)->create(['is_done' => false]);

    $this->withToken($token)
        ->patchJson("/api/bookings/{$booking->id}/bookingTasks/{$task->id}", ['is_done' => true])
        ->assertSuccessful();

    expect($task->fresh()->done_at)->not->toBeNull();

    $this->withToken($token)
        ->patchJson("/api/bookings/{$booking->id}/bookingTasks/{$task->id}", ['is_done' => false])
        ->assertSuccessful();

    expect($task->fresh()->done_at)->toBeNull()
        ->and(ActivityLog::where('action', 'task.toggled')->count())->toBe(2);
});

it('deletes a task and records the deletion', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = Booking::factory()->create();
    $task = BookingTask::factory()->for($booking)->create();

    $this->withToken($token)
        ->deleteJson("/api/bookings/{$booking->id}/bookingTasks/{$task->id}")
        ->assertNoContent();

    expect(BookingTask::find($task->id))->toBeNull()
        ->and(ActivityLog::where('action', 'task.deleted')->where('user_id', $actor->id)->exists())->toBeTrue();
});

it('cascades tasks when a booking is physically deleted', function () {
    $booking = Booking::factory()->create();
    $task = BookingTask::factory()->for($booking)->create();

    $booking->delete();

    expect(BookingTask::find($task->id))->toBeNull();
});
