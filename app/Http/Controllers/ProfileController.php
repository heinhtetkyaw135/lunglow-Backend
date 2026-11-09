<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'bio' => 'sometimes|nullable|string|max:500',
        ]);

        $user = $request->user();
        $user->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['user' => $user->toAuthArray()]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:5120']);

        $path = $request->file('avatar')->store('avatars', 'public');
        $request->user()->update(['avatar' => $path]);

        return response()->json([
            'user' => $request->user()->toAuthArray(),
            'avatar' => $request->user()->avatar_url,
        ]);
    }

    public function resist(Request $request)
    {
        $request->user()->increment('resisted_count');
        return response()->json(['resisted_count' => $request->user()->resisted_count]);
    }
}
