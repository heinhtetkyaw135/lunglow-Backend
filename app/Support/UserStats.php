<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Per-user progress figures derived from smoke logs + settings.
 * Expects `smokeLogs` and `settings` to be eager-loaded to avoid N+1 queries.
 */
class UserStats
{
    public static function dailyLimit(User $u): int
    {
        return (int) ($u->settings?->daily_limit ?? 20);
    }

    /** Consecutive smoke-free (count = 0) days ending today. */
    public static function cleanStreak(User $u): int
    {
        $clean = $u->smokeLogs
            ->where('count', 0)
            ->map(fn($l) => $l->date->format('Y-m-d'))
            ->flip();

        $streak = 0;
        $day = now()->startOfDay();
        while ($clean->has($day->format('Y-m-d'))) {
            $streak++;
            $day->subDay();
        }
        return $streak;
    }

    /** Cigarettes avoided vs. the user's daily limit across all logged days. */
    public static function avoided(User $u): int
    {
        $limit = self::dailyLimit($u);
        return (int) $u->smokeLogs->sum(fn($l) => max(0, $limit - $l->count));
    }

    public static function savedMoney(User $u): float
    {
        return round(self::avoided($u) * (float) ($u->settings?->cig_price ?? 0), 2);
    }

    /**
     * Engagement bucket for the admin donut chart:
     * clean (on a streak) | reducing (under limit last 7d) | struggling (over limit) | inactive (no logs in 7d)
     */
    public static function segment(User $u): string
    {
        if (self::cleanStreak($u) > 0) {
            return 'clean';
        }
        $since = now()->subDays(6)->startOfDay();
        /** @var Collection $recent */
        $recent = $u->smokeLogs->filter(fn($l) => $l->date->gte($since));
        if ($recent->isEmpty()) {
            return 'inactive';
        }
        return $recent->avg('count') <= self::dailyLimit($u) ? 'reducing' : 'struggling';
    }
}
