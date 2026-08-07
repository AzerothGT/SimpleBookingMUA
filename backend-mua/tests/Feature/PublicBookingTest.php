<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-08-06 10:00:00'));
});

function publicBookingPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'service_id' => $service->id,
        'client_name' => 'Mei Lin',
        'client_phone' => '081234567890',
        'client_address' => 'Jl. Melati No. 10',
        'client_requested_date' => '2026-08-10',
        'client_requested_end_time' => '15:00',
    ], $overrides);
}

it('lists and shows only active services publicly', function () {
    $active = Service::factory()->create(['is_active' => true]);
    $inactive = Service::factory()->inactive()->create();

    $this->getJson('/api/services?include_inactive=true')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $active->id])
        ->assertJsonMissing(['id' => $inactive->id]);

    $this->getJson('/api/services/'.$active->id)->assertSuccessful();
    $this->getJson('/api/services/'.$inactive->id)->assertNotFound();
});

it('rejects inactive services and requested end times in the past', function () {
    $inactive = Service::factory()->inactive()->create();
    $active = Service::factory()->create(['is_active' => true]);

    $this->postJson('/api/bookings', publicBookingPayload($inactive))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('service_id');

    $this->postJson('/api/bookings', publicBookingPayload($active, [
        'client_requested_date' => '2026-08-06',
        'client_requested_end_time' => '09:00',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('client_requested_end_time');
});

it('requires map coordinates as a valid pair', function (array $coordinates, bool $valid) {
    $service = Service::factory()->create(['is_active' => true]);
    $response = $this->postJson('/api/bookings', publicBookingPayload($service, $coordinates));

    if ($valid) {
        $response->assertCreated();

        return;
    }

    $expectedErrors = match (true) {
        isset($coordinates['maps_lat']) && ! isset($coordinates['maps_lng']) => ['maps_lng'],
        isset($coordinates['maps_lng']) && ! isset($coordinates['maps_lat']) => ['maps_lat'],
        default => ['maps_lat', 'maps_lng'],
    };

    $response->assertUnprocessable()->assertJsonValidationErrors($expectedErrors);
})->with([
    'both absent' => [[], true],
    'both present' => [['maps_lat' => -6.2, 'maps_lng' => 106.8], true],
    'latitude only' => [['maps_lat' => -6.2], false],
    'longitude only' => [['maps_lng' => 106.8], false],
    'out of range' => [['maps_lat' => -91, 'maps_lng' => 181], false],
]);

it('rejects internal fields from public input', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $staff = User::factory()->create();

    $this->postJson('/api/bookings', publicBookingPayload($service, [
        'user_id' => $staff->id,
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 13:00:00',
        'status' => 'confirmed',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'starts_at', 'ends_at', 'status']);
});

it('sets safe defaults for public bookings', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $response = $this->postJson('/api/bookings', publicBookingPayload($service))->assertCreated();
    $booking = Booking::findOrFail($response->json('id'));

    expect($booking->user_id)->toBeNull()
        ->and($booking->starts_at)->toBeNull()
        ->and($booking->status)->toBe('pending')
        ->and($booking->client_requested_ends_at->toDateTimeString())->toBe('2026-08-10 15:00:00')
        ->and($booking->ends_at->equalTo($booking->client_requested_ends_at))->toBeTrue();
});

it('returns busy windows by requested date without blocking a proposal', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $staff = User::factory()->create();

    Booking::factory()->for($staff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 12:00:00',
    ]);
    Booking::factory()->for($staff)->create([
        'status' => 'cancelled',
        'starts_at' => '2026-08-10 13:00:00',
        'ends_at' => '2026-08-10 14:00:00',
    ]);

    $this->postJson('/api/schedule/check', [
        'client_requested_date' => '2026-08-10',
        'user_id' => $staff->id,
    ])->assertSuccessful()->assertJsonCount(1);

    $this->postJson('/api/bookings', publicBookingPayload($service))
        ->assertCreated();
});
