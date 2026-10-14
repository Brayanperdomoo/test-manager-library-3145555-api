<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LoanController
{
    public function index(Request $request): JsonResponse
    {
        $size = min(50, max(1, (int)$request->query('size',20))); $page = max(1,(int)$request->query('page',1)); $now = now('UTC');
        $query = DB::table('library.loan as l')->select('l.*')->orderByDesc('l.loaned_at');
        if ($request->filled('member_id')) $query->where('l.member_id',$request->query('member_id'));
        if ($request->filled('from')) $query->where('l.loaned_at','>=',Carbon::parse($request->query('from'),'UTC')->startOfDay());
        if ($request->filled('to')) $query->where('l.loaned_at','<',Carbon::parse($request->query('to'),'UTC')->addDay()->startOfDay());
        if ($status = $request->query('status')) {
            if ($status === 'closed') $query->whereNotExists(fn($q)=>$q->selectRaw('1')->from('library.loan_item as i')->whereColumn('i.loan_id','l.id')->whereNull('i.returned_at'));
            elseif ($status === 'overdue') $query->whereExists(fn($q)=>$q->selectRaw('1')->from('library.loan_item as i')->whereColumn('i.loan_id','l.id')->whereNull('i.returned_at')->where('i.due_at','<',$now));
            elseif ($status === 'open') $query->whereExists(fn($q)=>$q->selectRaw('1')->from('library.loan_item as i')->whereColumn('i.loan_id','l.id')->whereNull('i.returned_at')->where('i.due_at','>=',$now))->whereNotExists(fn($q)=>$q->selectRaw('1')->from('library.loan_item asi')->whereColumn('asi.loan_id','l.id')->whereNull('asi.returned_at')->where('asi.due_at','<',$now));
        }
        $total=(clone $query)->count(); $rows=$query->offset(($page-1)*$size)->limit($size)->get(); $items=$rows->map(fn($loan)=>$this->detail($loan));
        return response()->json(compact('items','page','size','total'));
    }

    public function show(string $id): JsonResponse { $loan=DB::table('library.loan')->where('id',$id)->first(); abort_unless($loan,404); return response()->json($this->detail($loan)); }

    public function store(Request $request): JsonResponse
    {
        $data=$request->validate(['member_id'=>['required','uuid'],'items'=>['required','array','min:1','max:3'],'items.*.book_id'=>['required','uuid']]);
        $bookIds=array_column($data['items'],'book_id');
        if (count(array_unique($bookIds)) !== count($bookIds)) return $this->error('DUPLICATE_BOOK_IN_LOAN','A book can only appear once per loan.',422);
        $loanId=DB::transaction(function() use($data,$bookIds,$request) {
            $member=DB::table('library.member')->where('id',$data['member_id'])->lockForUpdate()->first(); abort_unless($member,404);
            $active=DB::table('library.loan')->join('library.loan_item','loan_item.loan_id','=','loan.id')->where('member_id',$member->id)->whereNull('returned_at')->count();
            if($active+count($bookIds)>3) return $this->error('MEMBER_LIMIT_EXCEEDED',"The member already has $active unreturned book(s).",422);
            if(DB::table('library.loan')->join('library.loan_item','loan_item.loan_id','=','loan.id')->where('member_id',$member->id)->whereNull('returned_at')->where('due_at','<',now('UTC'))->exists()) return $this->error('MEMBER_HAS_OVERDUE','The member has overdue books.',409);
            sort($bookIds); $books=DB::table('library.book')->whereIn('id',$bookIds)->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if($books->count()!==count($bookIds)) abort(404);
            foreach($bookIds as $id) if($books[$id]->available_copies<1) return $this->error('BOOK_NOT_AVAILABLE','No copies of this book are available.',409);
            $now=now('UTC'); $loanId=(string)Str::uuid(); DB::table('library.loan')->insert(['id'=>$loanId,'member_id'=>$member->id,'loaned_by_user_id'=>$request->attributes->get('library_user')['id'],'loaned_at'=>$now]);
            foreach($bookIds as $id) { $book=$books[$id]; DB::table('library.book')->where('id',$id)->decrement('available_copies'); DB::table('library.loan_item')->insert(['id'=>(string)Str::uuid(),'loan_id'=>$loanId,'book_id'=>$id,'book_title'=>$book->title,'isbn'=>$book->isbn,'category_name'=>DB::table('library.category')->where('id',$book->category_id)->value('name'),'due_at'=>$now->copy()->addDays(14),'returned_at'=>null]); }
            return $loanId;
        });
        if($loanId instanceof JsonResponse) return $loanId;
        return response()->json($this->detail(DB::table('library.loan')->where('id',$loanId)->first()),201);
    }

    public function returnItem(string $loanId,string $itemId): JsonResponse
    {
        $result=DB::transaction(function() use($loanId,$itemId) {
            $item=DB::table('library.loan_item')->where('id',$itemId)->where('loan_id',$loanId)->lockForUpdate()->first(); abort_unless($item,404);
            if($item->returned_at) return $this->error('ITEM_ALREADY_RETURNED','This loan item has already been returned.',409);
            $book=DB::table('library.book')->where('id',$item->book_id)->lockForUpdate()->first(); abort_unless($book,404);
            if($book->available_copies >= $book->total_copies) abort(409);
            $returned=now('UTC'); DB::table('library.book')->where('id',$book->id)->increment('available_copies'); DB::table('library.loan_item')->where('id',$itemId)->update(['returned_at'=>$returned]);
            $item->returned_at=$returned; return $item;
        });
        if($result instanceof JsonResponse) return $result;
        return response()->json(['id'=>$result->id,'book_id'=>$result->book_id,'book_title'=>$result->book_title,'isbn'=>trim($result->isbn),'category_name'=>$result->category_name,'due_at'=>Carbon::parse($result->due_at)->utc()->toISOString(),'returned_at'=>Carbon::parse($result->returned_at)->utc()->toISOString()]);
    }

    public function overdue(): JsonResponse
    {
        $now=now('UTC'); $items=DB::table('library.loan_item as i')->join('library.loan as l','l.id','=','i.loan_id')->join('library.member as m','m.id','=','l.member_id')->whereNull('i.returned_at')->where('i.due_at','<',$now)->orderBy('i.due_at')->get(['l.id as loan_id','i.id as item_id','i.book_title','m.full_name as member_name','i.due_at'])->map(fn($i)=>['loan_id'=>$i->loan_id,'item_id'=>$i->item_id,'book_title'=>$i->book_title,'member_name'=>$i->member_name,'due_at'=>Carbon::parse($i->due_at)->utc()->toISOString(),'days_late'=>max(1,(int)Carbon::parse($i->due_at)->diffInDays($now))]);
        return response()->json(['items'=>$items]);
    }

    private function detail(object $loan): array
    {
        $items=DB::table('library.loan_item')->where('loan_id',$loan->id)->orderBy('id')->get()->map(fn($i)=>['id'=>$i->id,'book_id'=>$i->book_id,'book_title'=>$i->book_title,'isbn'=>trim($i->isbn),'category_name'=>$i->category_name,'due_at'=>Carbon::parse($i->due_at)->utc()->toISOString(),'returned_at'=>$i->returned_at?Carbon::parse($i->returned_at)->utc()->toISOString():null]);
        $status=$items->every(fn($i)=>$i['returned_at']!==null)?'closed':($items->contains(fn($i)=>$i['returned_at']===null && Carbon::parse($i['due_at'])->isPast())?'overdue':'open');
        return ['id'=>$loan->id,'member_id'=>$loan->member_id,'loaned_at'=>Carbon::parse($loan->loaned_at)->utc()->toISOString(),'status'=>$status,'items'=>$items];
    }
    private function error(string $code,string $message,int $status): JsonResponse { return response()->json(['code'=>$code,'message'=>$message,'details'=>[]],$status); }
}
