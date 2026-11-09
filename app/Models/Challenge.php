<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    protected $fillable = [
        'title', 'description', 'details', 'reward',
        'days', 'active', 'emoji', 'cover',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'challenge_user')
            ->withPivot('progress', 'last_log_date')
            ->withTimestamps();
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover) {
            return null;
        }
        return str_starts_with($this->cover, 'http') || str_starts_with($this->cover, '/')
            ? $this->cover
            : '/storage/' . ltrim($this->cover, '/');
    }
}
