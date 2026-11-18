<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedRouteBinding;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasPrefixedRouteBinding;

    protected $fillable = [
        'user_id', 'kind', 'title', 'body', 'meta', 'window_start_at', 'fetched_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'window_start_at' => 'datetime',
            'fetched_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a notification for a user. Server-side events (goals, reactions,
     * comments, challenges) go through this so they persist and are picked up
     * by both the history endpoint and the 30s delivery poll.
     */
    public static function notify(int $userId, string $kind, string $title, string $body, ?array $meta = null): self
    {
        return static::create([
            'user_id' => $userId,
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
            'meta' => $meta,
            'read_at' => null,
        ]);
    }
}
