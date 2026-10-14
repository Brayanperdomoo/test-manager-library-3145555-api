<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BookController
{
    public function index(Request $request): JsonResponse
    {
        $size = min(50, max(1, (int) $request->query('size', 20))); $page = max(1, (int) $request->query('page', 1));
        $query = DB::table('library.book as b')->join('library.category as c', 'c.id', '=', 'b.category_id')->whereNull('b.deleted_at');
        if ($search = trim((string) $request->query('q', ''))) $query->where(fn ($q) => $q->where('b.title', 'ilike', "%$search%")->orWhere('b.author', 'ilike', "%$search%"));
        if ($category = $request->query('category_id')) $query->where('b.category_id', $category);
        $total = (clone $query)->count(); $items = $query->orderBy('b.title')->offset(($page - 1) * $size)->limit($size)->get(['b.id','b.title','b.author','b.isbn','b.category_id','c.name as category_name','b.total_copies','b.available_copies','b.cover_key'])->map(fn ($b) => $this->format($b));
        return response()->json(compact('items', 'page', 'size', 'total'));
    }

    public function show(string $id): JsonResponse { $book = $this->find($id); return response()->json($this->format($book)); }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['title' => ['required','string','max:250'], 'author' => ['required','string','max:200'], 'isbn' => ['required','regex:/^\d{13}$/'], 'category_id' => ['required','uuid','exists:library.category,id'], 'total_copies' => ['required','integer','min:1']]);
        if (DB::table('library.book')->where('isbn', $data['isbn'])->whereNull('deleted_at')->exists()) return $this->conflict('ISBN_TAKEN', 'An active book already uses this ISBN.');
        $id = (string) Str::uuid(); DB::table('library.book')->insert(['id'=>$id,'title'=>trim($data['title']),'author'=>trim($data['author']),'isbn'=>$data['isbn'],'category_id'=>$data['category_id'],'total_copies'=>$data['total_copies'],'available_copies'=>$data['total_copies'],'cover_key'=>null,'deleted_at'=>null]);
        return response()->json($this->format($this->find($id)), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['title'=>['required','string','max:250'],'author'=>['required','string','max:200'],'category_id'=>['required','uuid','exists:library.category,id'],'total_copies'=>['required','integer','min:1']]);
        $book = DB::transaction(function () use ($id, $data) {
            $book = DB::table('library.book')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first(); abort_unless($book, 404);
            $available = $book->available_copies + ($data['total_copies'] - $book->total_copies);
            abort_if($available < 0, 422, 'Total copies cannot be reduced below the number already on loan.');
            DB::table('library.book')->where('id', $id)->update(['title'=>trim($data['title']),'author'=>trim($data['author']),'category_id'=>$data['category_id'],'total_copies'=>$data['total_copies'],'available_copies'=>$available]);
            return $id;
        });
        return response()->json($this->format($this->find($book)));
    }

    public function destroy(string $id): JsonResponse
    {
        $book = $this->find($id);
        if (DB::table('library.loan_item')->where('book_id', $id)->whereNull('returned_at')->exists()) return $this->conflict('BOOK_HAS_OPEN_LOANS', 'This book has unreturned loan items.');
        DB::table('library.book')->where('id', $id)->update(['deleted_at' => now('UTC')]); return response()->json(null, 204);
    }

    private function find(string $id): object { $book = DB::table('library.book as b')->join('library.category as c','c.id','=','b.category_id')->where('b.id',$id)->whereNull('b.deleted_at')->first(['b.id','b.title','b.author','b.isbn','b.category_id','c.name as category_name','b.total_copies','b.available_copies','b.cover_key']); abort_unless($book,404); return $book; }
    private function format(object $b): array { return ['id'=>$b->id,'title'=>$b->title,'author'=>$b->author,'isbn'=>trim($b->isbn),'category'=>['id'=>$b->category_id,'name'=>$b->category_name],'total_copies'=>(int)$b->total_copies,'available_copies'=>(int)$b->available_copies,'cover_key'=>$b->cover_key]; }
    private function conflict(string $code, string $message): JsonResponse { return response()->json(['code'=>$code,'message'=>$message,'details'=>[]],409); }
}
