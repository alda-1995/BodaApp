<?php

namespace App\Providers;
use Twilio\Rest\Client;

use Illuminate\Support\ServiceProvider;

class TwilioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            return new Client($sid, $token);
        });
    }
    public function boot(): void
    {
    }
}
