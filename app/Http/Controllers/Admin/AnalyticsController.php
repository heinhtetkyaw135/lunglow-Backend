<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Challenge;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserRequest;
use App\Support\UserStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Live admin dashboard metrics, computed from the database.
 * "Users" here means members (role != admin).
 */
class AnalyticsController extends Controller
{
    private const WEEKS = 12;
    private const DAYS = 14;

    public function overview(?Request $request = null)
    {
        $request ??= request();
        $startParam = $request->query('start_date');
        $endParam = $request->query('end_date');

        $startDate = null;
        $endDate = null;
        if ($startParam) {
            try {
                $startDate = Carbon::parse($startParam)->startOfDay();
            } catch (\Throwable) {
                $startDate = null;
            }
        }
        if ($endParam) {
            try {
                $endDate = Carbon::parse($endParam)->endOfDay();
            } catch (\Throwable) {
                $endDate = null;
            }
        }
        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $hasRange = $startDate !== null || $endDate !== null;
        $rangeStart = $startDate ?? Carbon::create(2020, 1, 1)->startOfDay();
        $rangeEnd = $endDate ?? now()->endOfDay();

        $allMembers = User::where('role', '!=', 'admin')->with(['settings', 'smokeLogs'])->get();
        $members = $hasRange
            ? $allMembers->filter(fn($u) => $u->created_at && $u->created_at->between($rangeStart, $rangeEnd))->values()
            : $allMembers;
        $memberIds = $allMembers->pluck('id');

        if ($hasRange) {
            $daysDiff = max(1, (int) $rangeStart->copy()->startOfDay()->diffInDays($rangeEnd->copy()->startOfDay()) + 1);
            if ($daysDiff <= 31) {
                $dayKeys = collect(range(0, $daysDiff - 1))
                    ->map(fn($i) => $rangeStart->copy()->addDays($i)->format('Y-m-d'));
                $signupsSeries = $dayKeys->map(fn($d) => $allMembers
                    ->filter(fn($u) => $u->created_at && $u->created_at->format('Y-m-d') === $d)
                    ->count())->values();
                $signupsLabels = $dayKeys->values();
            } else {
                $bucketCount = min(self::WEEKS, max(2, (int) ceil($daysDiff / 7)));
                $stepDays = max(1, (int) ceil($daysDiff / $bucketCount));
                $signupsSeries = collect();
                $signupsLabels = collect();
                $cursor = $rangeStart->copy()->startOfDay();
                while ($cursor->lte($rangeEnd)) {
                    $bStart = $cursor->copy();
                    $bEnd = $cursor->copy()->addDays($stepDays - 1)->endOfDay();
                    if ($bEnd->gt($rangeEnd)) {
                        $bEnd = $rangeEnd->copy();
                    }
                    $signupsSeries->push(
                        $allMembers->filter(fn($u) => $u->created_at && $u->created_at->between($bStart, $bEnd))->count()
                    );
                    $signupsLabels->push($bStart->format('M j'));
                    $cursor->addDays($stepDays);
                }
            }
            $signupsWeekly = $signupsSeries->values();
        } else {
            $weekStarts = collect(range(self::WEEKS - 1, 0))
                ->map(fn($i) => now()->startOfWeek()->subWeeks($i));
            $dayKeys = collect(range(self::DAYS - 1, 0))
                ->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

            $signupsWeekly = $weekStarts->map(fn(Carbon $start) => $allMembers
                ->filter(fn($u) => $u->created_at && $u->created_at->between($start, $start->copy()->endOfWeek()))
                ->count())->values();
            $signupsLabels = $weekStarts->map(fn(Carbon $start) => $start->format('M j'))->values();
        }

        // ---- Daily active members (logged smoking, check-in, craving, post or comment)
        $activityStart = $hasRange ? $rangeStart->copy()->startOfDay() : now()->subDays(self::DAYS - 1)->startOfDay();
        $activityEnd = $hasRange ? $rangeEnd->copy()->endOfDay() : now()->endOfDay();
        $activity = $this->memberActivity($memberIds->all(), $activityStart, $activityEnd);

        if ($hasRange) {
            $activeInPeriod = collect($activity)->flatten()->unique()->count();
            $activeLast7 = $activeInPeriod;
            $dailyActive = collect($dayKeys ?? [])->map(fn($d) => count($activity[$d] ?? []))->values();
        } else {
            $dailyActive = $dayKeys->map(fn($d) => count($activity[$d] ?? []))->values();
            $activeLast7 = collect($activity)
                ->filter(fn($_, $d) => $d >= now()->subDays(6)->format('Y-m-d'))
                ->flatten()->unique()->count();
            $activeInPeriod = $allMembers->where('status', 'active')->count();
        }

        // ---- Weekly engagement events -----------------------------------------
        $weekStarts = collect(range(self::WEEKS - 1, 0))
            ->map(fn($i) => now()->startOfWeek()->subWeeks($i));
        $since = $weekStarts->first();
        $events = collect()
            ->merge(DB::table('smoke_logs')->whereIn('user_id', $memberIds)->where('updated_at', '>=', $since)->pluck('updated_at'))
            ->merge(DB::table('daily_checkins')->whereIn('user_id', $memberIds)->where('created_at', '>=', $since)->pluck('created_at'))
            ->merge(DB::table('craving_logs')->whereIn('user_id', $memberIds)->where('created_at', '>=', $since)->pluck('created_at'))
            ->merge(Post::whereIn('user_id', $memberIds)->where('created_at', '>=', $since)->pluck('created_at'))
            ->merge(Blog::whereIn('user_id', $memberIds)->where('created_at', '>=', $since)->pluck('created_at'))
            ->merge(Comment::whereIn('user_id', $memberIds)->where('created_at', '>=', $since)->pluck('created_at'))
            ->map(fn($t) => Carbon::parse($t));
        $weeklyEngagement = $weekStarts->map(fn(Carbon $start) => $events
            ->filter(fn($t) => $t->between($start, $start->copy()->endOfWeek()))
            ->count())->values();

        // ---- Per-member progress ---------------------------------------------
        $segments = ['clean' => 0, 'reducing' => 0, 'struggling' => 0, 'inactive' => 0];
        $streaks = [];
        $saved = 0.0;
        $avoided = 0;
        foreach ($members as $u) {
            if ($hasRange) {
                $logsInRange = $u->smokeLogs->filter(
                    fn($l) => $l->date->between($rangeStart->copy()->startOfDay(), $rangeEnd->copy()->endOfDay())
                );
                $limit = UserStats::dailyLimit($u);
                $uAvoided = (int) $logsInRange->sum(fn($l) => max(0, $limit - $l->count));
                $uSaved = round($uAvoided * (float) ($u->settings?->cig_price ?? 0), 2);
                $avoided += $uAvoided;
                $saved += $uSaved;
                $streaks[] = UserStats::cleanStreak($u);
                if ($logsInRange->isEmpty()) {
                    $segments['inactive']++;
                } elseif ($logsInRange->where('count', 0)->isNotEmpty() && $logsInRange->avg('count') == 0) {
                    $segments['clean']++;
                } elseif ($logsInRange->avg('count') <= $limit) {
                    $segments['reducing']++;
                } else {
                    $segments['struggling']++;
                }
            } else {
                $segments[UserStats::segment($u)]++;
                $streaks[] = UserStats::cleanStreak($u);
                $saved += UserStats::savedMoney($u);
                $avoided += UserStats::avoided($u);
            }
        }

        $postQuery = Post::query();
        $blogQuery = Blog::query();
        $commentQuery = Comment::query();
        $reportQuery = Report::query();
        $pendingReportQuery = Report::whereIn('status', ['pending', 'open']);
        $openRequestQuery = UserRequest::where('status', '!=', 'resolved');

        if ($hasRange) {
            $postQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
            $blogQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
            $commentQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
            $reportQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
            $pendingReportQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
            $openRequestQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }

        $totalPosts = $postQuery->count();
        $totalBlogs = $blogQuery->count();
        $totalComments = $commentQuery->count();
        $totalReports = $reportQuery->count();
        $pendingReports = $pendingReportQuery->count();
        $openRequests = $openRequestQuery->count();
        $contentTotal = $totalPosts + $totalBlogs + $totalComments;

        $newThisWeek = $hasRange
            ? $members->count()
            : $allMembers->filter(fn($u) => $u->created_at?->gte(now()->startOfWeek()))->count();
        $newLastWeek = $hasRange ? 0 : ($signupsWeekly[self::WEEKS - 2] ?? 0);

        return response()->json([
            'filtered' => $hasRange,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),

            // KPI totals
            'total_users' => $members->count(),
            'active_users' => $hasRange ? $members->where('status', 'active')->count() : $allMembers->where('status', 'active')->count(),
            'active_in_period' => $activeInPeriod,
            'suspended_users' => $members->where('status', 'suspended')->count(),
            'new_users_this_week' => $newThisWeek,
            'new_users_last_week' => $newLastWeek,
            'active_last_7_days' => $activeLast7,
            'pending_reports' => $pendingReports,
            'total_reports' => $totalReports,
            'total_posts' => $totalPosts,
            'total_blogs' => $totalBlogs,
            'total_comments' => $totalComments,
            'live_challenges' => Challenge::where('active', true)->count(),
            'challenge_participants' => DB::table('challenge_user')->whereIn('user_id', $members->pluck('id'))->count(),
            'open_requests' => $openRequests,

            // Health
            'total_saved_money' => round($saved, 2),
            'total_avoided_cigs' => $avoided,
            'avg_clean_streak' => $streaks ? round(array_sum($streaks) / count($streaks), 1) : 0,
            'retention_rate' => $members->count() ? round($activeLast7 / $members->count() * 100) : 0,
            'report_rate' => $contentTotal ? round($totalReports / $contentTotal * 100, 1) : 0,

            // Trends
            'segments' => $segments,
            'signups_weekly' => $signupsWeekly,
            'signups_labels' => $signupsLabels,
            'daily_active' => $dailyActive,
            'daily_active_labels' => isset($dayKeys) ? $dayKeys->values() : [],
            'weekly_engagement' => $weeklyEngagement,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** @return array<string, int[]> date => distinct member ids active that day */
    private function memberActivity(array $memberIds, Carbon $since, ?Carbon $until = null): array
    {
        if (!$memberIds) {
            return [];
        }
        $until ??= now()->endOfDay();
        $rows = collect()
            ->merge(DB::table('smoke_logs')->whereIn('user_id', $memberIds)
                ->whereBetween('date', [$since->toDateString(), $until->toDateString()])
                ->get(['user_id', DB::raw('DATE(date) as d')]))
            ->merge(DB::table('daily_checkins')->whereIn('user_id', $memberIds)
                ->whereBetween('date', [$since->toDateString(), $until->toDateString()])
                ->get(['user_id', DB::raw('DATE(date) as d')]));
        foreach (['craving_logs', 'posts', 'blogs', 'comments'] as $table) {
            $rows = $rows->merge(DB::table($table)->whereIn('user_id', $memberIds)
                ->whereBetween('created_at', [$since, $until])
                ->get(['user_id', DB::raw('DATE(created_at) as d')]));
        }

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r->d][$r->user_id] = $r->user_id;
        }
        return array_map('array_values', $byDay);
    }
}
