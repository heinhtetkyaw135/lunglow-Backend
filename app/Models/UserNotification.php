<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id', 'kind', 'title', 'body', 'meta', 'window_start_at', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'window_start_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
