<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Models\Challenge;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChallengeController extends Controller
{
    use ResolvesViewer;
    public function index(Request $request)
    {
        // Public route — resolve the Sanctum token explicitly so `joined` /
        // `progress` reflect the logged-in viewer instead of always being false.
        $viewerId = $this->viewerId($request);

        $challenges = Challenge::withCount('participants')
            ->with($viewerId ? ['participants' => fn($q) => $q->where('users.id', $viewerId)] : [])
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
            UserNotification::notify(
                $user->id,
                'challenge',
                'Challenge Left',
                sprintf('You left the challenge "%s".', $challenge->title),
                ['challenge_id' => $challenge->id, 'action' => 'left']
            );
        } else {
            $challenge->participants()->attach($user->id, ['progress' => 0]);
            UserNotification::notify(
                $user->id,
                'challenge',
                'Challenge Joined',
                sprintf('You joined the challenge "%s" — %d day(s) to earn the %s!', $challenge->title, $challenge->days, $challenge->reward ?: 'badge'),
                ['challenge_id' => $challenge->id, 'action' => 'joined']
            );
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

        $completed = $progress >= $challenge->days;
        if ($completed) {
            UserNotification::notify(
                $user->id,
                'challenge',
                'Badge Earned!',
                sprintf('Congratulations! You completed "%s" and earned the %s badge!', $challenge->title, $challenge->reward ?: 'challenge'),
                ['challenge_id' => $challenge->id, 'action' => 'completed', 'reward' => $challenge->reward]
            );
        } else {
            UserNotification::notify(
                $user->id,
                'challenge',
                'Day Logged',
                sprintf('Day %d of %d logged for "%s" — keep it up!', $progress, $challenge->days, $challenge->title),
                ['challenge_id' => $challenge->id, 'action' => 'logged', 'progress' => $progress]
            );
        }

        $challenge->loadCount('participants')
            ->load(['participants' => fn($q) => $q->where('users.id', $user->id)]);

        return response()->json([
            'challenge' => $this->serialize($challenge, $user->id),
            'completed' => $completed,
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
        $pivot = ($viewerId && $c->relationLoaded('participants'))
            ? $c->participants->firstWhere('id', $viewerId)?->pivot
            : null;

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
