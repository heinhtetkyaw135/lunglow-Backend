<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserStats;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['settings', 'smokeLogs'])->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(fn($u) => [
            'id' => 'u-' . $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'status' => $u->status,
            'streak' => UserStats::cleanStreak($u),
            'savedMoney' => UserStats::savedMoney($u),
            'avoidedCigs' => UserStats::avoided($u),
            'segment' => UserStats::segment($u),
            'avatar' => $u->avatar_url,
            'joinedAt' => $u->created_at?->format('Y-m-d'),
            'bio' => $u->bio,
        ]);

        return response()->json(['users' => $users]);
    }

    public function toggleStatus(User $user)
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Admin accounts cannot be suspended.'], 422);
        }
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();
        return response()->json(['user' => ['id' => 'u-' . $user->id, 'status' => $user->status]]);
    }

    public function resetAvatar(User $user)
    {
        $user->update(['avatar' => null]);
        return response()->json(['user' => ['id' => 'u-' . $user->id, 'avatar' => $user->avatar_url]]);
    }
}
