<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = auth('api')->attempt($request->validated());

        if (! $token) {
            return response()->json([
                'error' => 'invalid_credentials',
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        $user = auth('api')->user();

        if (! $user->is_active) {
            auth('api')->logout();
            return response()->json([
                'error' => 'account_inactive',
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        return $this->respondWithToken($token, $user);
    }

    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();
        $user = auth('api')->setToken($token)->user();

        return $this->respondWithToken($token, $user);
    }

    public function logout(): Response
    {
        auth('api')->logout();

        return response()->noContent();
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load('roles');

        return UserResource::make($user)->response()->setStatusCode(200);
    }

    private function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource($user->load('roles')),
        ]);
    }
}
