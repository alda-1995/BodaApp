<?php

namespace App\Providers;

use App\Services\Notifications\NotificationContext;
use App\View\Composers\Menu\SidebarComposer;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            return new StripeClient(config('services.stripe.secret'));
        });

        $this->app->singleton(NotificationContext::class, function ($app) {
            $defaultDriver = config('services.notifications.default', 'smtp');
            return (new NotificationContext())->via($defaultDriver);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.menu.sidebar', SidebarComposer::class);
    }
}
