<?php

namespace App\Http\Controllers;

use App\Models\PeakCravingAlert;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Full notification history for the signed-in user, newest first.
     * The bell panel renders this list, so every past notification survives
     * page reloads and re-logins.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn(UserNotification $n) => $this->serialize($n));

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('read', false)->count(),
        ]);
    }

    /**
     * Create a notification (used by the SPA for local events).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => 'nullable|string|max:40',
            'title' => 'required|string|max:200',
            'body' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $notification = UserNotification::create([
            'user_id' => $request->user()->id,
            'kind' => $data['kind'] ?? 'info',
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->json(['notification' => $this->serialize($notification)], 201);
    }

    /**
     * Mark every notification read (called when the bell panel opens).
     */
    public function markAllRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifications marked read']);
    }

    public function markRead(Request $request, UserNotification $notification)
    {
        $this->authorizeOwner($request, $notification);
        $notification->update(['read_at' => now()]);

        return response()->json(['notification' => $this->serialize($notification)]);
    }

    /** Delete a single notification owned by the caller. */
    public function destroy(Request $request, UserNotification $notification)
    {
        $this->authorizeOwner($request, $notification);
        $notification->delete();

        return response()->json(['message' => 'Notification removed']);
    }

    /** Clear the caller's whole notification history. */
    public function destroyAll(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Notifications cleared']);
    }

    /**
     * Delivers any newly-due peak-craving alerts, then returns everything
     * created since the client's last poll — so the SPA never loses one.
     */
    public function due(Request $request)
    {
        $user = $request->user();
        $graceMinutes = (int) config('services.peak_craving.grace_minutes', 10);

        PeakCravingAlert::where('user_id', $user->id)
            ->where('active', true)
            ->get()
            ->each(function (PeakCravingAlert $alert) use ($user, $graceMinutes) {
                $windowStart = $alert->currentWindowStart();
                $now = now();

                $due = $now->between(
                    $windowStart,
                    $windowStart->copy()->addMinutes($graceMinutes)
                ) && (
                    !$alert->last_sent_at || $alert->last_sent_at->lt($windowStart)
                );

                if ($due) {
                    UserNotification::create([
                        'user_id' => $user->id,
                        'kind' => 'peak-craving',
                        'title' => 'Peak Craving Window',
                        'body' => sprintf(
                            'Your peak craving window (%s) is starting — stay strong!',
                            self::slotLabel($alert->slot_index)
                        ),
                        'meta' => [
                            'day_index' => $alert->day_index,
                            'slot_index' => $alert->slot_index,
                        ],
                        'window_start_at' => $windowStart,
                    ]);
                    $alert->update(['last_sent_at' => $windowStart]);
                }
            });

        // Everything the SPA hasn't picked up yet. Rows stay in the table with
        // fetched_at set, so the history endpoint keeps returning them.
        $notifications = UserNotification::where('user_id', $user->id)
            ->whereNull('fetched_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn($n) => $this->serialize($n));

        if ($notifications->isNotEmpty()) {
            UserNotification::where('user_id', $user->id)
                ->whereNull('fetched_at')
                ->update(['fetched_at' => now()]);
        }

        return response()->json(['notifications' => $notifications]);
    }

    private function serialize(UserNotification $n): array
    {
        return [
            'id' => 'n-' . $n->id,
            'kind' => $n->kind,
            'title' => $n->title,
            'body' => $n->body,
            'meta' => $n->meta,
            'windowStartAt' => $n->window_start_at?->toIso8601String(),
            'createdAt' => $n->created_at?->diffForHumans(),
            'read' => $n->read_at !== null,
        ];
    }

    private function authorizeOwner(Request $request, UserNotification $notification): void
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'Not your notification.');
    }

    // English label matching the SPA's TIME_BLOCKS_EN.
    private static function slotLabel(int $slotIndex): string
    {
        $labels = ['6–9 AM', '9–12 PM', '12–3 PM', '3–6 PM', '6–9 PM', '9–12 AM'];
        return $labels[$slotIndex] ?? '12–3 PM';
    }
}
