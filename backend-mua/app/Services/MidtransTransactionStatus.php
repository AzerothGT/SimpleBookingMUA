<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MidtransTransactionStatus
{
    /** @return array<string, mixed> */
    public function fetch(string $orderId): array
    {
        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250)
            ->get(rtrim((string) config('services.midtrans.core_url'), '/').'/v2/'.$orderId.'/status')
            ->throw()
            ->json();
    }
}
