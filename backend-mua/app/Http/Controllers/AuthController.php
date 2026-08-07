<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'SimpleBookingMUA API',
    version: '1.0.0',
    description: 'API untuk booking Makeup Artist'
)]
#[OA\Server(url: '/api')]
#[OA\SecurityScheme(
    securityScheme: 'session',
    type: 'http',
    scheme: 'bearer',
    description: 'Bearer token dari response login'
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/login',
        summary: 'Login user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login success, returns token'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account deactivated'], 403);
        }

        $session = Session::create([
            'user_id' => $user->id,
            'token' => $token = Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'token' => $token,
            'expires_at' => $session->expires_at,
            'user' => UserResource::make($user),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Logout user (revoke token)',
        tags: ['Auth'],
        security: [['session' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        /** @var Session $session */
        $session = $request->attributes->get('auth_session');
        $session->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Logged out successfully']);
    }

    #[OA\Get(
        path: '/user',
        summary: 'Get current authenticated user',
        tags: ['Auth'],
        security: [['session' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current user data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
