<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

final class CategoryController
{
    public function index() { return response()->json(DB::table('library.category')->orderBy('name')->get(['id', 'name'])); }
}
