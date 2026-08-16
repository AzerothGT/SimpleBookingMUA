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

        $transactions = $this->transactions;
        $transaction = $transactions->first();
        $paidAmount = (int) $transactions->filter(fn ($item) => $item->isPaid())->sum('gross_amount');
        $totalAmount = (int) round($services->sum(fn (array $service): float => $service['subtotal']));

        return [
            'id' => $this->id,
            'status' => $this->status,
            'client_requested_date' => $this->client_requested_date?->toDateString(),
            'client_requested_end_time' => $this->client_requested_end_time,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'services' => $services,
            'gross_amount' => $totalAmount,
            'payment_summary' => [
                'total' => $totalAmount,
                'paid' => $paidAmount,
                'remaining' => max(0, $totalAmount - $paidAmount),
                'minimum_dp' => $totalAmount < 500000 ? 50000 : (int) ceil($totalAmount * 0.10),
            ],
            'transactions' => $transactions->map(fn ($item): array => [
                'type' => $item->type,
                'gross_amount' => $item->gross_amount,
                'transaction_status' => $item->transaction_status,
                'paid_at' => $item->paid_at,
            ])->values(),
            'payment' => $transaction ? [
                'transaction_status' => $transaction->transaction_status,
                'fraud_status' => $transaction->fraud_status,
                'gross_amount' => $transaction->gross_amount,
                'paid_at' => $transaction->paid_at,
            ] : null,
        ];
    }
}
