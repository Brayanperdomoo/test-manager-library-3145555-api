<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Loan extends Model
{
    protected $table = 'library.loan';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['loaned_at' => 'immutable_datetime'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void { static::creating(fn ($model) => $model->id ??= (string) Str::uuid()); }
    public function items() { return $this->hasMany(LoanItem::class, 'loan_id'); }
}
