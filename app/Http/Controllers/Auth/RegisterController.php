<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        // Check if user already exists to prevent enumeration
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            // Return generic message without revealing email exists
            return response()->json([
                'message' => 'Registration successful. Check your email to verify your account.',
            ], 201);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Log them in immediately after registration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;
        
        // Return token as HttpOnly cookie instead of JSON
        return response()
            ->json([
                'user'    => $user,
                'message' => 'Registered successfully',
            ], 201)
            ->cookie(
                'auth_token',
                $token,
                config('sanctum.expiration'),
                '/',
                env('SESSION_DOMAIN'),
                env('SESSION_SECURE_COOKIE', false),
                true,  // httpOnly
                false, // raw
                'Strict' // sameSite
            );
    }
}
