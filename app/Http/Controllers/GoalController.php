<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = $request->user()->goals()->latest()->get()
            ->map(fn($g) => ['id' => $g->id, 'title' => $g->title, 'price' => (float) $g->price]);

        return response()->json(['goals' => $goals]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
        ]);

        $goal = $request->user()->goals()->create($data);

        return response()->json(['goal' => [
            'id' => $goal->id, 'title' => $goal->title, 'price' => (float) $goal->price,
        ]], 201);
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $goal->delete();
        return response()->json(['ok' => true]);
    }
}
