<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'order_id' => $this->order_id,
            'snap_token' => $this->snap_token,
            'redirect_url' => $this->redirect_url,
            'midtrans_transaction_id' => $this->midtrans_transaction_id,
            'gross_amount' => $this->gross_amount,
            'type' => $this->type,
            'payment_type' => $this->payment_type,
            'transaction_status' => $this->transaction_status,
            'fraud_status' => $this->fraud_status,
            'status_code' => $this->status_code,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => UserResource::make($this->whenLoaded('user')),
            'booking' => BookingResource::make($this->whenLoaded('booking')),
        ];
    }
}
