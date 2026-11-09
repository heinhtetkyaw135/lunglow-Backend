<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'user_id', 'title', 'category', 'author_role', 'images', 'content', 'likes',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'likes' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function likedBy()
    {
        return $this->hasMany(BlogLike::class);
    }
}
