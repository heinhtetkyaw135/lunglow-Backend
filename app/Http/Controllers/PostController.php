<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $viewerId = $request->user()?->id;

        $posts = Post::with(['user', 'comments.user', 'reactions'])
            ->latest()
            ->get()
            ->map(fn($p) => $this->serialize($p, $viewerId));

        return response()->json(['posts' => $posts]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'post_type' => 'required|string|in:tip,progress,milestone',
            'tip_category' => 'nullable|string|max:80',
            'title' => 'nullable|string|max:200',
            'content' => 'nullable|string',
            'badge_key' => 'nullable|string|max:60',
            'badge_custom' => 'nullable|string|max:120',
            'stats' => 'nullable',
            'image' => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        // stats may arrive as JSON string via FormData.
        $stats = $request->input('stats');
        if (is_string($stats)) {
            $stats = json_decode($stats, true);
        }

        $post = $request->user()->posts()->create([
            'post_type' => $data['post_type'],
            'tip_category' => $data['tip_category'] ?? null,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'badge_key' => $data['badge_key'] ?? null,
            'badge_custom' => $data['badge_custom'] ?? null,
            'stats' => $stats,
            'image' => $imagePath,
            'reaction_counts' => ['fire' => 0, 'clap' => 0, 'heart' => 0],
        ]);

        $post->load(['user', 'comments.user', 'reactions']);

        return response()->json(['post' => $this->serialize($post, $request->user()->id)], 201);
    }

    public function destroy(Request $request, Post $post)
    {
        $viewer = $request->user();
        if ($post->user_id !== $viewer->id && !$viewer->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $post->delete();
        return response()->json(['ok' => true]);
    }

    // Toggle the viewer's reaction of a given type; returns fresh counts.
    public function react(Request $request, Post $post)
    {
        $data = $request->validate(['type' => 'required|string|in:fire,clap,heart']);
        $viewer = $request->user();

        $existing = $post->reactions()
            ->where('user_id', $viewer->id)
            ->where('type', $data['type'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $post->reactions()->create(['user_id' => $viewer->id, 'type' => $data['type']]);
        }

        $post->load(['user', 'comments.user', 'reactions']);
        return response()->json(['post' => $this->serialize($post, $viewer->id)]);
    }

    private function serialize(Post $p, ?int $viewerId): array
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
}
