<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Book extends Model
{
    protected $table = 'library.book';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['total_copies' => 'integer', 'available_copies' => 'integer', 'deleted_at' => 'immutable_datetime'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void { static::creating(fn ($model) => $model->id ??= (string) Str::uuid()); }
    public function category() { return $this->belongsTo(Category::class, 'category_id'); }
}
