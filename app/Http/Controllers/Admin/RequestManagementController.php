<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserRequest;
use Illuminate\Http\Request;

class RequestManagementController extends Controller
{
    public function index()
    {
        $requests = UserRequest::with('user:id,name,avatar')->latest()->get()
            ->map(fn($r) => [
                'id' => 'req-' . $r->id,
                'user' => $r->user?->name,
                'userId' => 'u-' . $r->user_id,
                'avatar' => $r->user?->avatar_url,
                'category' => $r->category,
                'subject' => $r->subject,
                'message' => $r->message,
                'status' => $r->status,
                'createdAt' => $r->created_at?->format('M j, Y'),
            ]);

        return response()->json(['requests' => $requests]);
    }

    public function updateStatus(Request $request, UserRequest $userRequest)
    {
        $data = $request->validate(['status' => 'required|in:pending,in-progress,resolved']);
        $userRequest->update(['status' => $data['status']]);
        return response()->json(['request' => ['id' => 'req-' . $userRequest->id, 'status' => $userRequest->status]]);
    }

    public function destroy(UserRequest $userRequest)
    {
        $userRequest->delete();
        return response()->json(['message' => 'Request removed']);
    }
}
