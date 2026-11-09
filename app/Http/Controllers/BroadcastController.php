<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    // Public — latest broadcast banner shown on every user's dashboard.
    public function show()
    {
        $latest = Broadcast::latest()->first();
        return response()->json(['broadcast' => $latest ? [
            'message' => $latest->message,
            'publishedAt' => $latest->created_at->diffForHumans(),
        ] : null]);
    }

    // Admin — publish a new announcement.
    public function store(Request $request)
    {
        $data = $request->validate(['message' => 'required|string|max:500']);

        $b = Broadcast::create([
            'admin_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return response()->json(['broadcast' => [
            'message' => $b->message,
            'publishedAt' => 'Just now',
        ]], 201);
    }

    // Admin — clear the banner.
    public function destroy()
    {
        Broadcast::truncate();
        return response()->json(['broadcast' => null]);
    }
}
