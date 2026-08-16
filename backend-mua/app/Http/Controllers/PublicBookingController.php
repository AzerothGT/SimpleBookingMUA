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
            'transactions' => fn ($query) => $query->latest(),
        ]));
    }

    public function createSnap(
        Request $request,
        Booking $booking,
        CreateSnapTransaction $createSnap,
    ): JsonResource {
        $this->authorizeToken($request, $booking);

        if (! $booking->starts_at || ! $booking->ends_at) {
            throw ValidationException::withMessages([
                'booking' => 'Payment is available after the booking schedule is set.',
            ]);
        }

        $type = $request->string('type', 'dp')->toString();
        if (! in_array($type, ['dp', 'pelunasan'], true)) {
            throw ValidationException::withMessages(['type' => 'Pilih pembayaran DP atau pelunasan.']);
        }

        $booking->load(['bookingServices.service', 'transactions']);
        $transaction = $booking->transactions
            ->where('type', $type)
            ->filter(fn ($item) => $item->isPending() || $item->isPaid())
            ->sortByDesc('created_at')
            ->first();

        if ($transaction?->isPaid() || $transaction?->isPending()) {
            return TransactionResource::make($transaction);
        }

        $total = (int) round($booking->bookingServices->sum(fn ($item) => (float) $item->service->price * $item->qty));
        $paid = (int) $booking->transactions->filter(fn ($item) => $item->isPaid())->sum('gross_amount');
        $remaining = max(0, $total - $paid);
        $minimumDp = $total < 500000 ? 50000 : (int) ceil($total * 0.10);
        $amount = $type === 'dp' ? min($remaining, $minimumDp) : $remaining;

        if ($amount <= 0 || ($type === 'dp' && $paid > 0)) {
            throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah tidak tersedia.']);
        }

        $transaction = $createSnap->handle($booking, null, $type, $amount);

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
