<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Cookie;

class LoginController extends Controller
{
    private function tokenCookie(string $token): Cookie
    {
        return cookie(
            'auth_token',
            $token,
            config('sanctum.expiration'),
            '/',
            config('session.domain'),
            config('session.secure'),
            true,   // httpOnly
            false,  // raw
            'Lax'   // sameSite
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'), false)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $user->tokens()->delete();

        $expiration = config('sanctum.expiration');
        $expiresAt  = $expiration ? now()->addMinutes($expiration) : null;
        $token      = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()
            ->json(['user' => $user, 'plainTextToken' => $token])
            ->withCookie($this->tokenCookie($token));
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $expiration = config('sanctum.expiration');
        $expiresAt  = $expiration ? now()->addMinutes($expiration) : null;
        $token      = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()
            ->json(['message' => 'Token refreshed', 'plainTextToken' => $token])
            ->withCookie($this->tokenCookie($token));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()
            ->json(['message' => 'Logged out'])
            ->withoutCookie('auth_token');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user('sanctum'));
    }
}
