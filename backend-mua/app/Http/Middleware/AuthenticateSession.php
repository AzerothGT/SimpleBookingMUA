<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $session = Session::query()
            ->with('user')
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->whereHas('user', fn ($query) => $query->active())
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $request->attributes->set('auth_session', $session);
        $request->setUserResolver(fn () => $session->user);
        Auth::setUser($session->user);

        return $next($request);
    }
}
