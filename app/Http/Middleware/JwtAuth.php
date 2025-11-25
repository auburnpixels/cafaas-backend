<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @class JwtAuth
 *
 * Middleware to authenticate internal dashboard users via JWT
 */
class JwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Attempt to authenticate user via JWT token
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return response()->json([
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User not found.',
                    ],
                ], 401);
            }

            // Attach user to request
            $request->setUserResolver(fn () => $user);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token has expired. Please refresh your token.',
                ],
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'error' => [
                    'code' => 'TOKEN_INVALID',
                    'message' => 'Token is invalid.',
                ],
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'error' => [
                    'code' => 'TOKEN_ABSENT',
                    'message' => 'Token not provided. Please provide a valid JWT token in the Authorization header.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
