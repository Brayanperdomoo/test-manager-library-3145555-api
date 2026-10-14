<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Category extends Model
{
    protected $table = 'library.category';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
}
