<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapController extends Controller
{
    // Grid derives each cell from MAX(intensity) across logged craving events.
    public function index(Request $request)
    {
        $rows = $request->user()->cravingLogs()
            ->select('day_index', 'slot_index', DB::raw('MAX(intensity) as intensity'))
            ->groupBy('day_index', 'slot_index')
            ->get();

        $heatmap = [];
        foreach ($rows as $row) {
            $heatmap["{$row->day_index}-{$row->slot_index}"] = (int) $row->intensity;
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
        $slot = intdiv($now->hour, 3); // 0..5 for 3h blocks

        $request->user()->cravingLogs()->create([
            'day_index' => $data['day_index'] ?? $now->dayOfWeek,
            'slot_index' => $data['slot_index'] ?? $slot,
            'intensity' => $data['intensity'] ?? 2,
            'occurred_at' => $now,
        ]);

        return response()->json(['ok' => true]);
    }
}
