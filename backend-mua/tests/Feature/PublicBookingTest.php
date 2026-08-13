<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\Transaction;
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
        'services' => [['id' => $service->id, 'qty' => 1]],
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
        ->assertJsonValidationErrors('services.0.id');

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

it('returns only confirmed paid schedules in the public calendar', function () {
    $includedSettlement = Booking::factory()->create([
        'client_name' => 'Private Settlement Client',
        'client_phone' => '081111111111',
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 12:00:00',
    ]);
    Transaction::factory()->settled()->for($includedSettlement)->create();

    $includedCapture = Booking::factory()->create([
        'client_name' => 'Private Capture Client',
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 13:00:00',
        'ends_at' => '2026-08-10 14:00:00',
    ]);
    Transaction::factory()->for($includedCapture)->create([
        'transaction_status' => 'capture',
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    $wrongFraudStatus = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-11 08:00:00',
        'ends_at' => '2026-08-11 09:00:00',
    ]);
    Transaction::factory()->for($wrongFraudStatus)->create([
        'transaction_status' => 'settlement',
        'fraud_status' => 'challenge',
        'paid_at' => now(),
    ]);

    $missingPaidAt = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-11 10:00:00',
        'ends_at' => '2026-08-11 11:00:00',
    ]);
    Transaction::factory()->for($missingPaidAt)->create([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => null,
    ]);

    $invalidTransactionStatus = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-11 12:00:00',
        'ends_at' => '2026-08-11 13:00:00',
    ]);
    Transaction::factory()->for($invalidTransactionStatus)->create([
        'transaction_status' => 'pending',
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    foreach (['pending', 'cancelled'] as $status) {
        $excluded = Booking::factory()->create([
            'status' => $status,
            'starts_at' => '2026-08-12 10:00:00',
            'ends_at' => '2026-08-12 12:00:00',
        ]);
        Transaction::factory()->settled()->for($excluded)->create();
    }

    $unscheduled = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => null,
        'ends_at' => null,
    ]);
    Transaction::factory()->settled()->for($unscheduled)->create();

    $missingEnd = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-13 10:00:00',
        'ends_at' => null,
    ]);
    Transaction::factory()->settled()->for($missingEnd)->create();

    $outOfRange = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-09-02 10:00:00',
        'ends_at' => '2026-09-02 12:00:00',
    ]);
    Transaction::factory()->settled()->for($outOfRange)->create();

    $this->getJson('/api/schedule/calendar?from=2026-08-01&to=2026-08-31')
        ->assertSuccessful()
        ->assertExactJson([
            'data' => [
                [
                    'date' => '2026-08-10',
                    'busy_ranges' => [
                        [
                            'starts_at' => '2026-08-10T10:00:00.000000Z',
                            'ends_at' => '2026-08-10T12:00:00.000000Z',
                        ],
                        [
                            'starts_at' => '2026-08-10T13:00:00.000000Z',
                            'ends_at' => '2026-08-10T14:00:00.000000Z',
                        ],
                    ],
                ],
            ],
        ]);
});

it('includes schedules overlapping the calendar range boundary', function () {
    $overlapping = Booking::factory()->create([
        'status' => 'confirmed',
        'starts_at' => '2026-07-31 23:00:00',
        'ends_at' => '2026-08-01 00:00:00',
    ]);
    Transaction::factory()->settled()->for($overlapping)->create();

    $this->getJson('/api/schedule/calendar?from=2026-08-01&to=2026-08-31')
        ->assertSuccessful()
        ->assertExactJson([
            'data' => [
                [
                    'date' => '2026-07-31',
                    'busy_ranges' => [
                        [
                            'starts_at' => '2026-07-31T23:00:00.000000Z',
                            'ends_at' => '2026-08-01T00:00:00.000000Z',
                        ],
                    ],
                ],
            ],
        ]);
});

it('validates public calendar ranges', function (string $query, array $errors) {
    $this->getJson('/api/schedule/calendar?'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'required dates' => ['', ['from', 'to']],
    'valid dates' => ['from=not-a-date&to=also-not-a-date', ['from', 'to']],
    'ordered dates' => ['from=2026-08-10&to=2026-08-09', ['to']],
    'maximum one month' => ['from=2026-08-01&to=2026-09-02', ['to']],
    'month-end overflow' => ['from=2026-01-31&to=2026-03-02', ['to']],
]);

it('returns busy windows by requested date without blocking a proposal', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $staff = User::factory()->create();

    $confirmedPaid = Booking::factory()->for($staff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 10:00:00',
        'ends_at' => '2026-08-10 12:00:00',
    ]);
    Transaction::factory()->settled()->for($confirmedPaid)->create();

    $confirmedUnpaid = Booking::factory()->for($staff)->create([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 13:00:00',
    ]);
    Transaction::factory()->for($confirmedUnpaid)->create();

    $pendingPaid = Booking::factory()->for($staff)->create([
        'status' => 'pending',
        'starts_at' => '2026-08-10 13:00:00',
        'ends_at' => '2026-08-10 14:00:00',
    ]);
    Transaction::factory()->settled()->for($pendingPaid)->create();

    $this->postJson('/api/schedule/check', [
        'client_requested_date' => '2026-08-10',
        'user_id' => $staff->id,
    ])->assertSuccessful()->assertExactJson([
        [
            'starts_at' => '2026-08-10T10:00:00.000000Z',
            'ends_at' => '2026-08-10T12:00:00.000000Z',
        ],
    ]);

    $this->postJson('/api/bookings', publicBookingPayload($service))
        ->assertCreated();
});
