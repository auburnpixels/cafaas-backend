<?php

namespace App\Http\Middleware;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Domain;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * @class CustomDomain
 */
class CustomDomain
{
    /**
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $domain = $request->hasHeader('apx-incoming-host') ? $request->header('apx-incoming-host') : $request->getHost();

        // Skip custom domain logic for authentication routes
        $excludedPaths = [
            'login',
            'register',
            'password',
            'email',
            'logout',
            'forgot-password',
            'reset-password',
            'raffles/all',
            'account',
            'api',
            '_ignition',
        ];

        foreach ($excludedPaths as $path) {
            if (Str::startsWith($request->path(), $path)) {
                // Still need to set domain in request if not on main domain
                if (! in_array($domain, explode(',', config('raffaly.excluded_tenant_hosts')))) {
                    $customDomain = Domain::where('domain', $domain)->first();
                    if ($customDomain) {
                        $request->merge(['custom_domain' => $customDomain, 'user' => $customDomain->user]);
                    }
                }

                return $next($request);
            }
        }

        $routes = [
            'profile.show',
            'profile.live',
            'profile.past',
            'checkout.index',
            'checkout.verify',
            'profile.winners',
            'competitions.show',
        ];

        /**
         * If we are on the main domain, and we are in one of the profile routes, see if we have a domain attached
         * for this user and redirect there, if so.
         */
        if (in_array($domain, explode(',', config('raffaly.excluded_tenant_hosts')))) {
            if (isOneOfActiveRoute($routes)) {
                // Extract some route parameters.
                $username = request()->route('username');
                $checkoutUuuid = request()->route('uuid');
                $competitionSlug = request()->route('slug');

                // Get the user from the entity from the route.
                $user = null;
                if ($username) {
                    $user = User::where('username', $username)->first();
                }
                if ($checkoutUuuid) {
                    $checkout = Checkout::where('uuid', $checkoutUuuid)->first();
                    $user = $checkout->competition->user;
                }
                if ($competitionSlug) {
                    $competition = Competition::where('slug', $competitionSlug)->first();
                    if ($competition) {
                        $user = $competition->user;
                    }
                }

                // If we have a user then we can continue.
                if ($user && $user->domains->count() > 0) {
                    $domain = $user->domains()->where('primary', true)->first();

                    if ($domain) {
                        // Merge in entities to the request.
                        $request->merge(['custom_domain' => $domain, 'user' => $domain->user]);

                        return redirect((app()->isLocal() ? 'http' : 'https').'://'.$domain->domain.(app()->isLocal() ? ':82' : '').$request->getRequestUri(), 301);
                    }
                }
            }
        } else {
            // If we arn't on one main domains, check if the domain name can be found as a domain record.
            $domain = Domain::where('domain', $domain)->first();

            if ($domain) {
                // Merge in entities to the request.
                $request->merge(['custom_domain' => $domain, 'user' => $domain->user]);

                switch ($request->getRequestUri()) {
                    case '/'.$domain->user->username:
                        return redirect((app()->isLocal() ? 'http' : 'https').'://'.$domain->domain.(app()->isLocal() ? ':82' : ''), 301);
                    case '/':
                        return response(App::call('App\Http\Controllers\ProfileController@show', ['username' => $domain->user->username]));
                    case Str::startsWith($request->getRequestUri(), '/tickets'):
                    case Str::startsWith($request->getRequestUri(), '/checkout'):
                        $explodedRequestUri = explode('/', $request->getRequestUri());

                        return response(App::call('App\Http\Controllers\CheckoutController@index', ['uuid' => end($explodedRequestUri)]));
                    case Str::startsWith($request->getRequestUri(), '/raffles'):
                        $explodedRequestUri = explode('/', $request->getRequestUri());

                        return response(App::call('App\Http\Controllers\CompetitionController@show', ['slug' => end($explodedRequestUri)]));
                    case '/active-raffles':
                        return response(App::call('App\Http\Controllers\ProfileController@live', ['username' => $domain->user->username]));
                    case '/past-raffles':
                        return response(App::call('App\Http\Controllers\ProfileController@past', ['username' => $domain->user->username]));
                    case '/winners':
                        return response(App::call('App\Http\Controllers\ProfileController@winners', ['username' => $domain->user->username]));
                    default:
                        return response(App::call('App\Http\Controllers\ProfileController@notFound', ['username' => $domain->user->username]), 404);
                }
            }

            return redirect(config('app.url'), 301);
        }

        return $next($request);
    }
}
