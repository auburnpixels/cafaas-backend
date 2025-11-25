<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Auth\LoginRequest;
use App\Http\Requests\Internal\Auth\LogoutRequest;
use App\Http\Requests\Internal\Auth\MeRequest;
use App\Http\Requests\Internal\Auth\RefreshTokenRequest;
use App\Http\Requests\Internal\Auth\RegisterOperatorRequest;
use App\Http\Resources\Internal\Auth\LoginResource;
use App\Http\Resources\Internal\Auth\MeResource;
use App\Http\Resources\Internal\Auth\RefreshTokenResource;
use App\Http\Resources\Internal\Auth\RegisterResource;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Authentication controller for internal dashboard (JWT-based)
 */
final class AuthController extends Controller
{
    /**
     * Handle login request and return JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Attempt to find user
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid email or password.',
                ],
            ], 401);
        }

        // Check if user has dashboard access (operator, regulator, or admin role)
        if (! in_array($user->role, ['operator', 'regulator', 'admin'])) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED_ROLE',
                    'message' => 'You do not have access to the dashboard.',
                ],
            ], 403);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        return (new LoginResource([
            'access_token' => $token,
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
        ]))->response();
    }

    /**
     * Refresh JWT token.
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();

            return (new RefreshTokenResource([
                'access_token' => $token,
                'expires_in' => config('jwt.ttl') * 60,
            ]))->response();
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'message' => 'Failed to refresh token.',
                ],
            ], 401);
        }
    }

    /**
     * Logout and invalidate token.
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Successfully logged out.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'LOGOUT_FAILED',
                    'message' => 'Failed to logout.',
                ],
            ], 500);
        }
    }

    /**
     * Get authenticated user details.
     */
    public function me(MeRequest $request): JsonResponse
    {
        $user = $request->user();

        return (new MeResource($user))->response();
    }

    /**
     * Register a new operator and associated user account.
     */
    public function register(RegisterOperatorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            [$operator, $user] = DB::transaction(function () use ($validated) {
                // 1. Create Operator record
                $operator = Operator::create([
                    'name' => $validated['operator_name'],
                    'slug' => Str::slug($validated['operator_name']),
                    'is_active' => true,
                ]);

                // 2. Create User record
                $user = User::create([
                    'uuid' => Str::uuid()->toString(),
                    'name' => $validated['operator_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'operator',
                    'operator_id' => $operator->id,
                ]);

                return [$operator, $user];
            });

            // 3. Generate JWT token for immediate login
            $token = JWTAuth::fromUser($user);

            return (new RegisterResource([
                'access_token' => $token,
                'expires_in' => config('jwt.ttl') * 60,
                'user' => $user,
                'operator' => $operator,
            ]))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
