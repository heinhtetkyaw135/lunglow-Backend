<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'user_id', 'post_type', 'tip_category', 'title',
        'content', 'image', 'badge_key', 'badge_custom', 'stats', 'reaction_counts',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'reaction_counts' => 'array',
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

    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return str_starts_with($this->image, 'http') || str_starts_with($this->image, '/')
            ? $this->image
            : '/storage/' . ltrim($this->image, '/');
    }
}
