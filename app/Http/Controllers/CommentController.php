<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\UserNotification;
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

        // Notify the content author (skip self-comments).
        $authorId = $model->user_id;
        if ($authorId && $authorId !== $request->user()->id) {
            $contentLabel = $type === 'post' ? 'post' : 'blog';
            $contentTitle = $type === 'post'
                ? ($model->title ?: mb_strimwidth((string) $model->content, 0, 60, '…'))
                : ($model->title ?: 'your blog');
            UserNotification::notify(
                $authorId,
                'comment',
                'New Comment',
                sprintf(
                    '%s commented on your %s "%s": "%s"',
                    $request->user()->name,
                    $contentLabel,
                    $contentTitle,
                    mb_strimwidth($data['text'], 0, 120, '…')
                ),
                [
                    'type' => $type,
                    'content_id' => $numericId,
                    'comment_id' => $comment->id,
                ]
            );
        }

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
        \App\Models\Report::where('target_type', 'comment')
            ->whereIn('target_id', ['pcom-' . $comment->id, 'bcom-' . $comment->id, (string) $comment->id])
            ->where('status', 'pending')
            ->update(['status' => 'resolved']);
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
