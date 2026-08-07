<?php

namespace App\Http\Controllers;

use App\Actions\Users\ManageUser;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/users',
        summary: 'List users',
        tags: ['Users'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['owner', 'admin', 'staff'])),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of users'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->is_active))
            ->latest()
            ->paginate();

        return UserResource::collection($users);
    }

    #[OA\Post(
        path: '/users',
        summary: 'Create user',
        tags: ['Users'],
        security: [['session' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'username', type: 'string', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'instagram_url', type: 'string', format: 'uri'),
                    new OA\Property(property: 'role', type: 'string', enum: ['owner', 'admin', 'staff']),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(
        StoreUserRequest $request,
        ManageUser $manageUser,
    ): JsonResponse {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        Gate::authorize('assignRole', [User::class, $data['role']]);
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);

        $user = $manageUser->create($request->user(), $data);

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/users/{user}',
        summary: 'Get user detail',
        tags: ['Users'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User detail'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return UserResource::make($user);
    }

    #[OA\Put(
        path: '/users/{user}',
        summary: 'Update user',
        tags: ['Users'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'username', type: 'string', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'instagram_url', type: 'string', format: 'uri'),
                    new OA\Property(property: 'role', type: 'string', enum: ['owner', 'admin', 'staff']),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(
        UpdateUserRequest $request,
        User $user,
        ManageUser $manageUser,
    ): UserResource {
        Gate::authorize('update', $user);

        $data = $request->validated();
        if (isset($data['role'])) {
            Gate::authorize('assignRole', [User::class, $data['role']]);
        }

        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user = $manageUser->update($user, $request->user(), $data);

        return UserResource::make($user);
    }

    #[OA\Delete(
        path: '/users/{user}',
        summary: 'Delete user',
        tags: ['Users'],
        security: [['session' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(
        Request $request,
        User $user,
        ManageUser $manageUser,
    ): Response {
        Gate::authorize('delete', $user);

        $manageUser->deactivate($user, $request->user());

        return response()->noContent();
    }
}
