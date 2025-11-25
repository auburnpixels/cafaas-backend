<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @class RegulatorOnly
 *
 * Middleware to restrict access to regulator users only
 */
class RegulatorOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        // Check if user is a regulator or admin
        if (! in_array($user->role, ['regulator', 'admin'])) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'This endpoint is restricted to regulators only.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
