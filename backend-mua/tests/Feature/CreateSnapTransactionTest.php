<?php

use App\Contracts\PaymentGateway;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

it('creates a pending Snap transaction through the gateway', function () {
    ['user' => $actor, 'token' => $token] = authenticatedSession();
    $booking = Booking::factory()->create(['status' => 'pending']);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
        {
            expect($orderId)->not->toBeEmpty()
                ->and(mb_strlen($orderId))->toBeLessThanOrEqual(50)
                ->and($grossAmount)->toBeGreaterThan(0);

            return [
                'token' => 'snap-test-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/test',
            ];
        }
    });

    $response = $this->withToken($token)
        ->postJson('/api/bookings/'.$booking->id.'/transactions/snap')
        ->assertCreated()
        ->assertJsonPath('snap_token', 'snap-test-token');

    $transaction = Transaction::findOrFail($response->json('id'));

    expect($transaction->user_id)->toBe($actor->id)
        ->and($transaction->booking_id)->toBe($booking->id)
        ->and($transaction->transaction_status)->toBe('pending')
        ->and($transaction->gross_amount)->toBeGreaterThan(0)
        ->and(mb_strlen($transaction->order_id))->toBeLessThanOrEqual(50)
        ->and(ActivityLog::where('action', 'transaction.created')->exists())->toBeTrue();
});

it('rejects Snap creation for terminal bookings', function (string $status) {
    $booking = Booking::factory()->create(['status' => $status]);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
        {
            throw new RuntimeException('Gateway must not be called.');
        }
    });

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/transactions/snap')
        ->assertUnprocessable();
})->with(['cancelled', 'done']);

it('does not persist a transaction when Midtrans fails', function () {
    $booking = Booking::factory()->create(['status' => 'pending']);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
        {
            throw new RuntimeException('Midtrans unavailable.');
        }
    });

    $this->withToken(tokenForRole('admin'))
        ->postJson('/api/bookings/'.$booking->id.'/transactions/snap')
        ->assertServerError();

    expect(Transaction::where('booking_id', $booking->id)->exists())->toBeFalse();
});
