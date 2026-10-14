<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class LibraryUser extends Model
{
    protected $table = 'library.user';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password_hash'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted(): void { static::creating(fn ($model) => $model->id ??= (string) Str::uuid()); }
}
