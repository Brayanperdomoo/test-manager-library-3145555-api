<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthenticateToken;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->withoutMiddleware(AuthenticateToken::class);
Route::post('/auth/login', [AuthController::class, 'login'])->withoutMiddleware(AuthenticateToken::class);
Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);
Route::post('/books', [BookController::class, 'store'])->middleware('role:admin');
Route::put('/books/{id}', [BookController::class, 'update'])->middleware('role:admin');
Route::delete('/books/{id}', [BookController::class, 'destroy'])->middleware('role:admin');
Route::get('/members', [MemberController::class, 'index']);
Route::get('/members/{id}', [MemberController::class, 'show']);
Route::post('/members', [MemberController::class, 'store']);
Route::get('/loans/overdue', [LoanController::class, 'overdue']);
Route::get('/loans', [LoanController::class, 'index']);
Route::post('/loans', [LoanController::class, 'store']);
Route::get('/loans/{id}', [LoanController::class, 'show']);
Route::post('/loans/{loanId}/items/{itemId}/return', [LoanController::class, 'returnItem']);
Route::get('/reports/most-borrowed', [ReportController::class, 'mostBorrowed'])->middleware('role:admin');
