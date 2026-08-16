<?php

use App\Contracts\PaymentGateway;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-08-06 10:00:00'));
});

function publicPaymentBooking(): array
{
    $service = Service::factory()->create(['is_active' => true, 'price' => 500000]);
    $response = test()->postJson('/api/bookings', [
        'services' => [['id' => $service->id, 'qty' => 1]],
        'client_name' => 'Mei Lin',
        'client_phone' => '081234567890',
        'client_address' => 'Jl. Melati No. 10',
        'client_requested_date' => '2026-08-10',
        'client_requested_end_time' => '15:00',
    ])->assertCreated();

    return [Booking::findOrFail($response->json('id')), $response->json('payment_access_token')];
}

it('stores only a hashed expiring public payment credential', function () {
    [$booking, $token] = publicPaymentBooking();

    expect($token)->toBeString()->not->toBeEmpty()
        ->and($booking->payment_access_token_hash)->not->toBe($token)
        ->and($booking->payment_access_token_expires_at)->not->toBeNull()
        ->and($booking->hasValidPublicPaymentToken($token))->toBeTrue();
});

it('returns only safe booking status with a valid token', function () {
    [$booking, $token] = publicPaymentBooking();

    $this->getJson("/api/public/bookings/{$booking->id}/status?token={$token}")
        ->assertSuccessful()
        ->assertJsonPath('id', $booking->booking_code)
        ->assertJsonPath('id', fn ($value) => is_string($value) && strlen($value) === 8)
        ->assertJsonMissingPath('client_phone')
        ->assertJsonMissingPath('activity_logs');
});

it('resolves a public booking by uuid or booking code', function () {
    [$booking, $token] = publicPaymentBooking();

    foreach ([$booking->id, $booking->booking_code] as $key) {
        $this->getJson("/api/public/bookings/{$key}/status?token={$token}")
            ->assertSuccessful()
            ->assertJsonPath('id', $booking->booking_code);
    }

    $this->getJson("/api/public/bookings/NOTFOUND/status?token={$token}")->assertNotFound();
});

it('rejects invalid and expired public tokens', function () {
    [$booking, $token] = publicPaymentBooking();

    $this->getJson("/api/public/bookings/{$booking->id}/status?token=wrong")->assertUnauthorized();

    $booking->update(['payment_access_token_expires_at' => now()->subMinute()]);
    $this->getJson("/api/public/bookings/{$booking->id}/status?token={$token}")->assertUnauthorized();
});

it('does not create Snap before a booking is scheduled', function () {
    [$booking, $token] = publicPaymentBooking();

    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertUnprocessable();
});

it('creates a public Snap transaction for a scheduled pending booking', function () {
    [$booking, $token] = publicPaymentBooking();
    $booking->update([
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
        {
            return ['token' => 'scheduled-pending-token', 'redirect_url' => 'https://example.test/pay'];
        }
    });

    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertCreated()
        ->assertJsonPath('snap_token', 'scheduled-pending-token');
});

it('creates one public Snap transaction after scheduling', function () {
    [$booking, $token] = publicPaymentBooking();
    $booking->update([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    $calls = 0;
    app()->bind(PaymentGateway::class, function () use (&$calls) {
        return new class($calls) implements PaymentGateway
        {
            public function __construct(private int &$calls) {}

            public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
            {
                $this->calls++;

                return ['token' => 'public-snap-token', 'redirect_url' => 'https://example.test/pay'];
            }
        };
    });

    $first = $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertCreated()
        ->assertJsonPath('snap_token', 'public-snap-token');
    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertOk()
        ->assertJsonPath('id', $first->json('id'));

    expect($calls)->toBe(1)
        ->and(Transaction::where('booking_id', $booking->id)->count())->toBe(1)
        ->and(Transaction::where('booking_id', $booking->id)->value('user_id'))->toBeNull();
});

it('reuses a paid transaction without calling Midtrans again', function (string $paidStatus) {
    [$booking, $token] = publicPaymentBooking();
    $booking->update([
        'status' => 'confirmed',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    $calls = 0;
    app()->bind(PaymentGateway::class, function () use (&$calls) {
        return new class($calls) implements PaymentGateway
        {
            public function __construct(private int &$calls) {}

            public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
            {
                $this->calls++;

                return ['token' => 'public-snap-token', 'redirect_url' => 'https://example.test/pay'];
            }
        };
    });

    $first = $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertCreated();

    Transaction::findOrFail($first->json('id'))->update([
        'transaction_status' => $paidStatus,
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    $this->postJson("/api/public/bookings/{$booking->id}/transactions/snap?token={$token}")
        ->assertOk()
        ->assertJsonPath('id', $first->json('id'))
        ->assertJsonPath('transaction_status', $paidStatus);

    expect($calls)->toBe(1)
        ->and(Transaction::where('booking_id', $booking->id)->count())->toBe(1);
})->with([
    'settlement' => ['settlement'],
    'capture' => ['capture'],
]);
