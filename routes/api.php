<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes for TC Zutendaal
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'TC Zutendaal API works!',
        'laravel_version' => app()->version(),
        'timestamp' => now()
    ]);
});

// Authentication endpoint
Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'errors' => [
                'email' => ['De opgegeven inloggegevens zijn onjuist.'],
            ]
        ], 422);
    }

    // Create new token (zonder oude tokens te verwijderen voor nu)
    $token = $user->createToken('auth-token')->plainTextToken;

    // Return response in Vuexy format
    return response()->json([
        'accessToken' => $token,
        'userData' => [
            'id' => $user->id,
            'fullName' => $user->name,
            'username' => explode('@', $user->email)[0],
            'avatar' => $user->avatar ?? '/images/avatars/avatar-1.png',
            'email' => $user->email,
            'role' => 'admin',
        ],
        'userAbilityRules' => [
            ['action' => 'manage', 'subject' => 'all'],
        ],
    ]);
});

// Logout endpoint
Route::post('/auth/logout', function (Request $request) {
    if ($request->user()) {
        $request->user()->currentAccessToken()->delete();
    }
    
    return response()->json(['message' => 'Successfully logged out']);
})->middleware('auth:sanctum');

// Get current user
Route::get('/auth/user', function (Request $request) {
    $user = $request->user();
    
    return response()->json([
        'userData' => [
            'id' => $user->id,
            'fullName' => $user->name,
            'username' => explode('@', $user->email)[0],
            'avatar' => $user->avatar ?? '/images/avatars/avatar-1.png',
            'email' => $user->email,
            'role' => 'admin',
        ],
        'userAbilityRules' => [
            ['action' => 'manage', 'subject' => 'all'],
        ],
    ]);
})->middleware('auth:sanctum');
