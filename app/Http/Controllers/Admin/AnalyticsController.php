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

    public function overview()
    {
        $members = User::where('role', '!=', 'admin')->with(['settings', 'smokeLogs'])->get();
        $memberIds = $members->pluck('id');

        $weekStarts = collect(range(self::WEEKS - 1, 0))
            ->map(fn($i) => now()->startOfWeek()->subWeeks($i));
        $dayKeys = collect(range(self::DAYS - 1, 0))
            ->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

        // ---- Signups per week ------------------------------------------------
        $signupsWeekly = $weekStarts->map(fn(Carbon $start) => $members
            ->filter(fn($u) => $u->created_at && $u->created_at->between($start, $start->copy()->endOfWeek()))
            ->count())->values();

        // ---- Daily active members (logged smoking, check-in, craving, post or comment)
        $activity = $this->memberActivity($memberIds->all(), now()->subDays(self::DAYS - 1)->startOfDay());
        $dailyActive = $dayKeys->map(fn($d) => count($activity[$d] ?? []))->values();
        $activeLast7 = collect($activity)
            ->filter(fn($_, $d) => $d >= now()->subDays(6)->format('Y-m-d'))
            ->flatten()->unique()->count();

        // ---- Weekly engagement events -----------------------------------------
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
            $segments[UserStats::segment($u)]++;
            $streaks[] = UserStats::cleanStreak($u);
            $saved += UserStats::savedMoney($u);
            $avoided += UserStats::avoided($u);
        }

        $totalPosts = Post::count();
        $totalBlogs = Blog::count();
        $totalComments = Comment::count();
        $totalReports = Report::count();
        $contentTotal = $totalPosts + $totalBlogs + $totalComments;

        $newThisWeek = $members->filter(fn($u) => $u->created_at?->gte(now()->startOfWeek()))->count();
        $newLastWeek = $signupsWeekly[self::WEEKS - 2] ?? 0;

        return response()->json([
            // KPI totals
            'total_users' => $members->count(),
            'active_users' => $members->where('status', 'active')->count(),
            'suspended_users' => $members->where('status', 'suspended')->count(),
            'new_users_this_week' => $newThisWeek,
            'new_users_last_week' => $newLastWeek,
            'active_last_7_days' => $activeLast7,
            'pending_reports' => Report::whereIn('status', ['pending', 'open'])->count(),
            'total_reports' => $totalReports,
            'total_posts' => $totalPosts,
            'total_blogs' => $totalBlogs,
            'total_comments' => $totalComments,
            'live_challenges' => Challenge::where('active', true)->count(),
            'challenge_participants' => DB::table('challenge_user')->whereIn('user_id', $memberIds)->count(),
            'open_requests' => UserRequest::where('status', '!=', 'resolved')->count(),

            // Health
            'total_saved_money' => round($saved, 2),
            'total_avoided_cigs' => $avoided,
            'avg_clean_streak' => $streaks ? round(array_sum($streaks) / count($streaks), 1) : 0,
            'retention_rate' => $members->count() ? round($activeLast7 / $members->count() * 100) : 0,
            'report_rate' => $contentTotal ? round($totalReports / $contentTotal * 100, 1) : 0,

            // Trends
            'segments' => $segments,
            'signups_weekly' => $signupsWeekly,
            'daily_active' => $dailyActive,
            'daily_active_labels' => $dayKeys->values(),
            'weekly_engagement' => $weeklyEngagement,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** @return array<string, int[]> date => distinct member ids active that day */
    private function memberActivity(array $memberIds, Carbon $since): array
    {
        if (!$memberIds) {
            return [];
        }
        $rows = collect()
            ->merge(DB::table('smoke_logs')->whereIn('user_id', $memberIds)->where('date', '>=', $since->toDateString())
                ->get(['user_id', DB::raw('DATE(date) as d')]))
            ->merge(DB::table('daily_checkins')->whereIn('user_id', $memberIds)->where('date', '>=', $since->toDateString())
                ->get(['user_id', DB::raw('DATE(date) as d')]));
        foreach (['craving_logs', 'posts', 'blogs', 'comments'] as $table) {
            $rows = $rows->merge(DB::table($table)->whereIn('user_id', $memberIds)->where('created_at', '>=', $since)
                ->get(['user_id', DB::raw('DATE(created_at) as d')]));
        }

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r->d][$r->user_id] = $r->user_id;
        }
        return array_map('array_values', $byDay);
    }
}
