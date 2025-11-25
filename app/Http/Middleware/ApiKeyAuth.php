<?php

namespace App\Http\Middleware;

use App\Services\OperatorAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * @class ApiKeyAuth
 *
 * Middleware to authenticate operators via API key
 */
class ApiKeyAuth
{
    protected OperatorAuthService $authService;

    public function __construct(OperatorAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract API key from Authorization header
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => [
                    'code' => 'MISSING_API_KEY',
                    'message' => 'API key is required. Please provide a valid API key in the Authorization header.',
                ],
            ], 401);
        }

        $apiKey = substr($authHeader, 7); // Remove "Bearer " prefix

        // Validate API key format
        // Removed "caas_" prefix check since keys are now generated without it
        if (strlen($apiKey) < 32) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_API_KEY_FORMAT',
                    'message' => 'Invalid API key format.',
                ],
            ], 401);
        }

        // Check rate limiting
        $rateLimitKey = 'api_rate_limit:'.hash('sha256', $apiKey);
        $maxRequests = config('app.operator_api_rate_limit', 100);
        $window = config('app.operator_api_rate_window', 1); // minutes

        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts >= $maxRequests) {
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => "Rate limit exceeded. Maximum {$maxRequests} requests per {$window} minute(s).",
                    'retry_after' => Cache::get($rateLimitKey.':reset'),
                ],
            ], 429);
        }

        // Validate API key and get operator
        $operator = $this->authService->validateApiKey($apiKey);

        if (! $operator) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid or revoked API key.',
                ],
            ], 401);
        }

        // Increment rate limit counter
        if ($attempts === 0) {
            Cache::put($rateLimitKey, 1, now()->addMinutes($window));
            Cache::put($rateLimitKey.':reset', now()->addMinutes($window)->timestamp, now()->addMinutes($window));
        } else {
            Cache::increment($rateLimitKey);
        }

        // Add remaining requests to response headers
        $remaining = max(0, $maxRequests - ($attempts + 1));
        $resetTime = Cache::get($rateLimitKey.':reset', now()->addMinutes($window)->timestamp);

        $request->attributes->set('rate_limit_remaining', $remaining);
        $request->attributes->set('rate_limit_reset', $resetTime);

        // Attach operator to request
        $request->attributes->set('operator', $operator);
        $request->merge(['operator' => $operator]);

        // Log API call
        $this->authService->logApiCall($operator, $request);

        $response = $next($request);

        // Add rate limit headers to response
        $response->headers->set('X-RateLimit-Limit', $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }
}
