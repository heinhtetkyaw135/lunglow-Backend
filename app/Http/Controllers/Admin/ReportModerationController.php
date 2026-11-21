<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ReportModerationController extends Controller
{
    public function index()
    {
        $reports = Report::with('reporter')->where('status', 'pending')->latest()->get()->map(fn($r) => [
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
            'createdDate' => $r->created_at?->format('Y-m-d'),
        ]);

        return response()->json(['reports' => $reports]);
    }

    // action = 'delete' → remove reported content; 'dismiss' → keep it.
    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate(['action' => 'required|in:delete,dismiss']);

        if ($data['action'] === 'delete') {
            $this->deleteTarget($report);
            // Also resolve any other pending reports against the same target
            if ($report->target_id) {
                Report::where('target_type', $report->target_type)
                    ->where('target_id', $report->target_id)
                    ->where('status', 'pending')
                    ->update(['status' => 'resolved']);
            }
            $report->status = 'resolved';
        } else {
            $report->status = 'dismissed';
        }
        $report->save();

        return response()->json(['report' => ['id' => 'rep-' . $report->id, 'status' => $report->status]]);
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return response()->json(['message' => 'Report removed']);
    }

    private function deleteTarget(Report $report): void
    {
        // Frontend ids are prefixed ("post-3", "bcom-1"); strip to numeric.
        $id = (int) preg_replace('/\D+/', '', (string) $report->target_id);

        if ($report->target_type === 'post') {
            if ($post = Post::find($id)) {
                $post->comments()->delete();
                $post->delete();
            }
        } elseif ($report->target_type === 'blog') {
            if ($blog = Blog::find($id)) {
                $blog->comments()->delete();
                $blog->delete();
            }
        } elseif ($report->target_type === 'comment') {
            Comment::where('id', $id)->delete();
        } elseif ($report->target_type === 'profile-image') {
            if ($id > 0 && User::where('id', $id)->exists()) {
                User::where('id', $id)->update(['avatar' => null]);
            } elseif ($report->target_author) {
                $authorName = preg_replace('/\s*\(Admin\)$/i', '', $report->target_author);
                User::where('name', $authorName)->update(['avatar' => null]);
            }
        }
    }
}
