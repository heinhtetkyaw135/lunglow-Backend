<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedRouteBinding;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasPrefixedRouteBinding;

    protected $table = 'user_requests';

    protected $fillable = ['user_id', 'category', 'subject', 'message', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
