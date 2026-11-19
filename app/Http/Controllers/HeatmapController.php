<?php

namespace App\Http\Controllers;

use App\Models\PeakCravingAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapController extends Controller
{
    // Grid derives each cell from the LATEST logged intensity for that (day_index, slot_index).
    public function index(Request $request)
    {
        $rows = $request->user()->cravingLogs()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get(['day_index', 'slot_index', 'intensity']);

        $heatmap = [];
        foreach ($rows as $row) {
            if ($row->slot_index >= 0 && $row->slot_index <= 5) {
                $heatmap["{$row->day_index}-{$row->slot_index}"] = (int) $row->intensity;
            }
        }

        return response()->json(['heatmap' => $heatmap]);
    }

    // Cycle a cell 0→1→2→0; records an event reflecting the new level.
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'day_index' => 'required|integer|min:0|max:6',
            'slot_index' => 'required|integer|min:0|max:5',
            'intensity' => 'required|integer|min:0|max:2',
        ]);

        $request->user()->cravingLogs()->create([
            'day_index' => $data['day_index'],
            'slot_index' => $data['slot_index'],
            'intensity' => $data['intensity'],
            'occurred_at' => now(),
        ]);

        // Peak Craving (2) schedules a weekly alert for this window's start;
        // cycling the cell away from 2 cancels it.
        $this->syncPeakAlert($request->user(), $data['day_index'], $data['slot_index'], $data['intensity']);

        return response()->json(['ok' => true]);
    }

    // "Log craving now" — writes a peak event to the current day+slot.
    public function logNow(Request $request)
    {
        $data = $request->validate([
            'intensity' => 'sometimes|integer|min:0|max:2',
            'day_index' => 'sometimes|integer|min:0|max:6',
            'slot_index' => 'sometimes|integer|min:0|max:5',
        ]);

        $now = now();
        $hour = $now->hour;
        $slot = $hour >= 6 && $hour < 9 ? 0
            : ($hour >= 9 && $hour < 12 ? 1
            : ($hour >= 12 && $hour < 15 ? 2
            : ($hour >= 15 && $hour < 18 ? 3
            : ($hour >= 18 && $hour < 21 ? 4 : 5))));
        $day = $data['day_index'] ?? $now->dayOfWeek;
        $slot = $data['slot_index'] ?? $slot;

        $request->user()->cravingLogs()->create([
            'day_index' => $day,
            'slot_index' => $slot,
            'intensity' => $data['intensity'] ?? 2,
            'occurred_at' => $now,
        ]);

        // "Log craving now" with peak intensity also arms the weekly alert.
        $this->syncPeakAlert($request->user(), $day, $slot, $data['intensity'] ?? 2);

        return response()->json(['ok' => true]);
    }

    private function syncPeakAlert($user, int $dayIndex, int $slotIndex, int $intensity): void
    {
        if ($intensity === 2) {
            PeakCravingAlert::updateOrCreate(
                ['user_id' => $user->id, 'day_index' => $dayIndex, 'slot_index' => $slotIndex],
                ['active' => true]
            );
        } else {
            PeakCravingAlert::where('user_id', $user->id)
                ->where('day_index', $dayIndex)
                ->where('slot_index', $slotIndex)
                ->update(['active' => false]);
        }
    }
}
