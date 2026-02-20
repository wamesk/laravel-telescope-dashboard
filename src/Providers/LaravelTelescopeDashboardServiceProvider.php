<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Wame\LaravelTelescopeDashboard\Http\Middleware\AuthorizeDashboard;

class LaravelTelescopeDashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/wame-telescope-dashboard.php', 'wame-telescope-dashboard');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'telescope-dashboard');

        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'telescope-dashboard');
    }

    public function boot(): void
    {
        if (! config('wame-telescope-dashboard.enabled')) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerRoutes();
        $this->registerPublishing();
    }

    protected function registerRoutes(): void
    {
        $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');
        $middleware = config('wame-telescope-dashboard.middleware', ['web', 'auth']);

        Route::middleware(array_merge($middleware, [AuthorizeDashboard::class]))
            ->prefix($path)
            ->group(__DIR__.'/../../routes/web.php');
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/wame-telescope-dashboard.php' => config_path('wame-telescope-dashboard.php'),
            ], 'telescope-dashboard-config');

            $this->publishes([
                __DIR__.'/../../dist' => public_path('vendor/telescope-dashboard'),
            ], ['telescope-dashboard-assets', 'laravel-assets']);
        }
    }
}
