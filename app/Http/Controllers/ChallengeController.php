<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChallengeController extends Controller
{
    public function index(Request $request)
    {
        $viewerId = $request->user()?->id;

        $challenges = Challenge::withCount('participants')
            ->with(['participants' => fn($q) => $q->when($viewerId, fn($x) => $x->where('users.id', $viewerId))])
            ->latest()
            ->get()
            ->map(fn($c) => $this->serialize($c, $viewerId));

        return response()->json(['challenges' => $challenges]);
    }

    public function toggleJoin(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $joined = $challenge->participants()->where('users.id', $user->id)->exists();

        if ($joined) {
            $challenge->participants()->detach($user->id);
        } else {
            $challenge->participants()->attach($user->id, ['progress' => 0]);
        }

        $challenge->loadCount('participants')
            ->load(['participants' => fn($q) => $q->where('users.id', $user->id)]);

        return response()->json(['challenge' => $this->serialize($challenge, $user->id)]);
    }

    // Log one clean day per calendar day; locks until tomorrow.
    public function logDay(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $pivot = $challenge->participants()->where('users.id', $user->id)->first();

        if (!$pivot) {
            return response()->json(['message' => 'Join the challenge first.'], 422);
        }

        $today = now()->toDateString();
        if ($pivot->pivot->last_log_date === $today) {
            return response()->json(['message' => 'Already logged today — come back tomorrow!'], 422);
        }

        $progress = min($challenge->days, $pivot->pivot->progress + 1);
        $challenge->participants()->updateExistingPivot($user->id, [
            'progress' => $progress,
            'last_log_date' => $today,
        ]);

        $challenge->loadCount('participants')
            ->load(['participants' => fn($q) => $q->where('users.id', $user->id)]);

        return response()->json([
            'challenge' => $this->serialize($challenge, $user->id),
            'completed' => $progress >= $challenge->days,
        ]);
    }

    // ---- Admin management ---------------------------------------------------

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'reward' => 'nullable|string|max:120',
            'days' => 'required|integer|min:1',
            'emoji' => 'nullable|string|max:8',
            'active' => 'nullable',
            'cover' => 'nullable|image|max:5120',
        ]);

        $cover = $request->hasFile('cover')
            ? $request->file('cover')->store('challenges', 'public')
            : null;

        $challenge = Challenge::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'details' => $data['details'] ?? null,
            'reward' => $data['reward'] ?? 'Exclusive Badge',
            'days' => $data['days'],
            'emoji' => $data['emoji'] ?? '🎯',
            'active' => filter_var($request->input('active', true), FILTER_VALIDATE_BOOLEAN),
            'cover' => $cover,
        ]);

        $challenge->loadCount('participants')->load('participants');
        return response()->json(['challenge' => $this->serialize($challenge, $request->user()->id)], 201);
    }

    public function update(Request $request, Challenge $challenge)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'reward' => 'nullable|string|max:120',
            'days' => 'sometimes|integer|min:1',
            'emoji' => 'nullable|string|max:8',
            'active' => 'nullable',
            'cover' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('cover')) {
            if ($challenge->cover) Storage::disk('public')->delete($challenge->cover);
            $challenge->cover = $request->file('cover')->store('challenges', 'public');
        }
        if ($request->has('active')) {
            $challenge->active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
        }
        foreach (['title', 'description', 'details', 'reward', 'days', 'emoji'] as $f) {
            if ($request->has($f)) $challenge->{$f} = $data[$f] ?? $challenge->{$f};
        }
        $challenge->save();

        $challenge->loadCount('participants')->load('participants');
        return response()->json(['challenge' => $this->serialize($challenge, $request->user()->id)]);
    }

    public function destroy(Request $request, Challenge $challenge)
    {
        if ($challenge->cover) Storage::disk('public')->delete($challenge->cover);
        $challenge->delete();
        return response()->json(['ok' => true]);
    }

    private function serialize(Challenge $c, ?int $viewerId): array
    {
        $pivot = $viewerId ? $c->participants->first()?->pivot : null;

        return [
            'id' => 'ch-' . $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'details' => $c->details,
            'reward' => $c->reward,
            'days' => (int) $c->days,
            'participants' => (int) ($c->participants_count ?? 0),
            'active' => (bool) $c->active,
            'joined' => $pivot !== null,
            'progress' => (int) ($pivot?->progress ?? 0),
            'lastLogDate' => $pivot?->last_log_date
                ? (new \DateTime($pivot->last_log_date))->format('D M d Y')
                : null,
            'emoji' => $c->emoji,
            'cover' => $c->cover_url,
        ];
    }
}
