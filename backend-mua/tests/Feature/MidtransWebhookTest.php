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

it('acknowledges valid notifications for unknown order IDs', function () {
    $transaction = webhookTransaction();
    $unknown = midtransPayload($transaction, [
        'order_id' => 'payment_notif_test_M263923382_283ce562-636e-472c-a57d-eb236a5d963a',
    ]);

    $this->postJson('/api/webhooks/midtrans', $unknown)->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('pending');
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
        ->and($transaction->fresh()->paid_at)->not->toBeNull()
        ->and($transaction->fresh()->refunded_amount)->toBe($transaction->gross_amount);
});

it('records a chargeback after settlement', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'chargeback',
        'fraud_status' => null,
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('chargeback')
        ->and($transaction->fresh()->refunded_amount)->toBe($transaction->gross_amount)
        ->and($transaction->fresh()->paid_at)->not->toBeNull();
});

it('records a partial refund without clobbering the settled status', function (string $status) {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
        'gross_amount' => 1000000,
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => $status,
        'fraud_status' => null,
        'refund_amount' => '250000.00',
    ]))->assertSuccessful();

    $transaction->refresh();

    expect($transaction->transaction_status)->toBe('settlement')
        ->and($transaction->fraud_status)->toBe('accept')
        ->and($transaction->paid_at)->not->toBeNull()
        ->and($transaction->refunded_amount)->toBe(250000)
        ->and($transaction->paidAmount())->toBe(750000);
})->with(['partial_refund', 'partial_chargeback']);

it('treats the partial refund amount as cumulative instead of accumulating it', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
        'gross_amount' => 1000000,
    ]);

    foreach (['250000.00', '400000.00'] as $refundAmount) {
        $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
            'transaction_status' => 'partial_refund',
            'fraud_status' => null,
            'refund_amount' => $refundAmount,
        ]))->assertSuccessful();
    }

    expect($transaction->fresh()->refunded_amount)->toBe(400000);
});

it('ignores a replayed partial refund notification', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
        'gross_amount' => 1000000,
    ]);

    $payload = midtransPayload($transaction, [
        'transaction_status' => 'partial_refund',
        'fraud_status' => null,
        'refund_amount' => '250000.00',
    ]);

    $this->postJson('/api/webhooks/midtrans', $payload)->assertSuccessful();
    $this->postJson('/api/webhooks/midtrans', $payload)->assertSuccessful();

    expect($transaction->fresh()->refunded_amount)->toBe(250000)
        ->and(ActivityLog::where('action', 'transaction.webhook')->count())->toBe(1);
});

it('finalises a partially refunded transaction with a full refund', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
        'gross_amount' => 1000000,
        'refunded_amount' => 250000,
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'refund',
        'fraud_status' => null,
        'refund_amount' => '1000000.00',
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('refund')
        ->and($transaction->fresh()->refunded_amount)->toBe(1000000)
        ->and($transaction->fresh()->paidAmount())->toBe(0);
});

it('rejects a refund larger than the transaction amount', function () {
    $transaction = webhookTransaction([
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'paid_at' => now(),
        'gross_amount' => 1000000,
    ]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'partial_refund',
        'fraud_status' => null,
        'refund_amount' => '1200000.00',
    ]))->assertUnprocessable();

    expect($transaction->fresh()->refunded_amount)->toBe(0)
        ->and($transaction->fresh()->transaction_status)->toBe('settlement');
});

it('ignores a partial refund for a transaction that was never paid', function () {
    $transaction = webhookTransaction(['gross_amount' => 1000000]);

    $this->postJson('/api/webhooks/midtrans', midtransPayload($transaction, [
        'transaction_status' => 'partial_refund',
        'fraud_status' => null,
        'refund_amount' => '250000.00',
    ]))->assertSuccessful();

    expect($transaction->fresh()->transaction_status)->toBe('pending')
        ->and($transaction->fresh()->refunded_amount)->toBe(0);
});
