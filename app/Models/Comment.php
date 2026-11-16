<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedRouteBinding;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasPrefixedRouteBinding;

    protected $fillable = ['user_id', 'commentable_type', 'commentable_id', 'text'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}
