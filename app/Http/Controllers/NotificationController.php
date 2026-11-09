<?php

namespace App\Http\Controllers;

use App\Models\PeakCravingAlert;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * The SPA polls this every ~30s while logged in. Each call:
     *  1. Checks active peak-craving alerts whose weekly window just started
     *     (within the grace window) and delivers the notification once.
     *  2. Returns all not-yet-fetched notifications and marks them fetched.
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

                // Window started recently and hasn't been delivered for it yet.
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

        $notifications = UserNotification::where('user_id', $user->id)
            ->whereNull('fetched_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn($n) => [
                'id' => 'n-' . $n->id,
                'kind' => $n->kind,
                'title' => $n->title,
                'body' => $n->body,
                'meta' => $n->meta,
                'windowStartAt' => $n->window_start_at?->toIso8601String(),
                'createdAt' => $n->created_at->diffForHumans(),
            ]);

        // Mark fetched so each notification is delivered exactly once.
        if ($notifications->isNotEmpty()) {
            UserNotification::where('user_id', $user->id)
                ->whereNull('fetched_at')
                ->update(['fetched_at' => now()]);
        }

        return response()->json(['notifications' => $notifications]);
    }

    // English label matching the SPA's TIME_BLOCKS_EN.
    private static function slotLabel(int $slotIndex): string
    {
        $labels = ['6–9 AM', '9–12 PM', '12–3 PM', '3–6 PM', '6–9 PM', '9–12 AM'];
        return $labels[$slotIndex] ?? '12–3 PM';
    }
}
