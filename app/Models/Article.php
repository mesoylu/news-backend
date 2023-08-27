<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'source_id',
        'title',
        'description',
        'article_url',
        'image_url',
        'published_at',
        'content',
    ];

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
