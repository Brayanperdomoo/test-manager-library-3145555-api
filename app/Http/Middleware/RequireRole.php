<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get('library_user');
        if (! $user || ! in_array($user['role'], $roles, true)) {
            return response()->json(['code' => 'FORBIDDEN_ROLE', 'message' => 'You are not allowed to perform this action.', 'details' => []], 403);
        }
        return $next($request);
    }
}
