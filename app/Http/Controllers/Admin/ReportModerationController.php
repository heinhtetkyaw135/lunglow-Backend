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
        $reports = Report::with('reporter')->latest()->get()->map(fn($r) => [
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
        ]);

        return response()->json(['reports' => $reports]);
    }

    // action = 'delete' → remove reported content; 'dismiss' → keep it.
    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate(['action' => 'required|in:delete,dismiss']);

        if ($data['action'] === 'delete') {
            $this->deleteTarget($report);
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

        match ($report->target_type) {
            'post' => Post::where('id', $id)->delete(),
            'blog' => Blog::where('id', $id)->delete(),
            'comment' => Comment::where('id', $id)->delete(),
            'profile-image' => User::where('id', $id)->update(['avatar' => null]),
            default => null,
        };
    }
}
