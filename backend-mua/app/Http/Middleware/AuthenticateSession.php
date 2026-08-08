<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = User::active()->find($accessToken->tokenable_id);

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user->withAccessToken($accessToken);
        $request->attributes->set('access_token', $accessToken);
        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);

        return $next($request);
    }
}
