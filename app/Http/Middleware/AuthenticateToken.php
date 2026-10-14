<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AuthenticateToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/health') || $request->is('api/v1/auth/login')) return $next($request);
        $token = $request->bearerToken();
        if (! $token || ! config('jwt.secret')) return $this->unauthenticated();
        try {
            $claims = JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
            if (! isset($claims->sub, $claims->role) || ! in_array($claims->role, ['admin', 'librarian'], true)) return $this->unauthenticated();
            $request->attributes->set('library_user', ['id' => $claims->sub, 'role' => $claims->role]);
        } catch (Throwable) {
            return $this->unauthenticated();
        }
        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json(['code' => 'UNAUTHENTICATED', 'message' => 'Authentication is required.', 'details' => []], 401);
    }
}
