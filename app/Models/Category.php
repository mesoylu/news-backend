<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    const CREATED_AT = null;

    const UPDATED_AT = null;

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
