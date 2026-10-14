<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UserController
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate(['username' => ['required', 'string', 'max:120', 'unique:library.user,username'], 'password' => ['required', 'string', 'min:12', 'max:200'], 'role' => ['required', Rule::in(['librarian'])]]);
        $id = (string) Str::uuid();
        DB::table('library.user')->insert(['id' => $id, 'username' => $data['username'], 'password_hash' => Hash::make($data['password']), 'role' => $data['role']]);
        return response()->json(['id' => $id, 'username' => $data['username'], 'role' => 'librarian'], 201);
    }
}
