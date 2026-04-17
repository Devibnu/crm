<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function authPayload(User $user, ?string $accessToken = null): array
    {
        $user = $user->fresh() ?? $user;

        $payload = [
            'userData' => $user->toAuthResponse(),
            'userAbilityRules' => $user->getAbilityRules(),
        ];

        if ($accessToken !== null) {
            $payload['accessToken'] = $accessToken;
        }

        return $payload;
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'errors' => [
                    'email' => ['Invalid email or password'],
                ],
            ], 400);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json($this->authPayload($user, $token), 201);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'full_name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'module_permissions' => User::defaultModulePermissionsForRole('client'),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json($this->authPayload($user, $token), 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->authPayload($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
