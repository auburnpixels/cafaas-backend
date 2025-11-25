<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/entries/*',
        '/checkout/*',
        '/6ce23b78-1489-4b3a-a7ad-004b0b26021e',
        '/0ce13a58-ebd5-4c77-a797-4441c21034c4',
        '/internal/auth/*',  // JWT-based auth endpoints
        '/api/*',  // API endpoints
    ];

    /**
     * @param  Request  $request
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        $referer = $request->headers->get('referer');
        $referer = str_replace(['http://', 'https://'], '', $referer);
        if (Str::endsWith($referer, '/')) {
            $referer = rtrim($referer, '/');
        }

        if ((\Route::currentRouteName() === 'entries.store')) {
            if (app()->isLocal() || Domain::where('domain', $referer)->first()) {
                return $next($request);
            }
        }

        return parent::handle($request, $next);
    }
}
