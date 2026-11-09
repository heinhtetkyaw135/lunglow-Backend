<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'daily_limit', 'cig_price'];

    protected function casts(): array
    {
        return [
            'daily_limit' => 'integer',
            'cig_price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
