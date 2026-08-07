<?php

namespace App\Contracts;

use App\Models\Booking;

interface PaymentGateway
{
    /** @return array{token: string, redirect_url: string} */
    public function createSnap(Booking $booking, string $orderId, int $grossAmount): array;
}
