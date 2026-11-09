<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

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
            'streak' => $this->cleanStreak($u),
            'savedMoney' => (float) ($u->settings?->cig_price ?? 0) * $this->avoided($u),
            'avoidedCigs' => $this->avoided($u),
            'avatar' => $u->avatar_url,
            'joinedAt' => $u->created_at?->format('Y-m-d'),
            'bio' => $u->bio,
        ]);

        return response()->json(['users' => $users]);
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();
        return response()->json(['user' => ['id' => 'u-' . $user->id, 'status' => $user->status]]);
    }

    public function resetAvatar(User $user)
    {
        $user->update(['avatar' => null]);
        return response()->json(['user' => ['id' => 'u-' . $user->id, 'avatar' => $user->avatar_url]]);
    }

    private function cleanStreak(User $u): int
    {
        $days = $u->smokeLogs()->where('count', 0)->orderByDesc('date')->pluck('date');
        $streak = 0;
        $expected = now()->toDateString();
        foreach ($days as $d) {
            $key = $d->format('Y-m-d');
            if ($key === $expected) {
                $streak++;
                $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
            } elseif ($key < $expected) {
                break;
            }
        }
        return $streak;
    }

    private function avoided(User $u): int
    {
        $limit = $u->settings?->daily_limit ?? 20;
        return $u->smokeLogs()->get()->reduce(fn($carry, $l) => $carry + max(0, $limit - $l->count), 0);
    }
}
