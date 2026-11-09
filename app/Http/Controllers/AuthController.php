<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'member',
            'status' => 'active',
            'avatar' => null,
        ]);

        // Seed default reminders + settings so first hydration is populated.
        $user->settings()->create(['daily_limit' => 20, 'cig_price' => 0.50]);
        foreach (['morning' => '08:00', 'peak' => '13:00', 'evening' => '21:00'] as $key => $time) {
            $user->reminders()->create(['key' => $key, 'time' => $time, 'enabled' => true]);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json(['user' => $user->toAuthArray(), 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }
        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Your account is suspended.'], 403);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json(['user' => $user->toAuthArray(), 'token' => $token]);
    }

    public function adminLogin(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password) || !$user->isAdmin()) {
            return response()->json(['message' => 'Invalid admin credentials.'], 401);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json(['user' => $user->toAuthArray(), 'token' => $token]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->toAuthArray()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
