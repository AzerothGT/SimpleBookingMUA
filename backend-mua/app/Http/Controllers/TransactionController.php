<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\CreateSnapTransaction;
use App\Actions\Transactions\HandleMidtransWebhook;
use App\Http\Requests\MidtransWebhookRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    #[OA\Get(
        path: '/bookings/{booking}/transactions',
        summary: 'List transactions for a booking',
        tags: ['Transactions'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of transactions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Booking $booking): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [Transaction::class, $booking]);

        $transactions = $booking->transactions()->with('user')->latest()->get();

        return TransactionResource::collection($transactions);
    }

    #[OA\Post(
        path: '/bookings/{booking}/transactions/snap',
        summary: 'Create Midtrans Snap token for booking',
        tags: ['Transactions'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Snap token created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function createSnap(
        Request $request,
        Booking $booking,
        CreateSnapTransaction $createSnap,
    ): JsonResponse {
        Gate::authorize('create', [Transaction::class, $booking]);

        $transaction = $createSnap->handle($booking, $request->user());

        return TransactionResource::make($transaction)->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/bookings/{booking}/transactions/{transaction}',
        summary: 'Get transaction detail',
        tags: ['Transactions'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transaction detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Booking $booking, Transaction $transaction): TransactionResource
    {
        Gate::authorize('view', $transaction);

        return TransactionResource::make($transaction->load(['booking', 'user']));
    }

    #[OA\Post(
        path: '/webhooks/midtrans',
        summary: 'Midtrans webhook handler',
        tags: ['Transactions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'order_id', type: 'string'),
                    new OA\Property(property: 'transaction_id', type: 'string'),
                    new OA\Property(property: 'payment_type', type: 'string'),
                    new OA\Property(property: 'transaction_status', type: 'string'),
                    new OA\Property(property: 'fraud_status', type: 'string'),
                    new OA\Property(property: 'status_code', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Webhook received'),
        ]
    )]
    public function webhook(
        MidtransWebhookRequest $request,
        HandleMidtransWebhook $handleWebhook,
    ): JsonResponse {
        $handleWebhook->handle($request->validated());

        return response()->json(['message' => 'Webhook received']);
    }
}
