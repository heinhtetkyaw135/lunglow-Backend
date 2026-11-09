<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function index(Request $request)
    {
        $checkins = $request->user()->checkins()
            ->latest('date')
            ->get()
            ->map(fn($c) => ['date' => $c->date->format('Y-m-d'), 'mood' => $c->mood]);

        return response()->json(['checkins' => $checkins]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'mood' => 'nullable|string|max:40',
        ]);

        $request->user()->checkins()->updateOrCreate(
            ['date' => $data['date']],
            ['mood' => $data['mood'] ?? null]
        );

        return response()->json(['ok' => true], 201);
    }
}
