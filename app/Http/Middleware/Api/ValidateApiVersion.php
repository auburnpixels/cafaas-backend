<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

/**
 * Validate and set API version headers
 */
class ValidateApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if Accept header includes version
        $acceptHeader = $request->header('Accept', '');

        // Allow application/json or versioned Accept header
        if ($acceptHeader && ! str_contains($acceptHeader, 'application/json') && ! str_contains($acceptHeader, 'application/vnd.raffaly')) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_PARAMETER',
                    'message' => 'Invalid Accept header. Use application/json or application/vnd.raffaly.v1+json',
                    'request_id' => $request->header('X-Request-Id', uniqid('req_')),
                ],
            ], 406);
        }

        return $next($request);
    }
}
