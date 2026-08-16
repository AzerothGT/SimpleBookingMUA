<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class ActivityLogController extends Controller
{
    #[OA\Get(
        path: '/activity-logs',
        summary: 'List activity logs',
        tags: ['ActivityLogs'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'entity_type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['booking', 'transaction', 'service', 'user'])),
            new OA\Parameter(name: 'entity_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'booking_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of activity logs'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->entity_type, fn ($q, $type) => $q->where('entity_type', $type))
            ->when($request->entity_id, fn ($q, $id) => $q->where('entity_id', $id))
            ->when($request->booking_id, fn ($q, $bookingId) => $q->where('booking_id', $bookingId))
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate();

        return ActivityLogResource::collection($logs);
    }

    #[OA\Get(
        path: '/activity-logs/{activity_log}',
        summary: 'Get activity log detail',
        tags: ['ActivityLogs'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'activity_log', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity log detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(ActivityLog $activity_log): ActivityLogResource
    {
        Gate::authorize('view', $activity_log);

        return ActivityLogResource::make($activity_log->load('user'));
    }
}
