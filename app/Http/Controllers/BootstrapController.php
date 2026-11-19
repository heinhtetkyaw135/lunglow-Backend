<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Models\Blog;
use App\Models\Broadcast;
use App\Models\Challenge;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserRequest;
use App\Support\UserStats;
use Illuminate\Http\Request;

/**
 * Single-request initial hydration for both the user site and the admin console.
 *
 * Replaces 11–12 concurrent GET calls on page load with 1 HTTP round-trip,
 * eliminating PHP built-in single-threaded server head-of-line blocking.
 */
class BootstrapController extends Controller
{
    use ResolvesViewer;

    public function index(Request $request)
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if ($user && $user->status === 'suspended') {
            $user = null;
        }
        $viewerId = $user?->id;

        // ---- Public feeds ---------------------------------------------------
        $posts = Post::with(['user', 'comments.user', 'reactions'])
            ->latest()
            ->get()
            ->map(fn(Post $p) => $this->serializePost($p, $viewerId))
            ->values();

        $blogs = Blog::with(['user', 'comments.user', 'likedBy'])
            ->latest()
            ->get()
            ->map(fn(Blog $b) => $this->serializeBlog($b, $viewerId))
            ->values();

        $challenges = Challenge::withCount('participants')
            ->with($viewerId ? ['participants' => fn($q) => $q->where('users.id', $viewerId)] : [])
            ->latest()
            ->get()
            ->map(fn(Challenge $c) => $this->serializeChallenge($c, $viewerId))
            ->values();

        $latestBroadcast = Broadcast::latest()->first();
        $broadcast = $latestBroadcast ? [
            'message' => $latestBroadcast->message,
            'publishedAt' => $latestBroadcast->created_at->diffForHumans(),
        ] : null;

        $payload = [
            'user' => $user ? $user->toAuthArray() : null,
            'posts' => $posts,
            'blogs' => $blogs,
            'challenges' => $challenges,
            'broadcast' => $broadcast,
        ];

        if (!$user) {
            return response()->json($payload);
        }

        // ---- Authenticated member data --------------------------------------
        $settings = $user->settings()->firstOrCreate(
            [],
            ['daily_limit' => 20, 'cig_price' => 0.50]
        );

        $logsByDate = $user->smokeLogs()
            ->get()
            ->mapWithKeys(fn($log) => [$log->date->format('Y-m-d') => $log->count])
            ->all();

        $goals = $user->goals()->latest()->get()
            ->map(fn($g) => ['id' => $g->id, 'title' => $g->title, 'price' => (float) $g->price])
            ->values();

        $reminders = $user->reminders()->get()
            ->map(fn($r) => ['id' => $r->key, 'time' => $r->time, 'enabled' => (bool) $r->enabled])
            ->values();

        $checkins = $user->checkins()->latest('date')->get()
            ->map(fn($c) => ['date' => $c->date->format('Y-m-d'), 'mood' => $c->mood])
            ->values();

        $heatmap = [];
        foreach ($user->cravingLogs()->orderBy('occurred_at')->orderBy('id')->get(['day_index', 'slot_index', 'intensity']) as $row) {
            if ($row->slot_index >= 0 && $row->slot_index <= 5) {
                $heatmap["{$row->day_index}-{$row->slot_index}"] = (int) $row->intensity;
            }
        }

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn(UserNotification $n) => [
                'id' => 'n-' . $n->id,
                'kind' => $n->kind,
                'title' => $n->title,
                'body' => $n->body,
                'meta' => $n->meta,
                'windowStartAt' => $n->window_start_at?->toIso8601String(),
                'createdAt' => $n->created_at?->diffForHumans(),
                'read' => $n->read_at !== null,
            ])
            ->values();

        $payload += [
            'settings' => [
                'daily_limit' => (int) $settings->daily_limit,
                'cig_price' => (float) $settings->cig_price,
            ],
            'logsByDate' => $logsByDate,
            'goals' => $goals,
            'reminders' => $reminders,
            'checkins' => $checkins,
            'heatmap' => $heatmap,
            'notifications' => $notifications,
        ];

        if ($user->isAdmin()) {
            $payload['users'] = User::with(['settings', 'smokeLogs'])->latest()->get()->map(fn($u) => [
                'id' => 'u-' . $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'status' => $u->status,
                'streak' => UserStats::cleanStreak($u),
                'savedMoney' => UserStats::savedMoney($u),
                'avoidedCigs' => UserStats::avoided($u),
                'segment' => UserStats::segment($u),
                'avatar' => $u->avatar_url,
                'joinedAt' => $u->created_at?->format('Y-m-d'),
                'bio' => $u->bio,
            ])->values();

            $payload['reports'] = Report::with('reporter')->latest()->get()->map(fn($r) => [
                'id' => 'rep-' . $r->id,
                'targetType' => $r->target_type,
                'targetId' => $r->target_id,
                'parentId' => $r->parent_id,
                'targetAuthor' => $r->target_author,
                'reason' => $r->note ? "{$r->reason} ({$r->note})" : $r->reason,
                'excerpt' => $r->excerpt,
                'reportedBy' => $r->reporter?->name ?? 'Member',
                'status' => $r->status,
                'createdAt' => $r->created_at?->format('M j, Y'),
            ])->values();

            $payload['requests'] = UserRequest::with('user:id,name,avatar')->latest()->get()->map(fn($r) => [
                'id' => 'req-' . $r->id,
                'user' => $r->user?->name,
                'userId' => 'u-' . $r->user_id,
                'avatar' => $r->user?->avatar_url,
                'category' => $r->category,
                'subject' => $r->subject,
                'message' => $r->message,
                'status' => $r->status,
                'createdAt' => $r->created_at?->format('M j, Y'),
            ])->values();

            $payload['analytics'] = app(AnalyticsController::class)->overview()->getData(true);
        } else {
            $payload['requests'] = $user->requests()->latest()->get()->map(fn($r) => [
                'id' => 'req-' . $r->id,
                'user' => $user->name,
                'userId' => 'u-' . $user->id,
                'avatar' => $user->avatar_url,
                'category' => $r->category,
                'subject' => $r->subject,
                'message' => $r->message,
                'status' => $r->status,
                'createdAt' => $r->created_at->diffForHumans(),
            ])->values();
        }

        return response()->json($payload);
    }

    private function serializePost(Post $p, ?int $viewerId): array
    {
        $counts = $p->reaction_counts ?? ['fire' => 0, 'clap' => 0, 'heart' => 0];
        $userReacted = ['fire' => false, 'clap' => false, 'heart' => false];
        $real = ['fire' => 0, 'clap' => 0, 'heart' => 0];

        foreach ($p->reactions as $r) {
            $real[$r->type] = ($real[$r->type] ?? 0) + 1;
            if ($viewerId && $r->user_id === $viewerId) {
                $userReacted[$r->type] = true;
            }
        }

        return [
            'id' => 'post-' . $p->id,
            'postType' => $p->post_type,
            'author' => $p->user->name,
            'authorId' => 'u-' . $p->user_id,
            'authorAvatar' => $p->user->avatar_url,
            'isYou' => $viewerId !== null && $p->user_id === $viewerId,
            'tipCategory' => $p->tip_category,
            'title' => $p->title,
            'content' => $p->content,
            'image' => $p->image_url,
            'badgeKey' => $p->badge_key,
            'badgeCustom' => $p->badge_custom,
            'stats' => $p->stats ?? [],
            'reactions' => [
                'fire' => ($counts['fire'] ?? 0) + $real['fire'],
                'clap' => ($counts['clap'] ?? 0) + $real['clap'],
                'heart' => ($counts['heart'] ?? 0) + $real['heart'],
            ],
            'userReacted' => $userReacted,
            'comments' => $p->comments->map(fn($c) => [
                'id' => 'pcom-' . $c->id,
                'author' => $c->user->name,
                'authorAvatar' => $c->user->avatar_url,
                'text' => $c->text,
                'createdAt' => $c->created_at->diffForHumans(),
            ])->values(),
        ];
    }

    private function serializeBlog(Blog $b, ?int $viewerId): array
    {
        $images = collect((array) $b->images)->map(fn($i) => \App\Support\MediaUrl::make($i))->filter()->values();
        $userLiked = $viewerId ? $b->likedBy->contains('user_id', $viewerId) : false;

        return [
            'id' => 'blog-' . $b->id,
            'title' => $b->title,
            'category' => $b->category,
            'author' => $b->user->name . ($b->author_role === 'admin' ? ' (Admin)' : ''),
            'authorRole' => $b->author_role,
            'authorAvatar' => $b->user->avatar_url,
            'createdAt' => $b->created_at->format('M j, Y'),
            'image' => $images->first(),
            'images' => $images,
            'content' => $b->content,
            'likes' => (int) $b->likes,
            'userLiked' => $userLiked,
            'comments' => $b->comments->map(fn($c) => [
                'id' => 'bcom-' . $c->id,
                'author' => $c->user->name,
                'authorAvatar' => $c->user->avatar_url,
                'text' => $c->text,
                'createdAt' => $c->created_at->diffForHumans(),
            ])->values(),
        ];
    }

    private function serializeChallenge(Challenge $c, ?int $viewerId): array
    {
        $pivot = ($viewerId && $c->relationLoaded('participants'))
            ? $c->participants->firstWhere('id', $viewerId)?->pivot
            : null;

        return [
            'id' => 'ch-' . $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'details' => $c->details,
            'reward' => $c->reward,
            'days' => (int) $c->days,
            'participants' => (int) ($c->participants_count ?? 0),
            'active' => (bool) $c->active,
            'joined' => $pivot !== null,
            'progress' => (int) ($pivot?->progress ?? 0),
            'lastLogDate' => $pivot?->last_log_date
                ? (new \DateTime($pivot->last_log_date))->format('D M d Y')
                : null,
            'emoji' => $c->emoji,
            'cover' => $c->cover_url,
        ];
    }
}
