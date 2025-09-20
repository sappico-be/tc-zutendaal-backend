<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // User abilities/permissions (customize as needed)
        $userAbilityRules = [
            ['action' => 'manage', 'subject' => 'all'], // Admin rights
        ];

        return response()->json([
            'accessToken' => $token,
            'userData' => [
                'id' => $user->id,
                'fullName' => $user->name,
                'username' => explode('@', $user->email)[0],
                'avatar' => $user->avatar ?? '/images/avatars/avatar-1.png',
                'email' => $user->email,
                'role' => 'admin', // Adjust based on your roles
            ],
            'userAbilityRules' => $userAbilityRules,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json([
            'userData' => [
                'id' => $request->user()->id,
                'fullName' => $request->user()->name,
                'username' => explode('@', $request->user()->email)[0],
                'avatar' => $request->user()->avatar ?? '/images/avatars/avatar-1.png',
                'email' => $request->user()->email,
                'role' => 'admin',
            ],
        ]);
    }
}
