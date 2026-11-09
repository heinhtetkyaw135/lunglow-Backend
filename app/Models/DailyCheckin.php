<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCheckin extends Model
{
    protected $fillable = ['user_id', 'date', 'mood'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
