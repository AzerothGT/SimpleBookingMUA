<?php

namespace App\Http\Controllers;

use App\Actions\Services\SaveServiceImage;
use App\Http\Requests\StoreServiceImageRequest;
use App\Http\Requests\UpdateServiceImageRequest;
use App\Http\Resources\ServiceImageResource;
use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class ServiceImageController extends Controller
{
    #[OA\Post(
        path: '/services/{service}/serviceImages',
        summary: 'Add image to service',
        tags: ['ServiceImages'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['image_url', 'image_source'],
                properties: [
                    new OA\Property(property: 'image_url', type: 'string'),
                    new OA\Property(property: 'image_source', type: 'string', enum: ['upload', 'external']),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                    new OA\Property(property: 'is_cover', type: 'boolean', description: 'Set true to make this the cover image'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Image added'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(
        StoreServiceImageRequest $request,
        Service $service,
        SaveServiceImage $saveImage,
    ): JsonResponse {
        Gate::authorize('update', $service);

        $serviceImage = $saveImage->create($service, $request->validated());

        return ServiceImageResource::make($serviceImage)->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/services/{service}/serviceImages/{serviceImage}',
        summary: 'Update service image',
        tags: ['ServiceImages'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'serviceImage', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'image_url', type: 'string'),
                    new OA\Property(property: 'image_source', type: 'string', enum: ['upload', 'external']),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                    new OA\Property(property: 'is_cover', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Image updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(
        UpdateServiceImageRequest $request,
        Service $service,
        ServiceImage $serviceImage,
        SaveServiceImage $saveImage,
    ): ServiceImageResource {
        Gate::authorize('update', $service);

        $serviceImage = $saveImage->update(
            $service,
            $serviceImage,
            $request->validated(),
        );

        return ServiceImageResource::make($serviceImage);
    }

    #[OA\Delete(
        path: '/services/{service}/serviceImages/{serviceImage}',
        summary: 'Delete service image',
        tags: ['ServiceImages'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'serviceImage', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Image deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(Service $service, ServiceImage $serviceImage): Response
    {
        Gate::authorize('update', $service);

        $serviceImage->delete();

        return response()->noContent();
    }
}
