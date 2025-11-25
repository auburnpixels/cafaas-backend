<?php

namespace App\Http\Middleware;

use Closure;

/**
 * @class AppSpecific
 */
class AppSpecific
{
    /**
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->guard('admin')->user()->app_id !== $request->domain->app->id) {
            abort(403);
        }

        return $next($request);
    }
}
