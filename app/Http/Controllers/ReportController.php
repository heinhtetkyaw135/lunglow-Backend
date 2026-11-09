<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => 'required|string',
            'target_id' => 'nullable|string|max:60',
            'parent_id' => 'nullable|string|max:60',
            'target_author' => 'nullable|string|max:120',
            'reason' => 'required|string|max:200',
            'note' => 'nullable|string|max:500',
            'excerpt' => 'nullable|string',
        ]);

        $report = $request->user()->reports()->create([
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'target_author' => $data['target_author'] ?? null,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['report' => ['id' => 'rep-' . $report->id]], 201);
    }
}
