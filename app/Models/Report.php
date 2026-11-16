<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedRouteBinding;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasPrefixedRouteBinding;

    protected $fillable = [
        'reporter_id', 'target_type', 'target_id', 'parent_id',
        'target_author', 'reason', 'note', 'excerpt', 'status',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
