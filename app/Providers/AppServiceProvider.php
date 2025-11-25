<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Validator::extend('recaptcha', 'App\\Validators\\ReCaptcha@validate');

        // Disable wrapping of JsonResource responses with 'data' key
        JsonResource::withoutWrapping();

        // CaaS Platform: Event logging handled by DrawEventService
        // No observers needed in this implementation
    }
}
