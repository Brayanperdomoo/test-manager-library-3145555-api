<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ReportController
{
    public function mostBorrowed(Request $request): JsonResponse
    {
        $data=$request->validate(['from'=>['required','date_format:Y-m-d'],'to'=>['required','date_format:Y-m-d']]);
        if($data['to']<$data['from']) return response()->json(['code'=>'INVALID_DATE_RANGE','message'=>'The end date must be on or after the start date.','details'=>[]],422);
        $start=Carbon::createFromFormat('Y-m-d',$data['from'],'UTC')->startOfDay(); $end=Carbon::createFromFormat('Y-m-d',$data['to'],'UTC')->addDay()->startOfDay();
        $rows=DB::table('library.loan_item as i')->join('library.loan as l','l.id','=','i.loan_id')->where('l.loaned_at','>=',$start)->where('l.loaned_at','<',$end)->groupBy('i.book_id','i.book_title','i.category_name')->orderByDesc('times_borrowed')->orderBy('i.book_title')->get(['i.book_id','i.book_title','i.category_name',DB::raw('count(*) as times_borrowed')]);
        return response()->json(['from'=>$data['from'],'to'=>$data['to'],'rows'=>$rows->map(fn($row)=>['book_id'=>$row->book_id,'book_title'=>$row->book_title,'category_name'=>$row->category_name,'times_borrowed'=>(int)$row->times_borrowed])]);
    }
}
