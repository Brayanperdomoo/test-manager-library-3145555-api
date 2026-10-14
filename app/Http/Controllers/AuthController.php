<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AuthController
{
    public function login(Request $request): JsonResponse
    {
        $input = $request->validate(['username' => ['required', 'string', 'max:120'], 'password' => ['required', 'string']]);
        $user = DB::table('library.user')->where('username', mb_strtolower(trim($input['username'])))->first();
        if (! $user || ! Hash::check($input['password'], $user->password_hash)) {
            return response()->json(['code' => 'INVALID_CREDENTIALS', 'message' => 'The username or password is incorrect.', 'details' => []], 401);
        }
        $now = time(); $token = JWT::encode(['sub' => $user->id, 'role' => $user->role, 'iat' => $now, 'exp' => $now + 28800], config('jwt.secret'), 'HS256');
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 28800, 'role' => $user->role]);
    }
}
