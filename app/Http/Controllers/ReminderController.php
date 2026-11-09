<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $reminders = $request->user()->reminders()->get()
            ->map(fn($r) => ['id' => $r->key, 'time' => $r->time, 'enabled' => $r->enabled]);

        return response()->json(['reminders' => $reminders]);
    }

    public function update(Request $request, string $key)
    {
        $data = $request->validate([
            'time' => 'sometimes|required|string',
            'enabled' => 'sometimes|required|boolean',
        ]);

        $request->user()->reminders()->updateOrCreate(['key' => $key], $data);

        return response()->json(['ok' => true]);
    }
}
