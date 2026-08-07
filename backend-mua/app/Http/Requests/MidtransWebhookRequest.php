<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MidtransWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:50'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'numeric', 'gt:0'],
            'transaction_status' => [
                'required',
                Rule::in(['pending', 'capture', 'settlement', 'deny', 'cancel', 'expire', 'failure', 'refund']),
            ],
            'fraud_status' => ['nullable', Rule::in(['accept', 'deny', 'challenge'])],
            'transaction_id' => ['nullable', 'string'],
            'payment_type' => ['nullable', 'string'],
            'signature_key' => ['required', 'string', 'size:128'],
        ];
    }
}
