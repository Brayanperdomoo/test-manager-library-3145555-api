<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MemberController
{
    public function index(Request $request): JsonResponse
    {
        $size = min(50, max(1, (int) $request->query('size', 20))); $page = max(1, (int) $request->query('page', 1));
        $query = DB::table('library.member as m')->select('m.id', 'm.full_name', 'm.document_number')->selectSub(
            DB::table('library.loan as l')->join('library.loan_item as i', 'i.loan_id', '=', 'l.id')->whereColumn('l.member_id', 'm.id')->whereNull('i.returned_at')->selectRaw('count(*)'), 'active_loans');
        if ($search = trim((string) $request->query('q', ''))) $query->where(fn ($q) => $q->where('m.full_name', 'ilike', "%$search%")->orWhere('m.document_number', 'ilike', "%$search%"));
        $total = (clone $query)->count(); $items = $query->orderBy('m.full_name')->offset(($page - 1) * $size)->limit($size)->get();
        return response()->json(compact('items', 'page', 'size', 'total'));
    }

    public function show(string $id): JsonResponse
    {
        $member = DB::table('library.member')->where('id', $id)->first(); abort_unless($member, 404);
        $member->active_loans = DB::table('library.loan')->join('library.loan_item', 'loan_item.loan_id', '=', 'loan.id')->where('member_id', $id)->whereNull('returned_at')->count();
        return response()->json($member);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:200'], 'document_number' => ['required', 'string', 'max:30', 'unique:library.member,document_number']]);
        $id = (string) Str::uuid(); DB::table('library.member')->insert(['id' => $id, 'full_name' => trim($data['full_name']), 'document_number' => trim($data['document_number'])]);
        return response()->json(['id' => $id, 'full_name' => trim($data['full_name']), 'document_number' => trim($data['document_number']), 'active_loans' => 0], 201);
    }
}
