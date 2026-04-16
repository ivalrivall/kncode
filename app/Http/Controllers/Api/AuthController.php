<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Freelance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive.'],
            ]);
        }

        $profile = null;
        if ($user->role === 'company') {
            $profile = $user->company;
        } elseif ($user->role === 'freelance') {
            $profile = $user->freelance;
        }

        return response()->json([
            'user' => $user,
            'profile' => $profile,
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:company,freelance',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'active',
        ]);

        if ($request->role === 'company') {
            Company::create([
                'user_id' => $user->id,
                'name' => $request->name,
            ]);
        } elseif ($request->role === 'freelance') {
            Freelance::create([
                'user_id' => $user->id,
                'fullname' => $request->name,
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $profile = null;

        if ($user->role === 'company') {
            $profile = $user->company;
        } elseif ($user->role === 'freelance') {
            $profile = $user->freelance;
        }

        return response()->json([
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}
