<?php

namespace Noo\StatamicLoginLink;

use Illuminate\Support\Facades\Route;
use Noo\StatamicLoginLink\Http\Controllers\LoginLinkController;
use Noo\StatamicLoginLink\Http\Middleware\InjectLoginLinks;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'statamic.cp' => [
            InjectLoginLinks::class,
        ],
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/statamic-login-link.php', 'statamic-login-link');
    }

    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../config/statamic-login-link.php' => config_path('statamic-login-link.php'),
        ], 'statamic-login-link-config');

        $this->registerLoginLinkRoute();
    }

    protected function registerLoginLinkRoute(): void
    {
        Route::middleware('statamic.cp')
            ->prefix(config('statamic.cp.route', 'cp'))
            ->name('statamic.cp.')
            ->group(function () {
                Route::get('auth/login-link/{email}', LoginLinkController::class)
                    ->name('login-link');
            });
    }
}
