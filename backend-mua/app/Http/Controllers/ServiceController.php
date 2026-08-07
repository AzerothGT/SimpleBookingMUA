<?php

namespace App\Http\Controllers;

use App\Actions\Services\UpdateService;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class ServiceController extends Controller
{
    #[OA\Get(
        path: '/services',
        summary: 'List services',
        tags: ['Services'],
        responses: [
            new OA\Response(response: 200, description: 'List of active services'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $services = Service::query()
            ->active()
            ->with('serviceImages')
            ->latest()
            ->paginate();

        return ServiceResource::collection($services);
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
    public function store(StoreServiceRequest $request): JsonResponse
    {
        Gate::authorize('create', Service::class);

        $service = Service::create($request->validated());

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

        $service = $updateService->handle($service, $request->validated());

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
    public function destroy(Service $service): Response
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return response()->noContent();
    }
}
