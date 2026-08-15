<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class PublicBookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $services = $this->bookingServices->map(fn ($bookingService): array => [
            'id' => $bookingService->service_id,
            'name' => $bookingService->service->name,
            'price' => (float) $bookingService->service->price,
            'qty' => $bookingService->qty,
            'subtotal' => (float) $bookingService->service->price * $bookingService->qty,
        ]);

        $transaction = $this->transactions->first();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'client_requested_date' => $this->client_requested_date?->toDateString(),
            'client_requested_end_time' => $this->client_requested_end_time,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'services' => $services,
            'gross_amount' => (int) round($services->sum(fn (array $service): float => $service['subtotal'])),
            'payment' => $transaction ? [
                'transaction_status' => $transaction->transaction_status,
                'fraud_status' => $transaction->fraud_status,
                'gross_amount' => $transaction->gross_amount,
                'paid_at' => $transaction->paid_at,
            ] : null,
        ];
    }
}
