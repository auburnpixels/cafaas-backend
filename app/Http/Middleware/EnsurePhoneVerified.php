<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * @class EnsurePhoneVerified
 */
class EnsurePhoneVerified
{
    /**
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->hasVerifiedPhoneNumber()) {
            return redirect()->route('account.settings.phone-number');
        }

        return $next($request);
    }
}
