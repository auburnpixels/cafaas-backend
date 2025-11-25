<?php

namespace App\Http\Middleware;

use App\Http\Services\LocationService;
use Closure;

/**
 * @class SetLocation
 */
class SetLocation
{
    /**
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $location = app(LocationService::class)->getLocation();
        } catch (\Exception $exception) {
            $location = new \stdClass;
            $location->timezone = 'Europe/London';
        }

        // Store location as an attribute (not input) to avoid Symfony type restrictions
        $request->attributes->set('location', $location);

        return $next($request);
    }
}
