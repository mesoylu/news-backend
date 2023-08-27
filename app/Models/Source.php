<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $fillable = [
        'name',
        'source_id',
        'api_name'
    ];

    const CREATED_AT = null;

    const UPDATED_AT = null;

    public function articles()
    {
        return $this->belongsTo(Article::class);
    }
}
