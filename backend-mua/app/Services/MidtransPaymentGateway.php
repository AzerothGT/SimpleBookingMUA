<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class MidtransPaymentGateway implements PaymentGateway
{
    public function createSnap(Booking $booking, string $orderId, int $grossAmount): array
    {
        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250)
            ->post(rtrim((string) config('services.midtrans.snap_url'), '/').'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $booking->client_name,
                    'phone' => $booking->client_phone,
                ],
            ])
            ->throw();

        $token = (string) $response->json('token');
        $redirectUrl = (string) $response->json('redirect_url');

        if ($token === '' || $redirectUrl === '') {
            throw new UnexpectedValueException('Midtrans returned an incomplete Snap response.');
        }

        return [
            'token' => $token,
            'redirect_url' => $redirectUrl,
        ];
    }
}
