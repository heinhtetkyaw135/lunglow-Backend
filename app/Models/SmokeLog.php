<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmokeLog extends Model
{
    protected $fillable = ['user_id', 'date', 'count'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
