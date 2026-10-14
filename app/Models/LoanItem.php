<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class LoanItem extends Model
{
    protected $table = 'library.loan_item';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['due_at' => 'immutable_datetime', 'returned_at' => 'immutable_datetime'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void { static::creating(fn ($model) => $model->id ??= (string) Str::uuid()); }
}
