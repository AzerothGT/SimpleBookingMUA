<?php

use App\Contracts\PaymentGateway;
use App\Models\Booking;
use App\Services\MidtransPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('binds the payment contract to the Midtrans adapter', function () {
    expect(app(PaymentGateway::class))->toBeInstanceOf(MidtransPaymentGateway::class);
});

it('rejects incomplete Midtrans Snap responses', function () {
    config([
        'services.midtrans.server_key' => 'server-secret',
        'services.midtrans.snap_url' => 'https://midtrans.test',
    ]);
    Http::fake([
        'https://midtrans.test/snap/v1/transactions' => Http::response([]),
    ]);

    expect(fn () => app(MidtransPaymentGateway::class)->createSnap(
        Booking::factory()->create(),
        'MUA-ORDER',
        500000,
    ))->toThrow(UnexpectedValueException::class);
});

it('creates Snap through Midtrans HTTP API and maps the response', function () {
    config([
        'services.midtrans.server_key' => 'server-secret',
        'services.midtrans.snap_url' => 'https://midtrans.test',
    ]);
    Http::fake([
        'https://midtrans.test/snap/v1/transactions' => Http::response([
            'token' => 'snap-token',
            'redirect_url' => 'https://midtrans.test/snap/token',
        ]),
    ]);

    $booking = Booking::factory()->create();
    $result = app(MidtransPaymentGateway::class)->createSnap($booking, 'MUA-ORDER', 500000);

    expect($result)->toBe([
        'token' => 'snap-token',
        'redirect_url' => 'https://midtrans.test/snap/token',
    ]);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://midtrans.test/snap/v1/transactions'
            && $request->data()['transaction_details']['order_id'] === 'MUA-ORDER'
            && $request->data()['transaction_details']['gross_amount'] === 500000
            && $request->header('Authorization') === ['Basic '.base64_encode('server-secret:')];
    });
});
