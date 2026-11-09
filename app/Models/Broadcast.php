<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = ['admin_id', 'message'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
