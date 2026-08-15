<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\CreateSnapTransaction;
use App\Http\Resources\PublicBookingResource;
use App\Http\Resources\TransactionResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class PublicBookingController extends Controller
{
    public function status(Request $request, Booking $booking): PublicBookingResource
    {
        $this->authorizeToken($request, $booking);

        return PublicBookingResource::make($booking->load([
            'bookingServices.service',
            'transactions' => fn ($query) => $query->latest()->limit(1),
        ]));
    }

    public function createSnap(
        Request $request,
        Booking $booking,
        CreateSnapTransaction $createSnap,
    ): JsonResource {
        $this->authorizeToken($request, $booking);

        if ($booking->status !== 'confirmed' || ! $booking->starts_at || ! $booking->ends_at) {
            throw ValidationException::withMessages([
                'booking' => 'Payment is available after the booking schedule is confirmed.',
            ]);
        }

        $transaction = $booking->transactions()->latest()->first();
        if (! $transaction || $transaction->transaction_status !== 'pending') {
            $transaction = $createSnap->handle($booking, null);
        }

        return TransactionResource::make($transaction);
    }

    private function authorizeToken(Request $request, Booking $booking): void
    {
        $token = $request->query('token');
        if (! is_string($token) || ! $booking->hasValidPublicPaymentToken($token)) {
            abort(401, 'Invalid or expired booking access token.');
        }
    }
}
