<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequestController extends Controller
{
    // Member inbox — only their own requests.
    public function index(Request $request)
    {
        $requests = $request->user()->requests()->latest()->get()
            ->map(fn($r) => [
                'id' => 'req-' . $r->id,
                'user' => $r->user->name,
                'userId' => 'u-' . $r->user_id,
                'avatar' => $r->user->avatar_url,
                'category' => $r->category,
                'subject' => $r->subject,
                'message' => $r->message,
                'status' => $r->status,
                'createdAt' => $r->created_at->diffForHumans(),
            ]);

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:80',
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
        ]);

        $req = $request->user()->requests()->create($data + ['status' => 'pending']);

        return response()->json(['request' => ['id' => 'req-' . $req->id]], 201);
    }
}
