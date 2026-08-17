<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns a consistent validation error envelope', function () {
    $response = $this->postJson('/api/bookings', [])->assertUnprocessable();

    expect(array_keys($response->json()))->toEqualCanonicalizing(['message', 'errors'])
        ->and($response->json('errors.services'))->toBeArray();
});

it('returns a message-only envelope for auth and lookup failures', function (string $method, string $uri, ?string $token, int $status) {
    config(['app.debug' => false]);

    $request = $token ? $this->withToken($token) : $this;

    $response = $request->json($method, $uri)->assertStatus($status);

    expect(array_keys($response->json()))->toBe(['message'])
        ->and($response->json('message'))->toBeString()->not->toBeEmpty();
})->with([
    'unauthenticated' => ['get', '/api/users', null, 401],
    'forbidden' => ['get', '/api/users', fn () => tokenForRole('staff'), 403],
    'not found' => ['get', '/api/bookings/'.'0199c0de-0000-4000-8000-000000000000', fn () => tokenForRole('staff'), 404],
]);

it('never exposes credentials or session tokens in user payloads', function () {
    $token = tokenForRole('admin');
    $staff = User::factory()->staff()->create();

    $payloads = [
        $this->withToken($token)->getJson('/api/user')->assertSuccessful()->json(),
        $this->withToken($token)->getJson('/api/users/'.$staff->id)->assertSuccessful()->json(),
        $this->withToken($token)->getJson('/api/users')->assertSuccessful()->json('data.0'),
    ];

    foreach ($payloads as $payload) {
        expect(array_keys($payload))->toEqualCanonicalizing([
            'id', 'name', 'username', 'phone', 'instagram_url', 'role', 'is_active', 'created_at',
        ]);
    }
});

it('limits public service payloads to catalogue fields', function () {
    $service = Service::factory()->create(['is_active' => true]);

    expect(array_keys($this->getJson('/api/services/'.$service->id)->assertSuccessful()->json()))
        ->toEqualCanonicalizing(['id', 'name', 'price', 'is_active', 'created_at', 'images']);
});

it('hides internal relations from the public booking response', function () {
    $service = Service::factory()->create(['is_active' => true]);

    $payload = $this->postJson('/api/bookings', [
        'services' => [['id' => $service->id, 'qty' => 1]],
        'client_name' => 'Rina',
        'client_phone' => '081234567890',
        'client_address' => 'Jl. Melati No. 10',
        'client_requested_date' => now()->addDays(3)->toDateString(),
        'client_requested_end_time' => '15:00',
    ])->assertCreated()->json();

    expect($payload)->toHaveKeys(['id', 'status', 'services'])
        ->and($payload)->not->toHaveKeys(['staff', 'transactions', 'activity_logs', 'service']);
});

it('keeps activity log payloads free of raw morph columns', function () {
    $log = ActivityLog::factory()->create();

    $payload = $this->withToken(tokenForRole('admin'))
        ->getJson('/api/activity-logs/'.$log->id)
        ->assertSuccessful()
        ->json();

    expect(array_keys($payload))->toEqualCanonicalizing([
        'id', 'action', 'entity_type', 'entity_id', 'booking_id', 'detail', 'meta', 'created_at', 'user',
    ]);
});

it('paginates listings and keeps booking queries constant regardless of row count', function () {
    $token = tokenForRole('owner');

    $count = function () use ($token): int {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->withToken($token)->getJson('/api/bookings')->assertSuccessful();

        return $queries;
    };

    Booking::factory()->count(2)->create()->each(
        fn (Booking $booking) => Transaction::factory()->for($booking)->create(),
    );
    $baseline = $count();

    Booking::factory()->count(5)->create()->each(
        fn (Booking $booking) => Transaction::factory()->for($booking)->create(),
    );
    $grown = $count();

    expect($grown)->toBe($baseline);

    $this->withToken($token)->getJson('/api/bookings')
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);
});
