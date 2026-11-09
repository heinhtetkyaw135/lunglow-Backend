<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeakCravingAlert extends Model
{
    protected $fillable = [
        'user_id', 'day_index', 'slot_index', 'active', 'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
            'slot_index' => 'integer',
            'active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start hour of a slot: slot 0 = 6AM, each block is 3 hours.
     */
    public static function slotStartHour(int $slotIndex): int
    {
        return 6 + 3 * $slotIndex;
    }

    /**
     * Start of the most recent occurrence of this weekly window
     * (may be today if the window already began, else last week).
     */
    public function currentWindowStart(): \Carbon\CarbonInterface
    {
        $now = now();
        $start = $now->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)
            ->addDays($this->day_index)
            ->setTime(self::slotStartHour($this->slot_index), 0, 0);

        if ($start->gt($now)) {
            $start->subWeek(); // this week's window hasn't started yet
        }

        return $start;
    }
}
