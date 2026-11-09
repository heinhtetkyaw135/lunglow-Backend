<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    protected $table = 'user_requests';

    protected $fillable = ['user_id', 'category', 'subject', 'message', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
