<?php

namespace App\Http\Controllers;

use App\Actions\ActivityLogs\RecordActivity;
use App\Actions\Services\UpdateService;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class ServiceController extends Controller
{
    #[OA\Get(
        path: '/services',
        summary: 'List services',
        tags: ['Services'],
        parameters: [
            new OA\Parameter(name: 'include_inactive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Sertakan service nonaktif (khusus owner/admin terautentikasi)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of active services'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $services = Service::query()
            ->with('serviceImages')
            ->when(! $this->wantsInactive($request), fn ($query) => $query->active())
            ->latest()
            ->paginate();

        return ServiceResource::collection($services);
    }

    /**
     * The /services listing is public (active-only). An authenticated
     * owner/admin may request inactive services too by passing
     * include_inactive=1. The token is resolved manually because this
     * route is not behind the auth.session middleware.
     */
    private function wantsInactive(Request $request): bool
    {
        if (! $request->boolean('include_inactive')) {
            return false;
        }

        $token = $request->bearerToken();
        if (! $token) {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (! $accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return false;
        }

        $user = User::active()->find($accessToken->tokenable_id);

        return $user instanceof User && in_array($user->role, ['owner', 'admin'], true);
    }

    #[OA\Get(
        path: '/services/{service}',
        summary: 'Get service detail with images',
        tags: ['Services'],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Service detail'),
            new OA\Response(response: 404, description: 'Service not found'),
        ]
    )]
    public function show(Service $service): ServiceResource
    {
        abort_unless($service->is_active, 404);

        return ServiceResource::make($service->load('serviceImages'));
    }

    #[OA\Post(
        path: '/services',
        summary: 'Create service',
        tags: ['Services'],
        security: [['session' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true),
                    new OA\Property(property: 'price', type: 'number', minimum: 0),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Service created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreServiceRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        Gate::authorize('create', Service::class);

        $service = Service::create($request->validated());

        $recordActivity->handle(
            $request->user(),
            $service,
            'service.created',
            detail: "Service {$service->name} created.",
        );

        return ServiceResource::make($service)->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/services/{service}',
        summary: 'Update service',
        tags: ['Services'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true),
                    new OA\Property(property: 'price', type: 'number', minimum: 0),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Service updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(
        UpdateServiceRequest $request,
        Service $service,
        UpdateService $updateService,
    ): ServiceResource {
        Gate::authorize('update', $service);

        $service = $updateService->handle($service, $request->user(), $request->validated());

        return ServiceResource::make($service);
    }

    #[OA\Delete(
        path: '/services/{service}',
        summary: 'Delete service',
        tags: ['Services'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Service deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(Service $service, Request $request, RecordActivity $recordActivity): Response
    {
        Gate::authorize('delete', $service);

        $recordActivity->handle(
            $request->user(),
            $service,
            'service.deleted',
            detail: "Service {$service->name} deleted.",
        );

        $service->delete();

        return response()->noContent();
    }
}
