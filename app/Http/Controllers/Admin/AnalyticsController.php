<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Challenge;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserRequest;

class AnalyticsController extends Controller
{
    public function overview()
    {
        return response()->json([
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'total_posts' => Post::count(),
            'total_blogs' => Blog::count(),
            'live_challenges' => Challenge::where('active', true)->count(),
            'open_requests' => UserRequest::where('status', '!=', 'resolved')->count(),
        ]);
    }
}
