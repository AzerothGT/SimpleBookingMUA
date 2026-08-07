<?php

namespace App\Http\Controllers;

use App\Actions\BookingTasks\ManageBookingTask;
use App\Http\Requests\StoreBookingTaskRequest;
use App\Http\Requests\UpdateBookingTaskRequest;
use App\Http\Resources\BookingTaskResource;
use App\Models\Booking;
use App\Models\BookingTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class BookingTaskController extends Controller
{
    #[OA\Post(
        path: '/bookings/{booking}/bookingTasks',
        summary: 'Add task to booking',
        tags: ['BookingTasks'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(
        StoreBookingTaskRequest $request,
        Booking $booking,
        ManageBookingTask $manageTask,
    ): JsonResponse {
        Gate::authorize('create', [BookingTask::class, $booking]);

        $bookingTask = $manageTask->create(
            $booking,
            $request->user(),
            $request->validated(),
        );

        return BookingTaskResource::make($bookingTask)->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/bookings/{booking}/bookingTasks/{bookingTask}',
        summary: 'Update booking task',
        tags: ['BookingTasks'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'bookingTask', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'is_done', type: 'boolean'),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(
        UpdateBookingTaskRequest $request,
        Booking $booking,
        BookingTask $bookingTask,
        ManageBookingTask $manageTask,
    ): BookingTaskResource {
        Gate::authorize('update', $bookingTask);

        $bookingTask = $manageTask->update(
            $bookingTask,
            $request->user(),
            $request->validated(),
        );

        return BookingTaskResource::make($bookingTask);
    }

    #[OA\Delete(
        path: '/bookings/{booking}/bookingTasks/{bookingTask}',
        summary: 'Delete booking task',
        tags: ['BookingTasks'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'bookingTask', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Task deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(
        Request $request,
        Booking $booking,
        BookingTask $bookingTask,
        ManageBookingTask $manageTask,
    ): Response {
        Gate::authorize('delete', $bookingTask);

        $manageTask->delete($bookingTask, $request->user());

        return response()->noContent();
    }
}
