<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

        return response()
            ->json(['user' => new UserResource($user), 'plainTextToken' => $token, 'message' => 'Registered successfully'], 201)
            ->cookie(
                'auth_token',
                $token,
                config('sanctum.expiration'),
                '/',
                config('session.domain'),
                config('session.secure'),
                true,
                false,
                'Strict'
            );
    }
}
