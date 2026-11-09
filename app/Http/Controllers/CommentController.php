<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, string $type, string $id)
    {
        $data = $request->validate(['text' => 'required|string|max:2000']);

        // Frontend sends prefixed ids ("post-3", "blog-1"); strip to numeric.
        $numericId = (int) preg_replace('/\D+/', '', (string) $id);
        $model = $type === 'post'
            ? Post::findOrFail($numericId)
            : Blog::findOrFail($numericId);

        $comment = $model->comments()->create([
            'user_id' => $request->user()->id,
            'text' => $data['text'],
        ]);
        $comment->load('user');

        $prefix = $type === 'post' ? 'pcom-' : 'bcom-';
        return response()->json(['comment' => [
            'id' => $prefix . $comment->id,
            'author' => $comment->user->name,
            'authorAvatar' => $comment->user->avatar_url,
            'text' => $comment->text,
            'createdAt' => $comment->created_at->diffForHumans(),
        ]], 201);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $viewer = $request->user();
        if ($comment->user_id !== $viewer->id && !$viewer->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
