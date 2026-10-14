<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Member extends Model
{
    protected $table = 'library.member';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void { static::creating(fn ($model) => $model->id ??= (string) Str::uuid()); }
}
