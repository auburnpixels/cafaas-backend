<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Add API-specific headers to all responses
 */
class AddApiHeaders
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Generate or retrieve request ID for tracing
        $requestId = $request->header('X-Request-Id', 'req_'.Str::random(16));
        $request->headers->set('X-Request-Id', $requestId);

        $response = $next($request);

        // Add standard API headers to response
        $response->headers->set('X-API-Version', config('api.version', 'v1'));
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        // Add sandbox indicator if in sandbox mode
        if (config('api.sandbox', false)) {
            $response->headers->set('X-API-Environment', 'sandbox');
        }

        return $response;
    }
}
