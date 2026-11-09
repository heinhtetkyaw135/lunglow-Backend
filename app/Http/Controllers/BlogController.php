<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $viewerId = $request->user()?->id;

        $blogs = Blog::with(['user', 'comments.user', 'likedBy'])
            ->latest()
            ->get()
            ->map(fn($b) => $this->serialize($b, $viewerId));

        return response()->json(['blogs' => $blogs]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'nullable|string|max:80',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
            'image' => 'nullable|image|max:5120',
        ]);

        $paths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $paths[] = $img->store('blogs', 'public');
            }
        } elseif ($request->hasFile('image')) {
            $paths[] = $request->file('image')->store('blogs', 'public');
        }

        $user = $request->user();
        $blog = $user->blogs()->create([
            'title' => $data['title'],
            'category' => $data['category'] ?? 'Tips & Tricks',
            'author_role' => $user->isAdmin() ? 'admin' : 'member',
            'images' => $paths,
            'content' => $data['content'],
            'likes' => 0,
        ]);

        $blog->load(['user', 'comments.user', 'likedBy']);

        return response()->json(['blog' => $this->serialize($blog, $user->id)], 201);
    }

    public function destroy(Request $request, Blog $blog)
    {
        $viewer = $request->user();
        if ($blog->user_id !== $viewer->id && !$viewer->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        foreach ((array) $blog->images as $img) {
            if ($img) Storage::disk('public')->delete($img);
        }
        $blog->delete();
        return response()->json(['ok' => true]);
    }

    public function toggleLike(Request $request, Blog $blog)
    {
        $viewer = $request->user();
        $like = $blog->likedBy()->where('user_id', $viewer->id)->first();

        if ($like) {
            $like->delete();
            $blog->decrement('likes');
        } else {
            $blog->likedBy()->create(['user_id' => $viewer->id]);
            $blog->increment('likes');
        }

        $blog->load(['user', 'comments.user', 'likedBy']);
        return response()->json(['blog' => $this->serialize($blog->fresh(), $viewer->id)]);
    }

    private function serialize(Blog $b, ?int $viewerId): array
    {
        $images = collect((array) $b->images)->map(fn($i) => $this->url($i))->filter()->values();
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

    private function url(?string $path): ?string
    {
        if (!$path) return null;
        return str_starts_with($path, 'http') || str_starts_with($path, '/')
            ? $path
            : '/storage/' . ltrim($path, '/');
    }
}
