<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SmokeLogController extends Controller
{
    // Returns logsByDate map { 'YYYY-MM-DD': count } the SPA feeds into analytics.
    public function index(Request $request)
    {
        $logsByDate = $request->user()->smokeLogs()
            ->get()
            ->mapWithKeys(fn($log) => [$log->date->format('Y-m-d') => $log->count])
            ->all();

        return response()->json(['logsByDate' => $logsByDate]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'count' => 'required|integer|min:0',
        ]);

        $request->user()->smokeLogs()->updateOrCreate(
            ['date' => $data['date']],
            ['count' => $data['count']]
        );

        return response()->json(['ok' => true]);
    }
}
