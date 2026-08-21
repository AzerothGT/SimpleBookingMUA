<?php

use App\Models\ActivityLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records public booking creation as a system activity', function () {
    $service = Service::factory()->create(['is_active' => true]);

    $response = $this->postJson('/api/bookings', [
        'services' => [['id' => $service->id, 'qty' => 1]],
        'client_name' => 'Mei Lin',
        'client_phone' => '081234567890',
        'client_address' => 'Jl. Melati No. 10',
        'client_requested_date' => now()->addWeek()->toDateString(),
        'client_requested_end_time' => '15:00',
    ])->assertCreated();

    $log = ActivityLog::where('action', 'booking.created')->firstOrFail();

    expect($log->user_id)->toBeNull()
        ->and($log->entity_type)->toBe('booking')
        ->and($log->entity_id)->toBe($response->json('id'))
        ->and($log->booking_id)->toBe($response->json('id'));
});

it('records user creation with the authenticated actor', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'Staff Baru',
        'username' => 'staff-baru',
        'password' => 'secret-password',
        'role' => 'staff',
    ])->assertCreated();

    $log = ActivityLog::where('action', 'user.created')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('user')
        ->and($log->entity_id)->toBe($response->json('id'));
});

it('records user updates and deactivation with before and after metadata', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);
    $staff = User::factory()->staff()->create(['name' => 'Old Name']);

    $this->withToken($token)
        ->patchJson('/api/users/'.$staff->id, ['name' => 'New Name'])
        ->assertSuccessful();

    $updated = ActivityLog::where('action', 'user.updated')->firstOrFail();
    expect($updated->meta['before']['name'])->toBe('Old Name')
        ->and($updated->meta['after']['name'])->toBe('New Name');

    $this->withToken($token)
        ->deleteJson('/api/users/'.$staff->id)
        ->assertNoContent();

    expect($staff->fresh()->is_active)->toBeFalse()
        ->and(ActivityLog::where('action', 'user.deactivated')->count())->toBe(1);
});

it('uses one consistent activity record per domain write', function () {
    expect(ActivityLog::query()->count())->toBe(0);

    $service = Service::factory()->create(['is_active' => true]);
    $this->postJson('/api/bookings', [
        'services' => [['id' => $service->id, 'qty' => 1]],
        'client_name' => 'Mei Lin',
        'client_phone' => '081234567890',
        'client_address' => 'Jl. Melati No. 10',
        'client_requested_date' => now()->addWeek()->toDateString(),
        'client_requested_end_time' => '15:00',
    ])->assertCreated();

    expect(ActivityLog::where('action', 'booking.created')->count())->toBe(1);
});

it('records service creation with the authenticated actor', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);

    $response = $this->withToken($token)->postJson('/api/services', [
        'name' => 'Wedding Makeup',
        'price' => 1_500_000,
    ])->assertCreated();

    $log = ActivityLog::where('action', 'service.created')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('service')
        ->and($log->entity_id)->toBe($response->json('id'));
});

it('records service updates with before and after metadata', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);
    $service = Service::factory()->create(['name' => 'Old Name']);

    $this->withToken($token)
        ->patchJson('/api/services/'.$service->id, ['name' => 'New Name'])
        ->assertSuccessful();

    $log = ActivityLog::where('action', 'service.updated')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->meta['before']['name'])->toBe('Old Name')
        ->and($log->meta['after']['name'])->toBe('New Name');
});

it('records service deletion with the authenticated actor', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);
    $service = Service::factory()->create();

    $this->withToken($token)
        ->deleteJson('/api/services/'.$service->id)
        ->assertNoContent();

    $log = ActivityLog::where('action', 'service.deleted')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('service')
        ->and($log->entity_id)->toBe($service->id);
});

it('records service image additions and removals', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $actor->update(['role' => 'admin']);
    $service = Service::factory()->create();

    $created = $this->withToken($token)
        ->postJson("/api/services/{$service->id}/serviceImages", [
            'image_url' => 'https://images.example.test/service.jpg',
            'image_source' => 'external',
        ])
        ->assertCreated();

    expect(ActivityLog::where('action', 'service.image_added')->count())->toBe(1);

    $this->withToken($token)
        ->deleteJson("/api/services/{$service->id}/serviceImages/{$created->json('id')}")
        ->assertNoContent();

    $log = ActivityLog::where('action', 'service.image_removed')->firstOrFail();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->entity_type)->toBe('service')
        ->and($log->entity_id)->toBe($service->id);
});
