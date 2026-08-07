<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.midtrans.server_key' => 'midtrans-secret']);
});

function midtransPayload(Transaction $transaction, array $overrides = []): array
{
    $payload = array_merge([
        'order_id' => $transaction->order_id,
        'status_code' => '200',
        'gross_amount' => $transaction->gross_amount.'.00',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'transaction_id' => 'midtrans-'.$transaction->id,
        'payment_type' => 'bank_transfer',
    ], $overrides);

    $payload['signature_key'] = hash('sha512',
        $payload['order_id']
        .$payload['status_code']
        .$payload['gross_amount']
        .config('services.midtrans.server_key')
    );

    return $payload;
}

function webhookTransaction(array $attributes = []): Transaction
{
    $staff = User::factory()->staff()->create();
    $booking = Booking::factory()->for($staff)->create([
        'status' => 'pending',
        'starts_at' => '2026-08-10 12:00:00',
        'ends_at' => '2026-08-10 15:00:00',
    ]);

    return Transaction::factory()->for($booking)->create($attributes);
}

it('validates required webhook fields', function () {
    $this->postJson('/api/webhooks/midtrans', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'order_id',
            'status_code',
            'gross_amount',
            'transaction_status',
            'signature_key',
        ]);
});

it('rejects webhooks when the server key is not configured', function () {
    config(['services.midtrans.server_key' => null]);
    $transaction = webhookTransaction();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction))
        ->assertServerError();

    expect($transaction->fresh()->transaction_status)->toBe('pending');
});

it('rejects forged signatures without mutation', function () {
    $transaction = webhookTransaction();
    $payload = midtransPayload($transaction);
    $payload['signature_key'] = str_repeat('0', 128);

    $this->postJson('/api/webhooks/midtrans', $payload)->assertUnprocessable();

    expect($transaction->fresh()->transaction_status)->toBe('pending');
});

it('rejects amount mismatch and unknown order IDs', function () {
    $transaction = webhookTransaction();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'gross_amount' => ($transaction->gross_amount + 1).'.00',
    ]))->assertUnprocessable();

    $unknown = midtransPayload($transaction, ['order_id' => 'UNKNOWN-ORDER']);
    $this->postJson('/api/webhooks/midtrans', $unknown)->assertNotFound();
});

it('processes successful payment and confirms a scheduled booking', function (string $status) {
    $transaction = webhookTransaction();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => $status,
    ]))->assertSuccessful();

    $log = ActivityLog::where('action', 'transaction.webhook')->firstOrFail();

    expect($transaction->fresh()->transaction_status)->toBe($status)
        ->and($transaction->fresh()->paid_at)->not->toBeNull()
        ->and($transaction->booking->fresh()->status)->toBe('confirmed')
        ->and($log->meta)->not->toHaveKey('signature_key');
})->with(['settlement', 'capture']);

it('does not confirm a booking without a complete schedule', function () {
    $booking = Booking::factory()->unassigned()->create(['status' => 'pending']);
    $transaction = Transaction::factory()->for($booking)->create();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction))
        ->assertSuccessful();

    expect($transaction->fresh()->paid_at)->not->toBeNull()
        ->and($booking->fresh()->status)->toBe('pending');
});

it('does not mark a fraud-denied capture as paid', function () {
    $transaction = webhookTransaction();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'capture',
        'fraud_status' => 'deny',
    ]))->assertSuccessful();

    expect($transaction->fresh()->paid_at)->toBeNull()
        ->and($transaction->booking->fresh()->status)->toBe('pending');
});

it('processes the same webhook idempotently', function () {
    $transaction = webhookTransaction();
    $payload = midtransPayload($transaction);

    $this->postJson('/api/webhooks/midtrans', $payload)->assertSuccessful();
    $this->postJson('/api/webhooks/midtrans', $payload)->assertSuccessful();

    expect(ActivityLog::where('action', 'transaction.webhook')->count())->toBe(1);
});

it('records unsuccessful terminal statuses without confirming booking', function (string $status) {
    $transaction = webhookTransaction();

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => $status,
        'fraud_status' => null,
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe($status)
        ->and($transaction->fresh()->paid_at)->toBeNull()
        ->and($transaction->booking->fresh()->status)->toBe('pending');
})->with(['deny', 'expire']);

it('does not downgrade a settled transaction from an older webhook', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'pending',
        'fraud_status' => null,
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('settlement');
});

it('allows refund after settlement without clearing paid_at', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'refund',
        'fraud_status' => null,
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('refund')
        ->and($transaction->fresh()->paid_at)->not->toBeNull();
});
