<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CravingLog extends Model
{
    protected $fillable = ['user_id', 'day_index', 'slot_index', 'intensity', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
            'slot_index' => 'integer',
            'intensity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
