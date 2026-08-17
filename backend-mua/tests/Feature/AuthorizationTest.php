<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restricts user management to superadmin', function (string $role, int $status) {
    $this->withToken(tokenForRole($role))
        ->getJson('/api/users')
        ->assertStatus($status);
})->with([
    'admin' => ['admin', 200],
    'owner' => ['owner', 403],
    'staff' => ['staff', 403],
]);

it('restricts service writes to owner and admin', function (string $role, int $status) {
    $this->withToken(tokenForRole($role))
        ->postJson('/api/services', [
            'name' => 'Wedding Makeup',
            'price' => 1_000_000,
            'is_active' => true,
        ])
        ->assertStatus($status);
})->with([
    'owner' => ['owner', 201],
    'admin' => ['admin', 201],
    'staff' => ['staff', 403],
]);

it('allows superadmins to create and promote every role', function () {
    $token = tokenForRole('admin');

    $this->withToken($token)
        ->postJson('/api/users', [
            'name' => 'New Owner',
            'username' => 'new-owner',
            'password' => 'secret-password',
            'role' => 'owner',
        ])
        ->assertCreated();

    $staff = User::factory()->staff()->create();

    $this->withToken($token)
        ->patchJson('/api/users/'.$staff->id, ['role' => 'owner'])
        ->assertSuccessful();
});

it('allows every internal role to access booking operations', function (string $role) {
    $booking = Booking::factory()->create();

    $this->withToken(tokenForRole($role))
        ->getJson('/api/bookings/'.$booking->id)
        ->assertSuccessful();
})->with(['owner', 'admin', 'staff']);

it('allows every internal role to view booking transactions', function (string $role) {
    $booking = Booking::factory()->create();

    $this->withToken(tokenForRole($role))
        ->getJson("/api/bookings/{$booking->id}/transactions")
        ->assertSuccessful();
})->with(['owner', 'admin', 'staff']);

it('restricts activity logs to superadmin', function (string $role, int $status) {
    ActivityLog::factory()->create();

    $this->withToken(tokenForRole($role))
        ->getJson('/api/activity-logs')
        ->assertStatus($status);
})->with([
    'admin' => ['admin', 200],
    'owner' => ['owner', 403],
    'staff' => ['staff', 403],
]);

it('rejects a service image outside the nested service', function () {
    $firstService = Service::factory()->create();
    $secondService = Service::factory()->create();
    $image = ServiceImage::factory()->for($firstService)->create();

    $this->withToken(tokenForRole('admin'))
        ->patchJson("/api/services/{$secondService->id}/serviceImages/{$image->id}", [
            'sort_order' => 2,
        ])
        ->assertNotFound();
});

it('rejects a transaction outside the nested booking', function () {
    $firstBooking = Booking::factory()->create();
    $secondBooking = Booking::factory()->create();
    $transaction = Transaction::factory()->for($firstBooking)->create();

    $this->withToken(tokenForRole('staff'))
        ->getJson("/api/bookings/{$secondBooking->id}/transactions/{$transaction->id}")
        ->assertNotFound();
});
