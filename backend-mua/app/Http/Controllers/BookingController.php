<?php

namespace App\Http\Controllers;

use App\Actions\Bookings\AssignBookingSchedule;
use App\Actions\Bookings\ChangeBookingStatus;
use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\ChangeBookingStatusRequest;
use App\Http\Requests\CheckScheduleRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class BookingController extends Controller
{
    #[OA\Get(
        path: '/bookings',
        summary: 'List bookings',
        tags: ['Bookings'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'done', 'cancelled'])),
            new OA\Parameter(name: 'client_name', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Cari berdasarkan nama klien (partial match)'),
            new OA\Parameter(name: 'client_phone', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of bookings'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->with(['user', 'service', 'transactions'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->client_name, fn ($q, $name) => $q->where('client_name', 'like', "%{$name}%"))
            ->when($request->client_phone, fn ($q, $phone) => $q->where('client_phone', $phone))
            ->latest()
            ->paginate();

        return BookingResource::collection($bookings);
    }

    #[OA\Post(
        path: '/bookings',
        summary: 'Create booking (client-facing)',
        tags: ['Bookings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['service_id', 'client_name', 'client_phone', 'client_address', 'client_requested_date', 'client_requested_end_time'],
                properties: [
                    new OA\Property(property: 'service_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'client_name', type: 'string'),
                    new OA\Property(property: 'client_phone', type: 'string'),
                    new OA\Property(property: 'client_address', type: 'string'),
                    new OA\Property(property: 'maps_url', type: 'string', format: 'uri'),
                    new OA\Property(property: 'maps_lat', type: 'number'),
                    new OA\Property(property: 'maps_lng', type: 'number'),
                    new OA\Property(property: 'client_requested_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'client_requested_end_time', type: 'string', format: 'time'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Booking created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(
        StoreBookingRequest $request,
        CreateBooking $createBooking,
    ): JsonResponse {
        $booking = $createBooking->handle($request->validated());

        return BookingResource::make($booking->load('service'))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/bookings/{booking}',
        summary: 'Get booking detail',
        tags: ['Bookings'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking detail with tasks, transactions, logs'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        return BookingResource::make($booking->load([
            'user',
            'service',
            'bookingTasks',
            'transactions',
            'activityLogs.user',
        ]));
    }

    #[OA\Put(
        path: '/bookings/{booking}',
        summary: 'Update booking (non-schedule fields)',
        tags: ['Bookings'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'client_name', type: 'string'),
                    new OA\Property(property: 'client_phone', type: 'string'),
                    new OA\Property(property: 'client_address', type: 'string'),
                    new OA\Property(property: 'maps_url', type: 'string', format: 'uri'),
                    new OA\Property(property: 'maps_lat', type: 'number'),
                    new OA\Property(property: 'maps_lng', type: 'number'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(
        UpdateBookingRequest $request,
        Booking $booking,
        UpdateBooking $updateBooking,
    ): BookingResource {
        Gate::authorize('update', $booking);

        $booking = $updateBooking->handle(
            $booking,
            $request->user(),
            $request->validated(),
        );

        return BookingResource::make($booking->load('service'));
    }

    public function changeStatus(
        ChangeBookingStatusRequest $request,
        Booking $booking,
        ChangeBookingStatus $changeStatus,
    ): BookingResource {
        Gate::authorize('update', $booking);

        $booking = $changeStatus->handle(
            $booking,
            $request->user(),
            $request->validated('status'),
        );

        return BookingResource::make($booking);
    }

    #[OA\Delete(
        path: '/bookings/{booking}',
        summary: 'Cancel booking',
        tags: ['Bookings'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Booking cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(
        Request $request,
        Booking $booking,
        ChangeBookingStatus $changeStatus,
    ): Response {
        Gate::authorize('delete', $booking);

        $changeStatus->handle($booking, $request->user(), 'cancelled');

        return response()->noContent();
    }

    #[OA\Post(
        path: '/bookings/{booking}/assign-staff',
        summary: 'Assign staff and set schedule (owner/staff only)',
        tags: ['Bookings'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'starts_at', 'ends_at'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'string', format: 'uuid', description: 'Staff ID to assign'),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', description: 'Jam mulai'),
                    new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', description: 'Jam selesai'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Staff assigned, booking updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assignStaff(
        AssignStaffRequest $request,
        Booking $booking,
        AssignBookingSchedule $assignSchedule,
    ): BookingResource {
        Gate::authorize('assignStaff', $booking);

        $booking = $assignSchedule->handle(
            $booking,
            $request->user(),
            $request->validated(),
        );

        return BookingResource::make($booking->load(['user', 'service']));
    }

    #[OA\Post(
        path: '/schedule/check',
        summary: 'Check busy schedule for a date',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'client_requested_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Tanggal yang dicek'),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter per staff (opsional)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of busy time windows'),
        ]
    )]
    public function checkAvailability(CheckScheduleRequest $request): JsonResponse
    {
        $query = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('starts_at')
            ->whereDate('starts_at', $request->client_requested_date);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $busy = $query->orderBy('starts_at')->get(['starts_at', 'ends_at']);

        return response()->json($busy);
    }
}
