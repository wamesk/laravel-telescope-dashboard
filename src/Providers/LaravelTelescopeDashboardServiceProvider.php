<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeColumn;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeMatcher;
use Wame\LaravelTelescopeDashboard\ApiTypes\ConfigApiTypeProvider;
use Wame\LaravelTelescopeDashboard\Console\SyncApiTypesCommand;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;
use Wame\LaravelTelescopeDashboard\Http\Middleware\AuthorizeDashboard;
use Wame\LaravelTelescopeDashboard\Jobs\SyncApiTypesJob;

class LaravelTelescopeDashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/wame-telescope-dashboard.php', 'wame-telescope-dashboard');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'telescope-dashboard');

        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'telescope-dashboard');

        $this->app->bindIf(ApiTypeProvider::class, fn ($app) => $app->make(
            config('wame-telescope-dashboard.api_types.provider', ConfigApiTypeProvider::class)
        ));

        // Compile the masks once per request / job instead of on every resolution.
        $this->app->scoped(ApiTypeMatcher::class);
    }

    public function boot(): void
    {
        if (! config('wame-telescope-dashboard.enabled')) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerApiTypeRecording();
        $this->registerRoutes();
        $this->registerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncApiTypesCommand::class]);

            $this->registerApiTypeSyncAfterMigrate();
        }
    }

    /**
     * Store the API type of new entries right after Telescope saved them.
     *
     * Telescope stores entries while the application terminates (after the
     * response was sent), so this never delays the response itself.
     */
    protected function registerApiTypeRecording(): void
    {
        if (config('telescope.driver', 'database') !== 'database') {
            return;
        }

        Telescope::afterStoring(
            fn (array $entries): bool => $this->app->make(ApiTypeColumn::class)->afterStoring($entries)
        );
    }

    /**
     * Recompute the stored API types after `migrate` when the map changed.
     *
     * Deploys run `migrate` and ship the map, so this keeps old entries current
     * without a scheduled command (which Telescope would record on every run).
     * `migrate` fires NoPendingMigrations instead of MigrationsEnded when there is
     * nothing to migrate, so both are handled. The work itself is queued.
     */
    protected function registerApiTypeSyncAfterMigrate(): void
    {
        if (! config('wame-telescope-dashboard.api_types.sync_after_migrate', true) || $this->app->runningUnitTests()) {
            return;
        }

        Event::listen([MigrationsEnded::class, NoPendingMigrations::class], function (object $event): void {
            if ($event->method !== 'up' || (property_exists($event, 'options') && ($event->options['pretend'] ?? false))) {
                return;
            }

            rescue(function (): void {
                $column = $this->app->make(ApiTypeColumn::class);

                if (! $column->isStale() || ! $column->columnExists()) {
                    return;
                }

                SyncApiTypesJob::dispatch()
                    ->onConnection(config('wame-telescope-dashboard.api_types.sync_queue.connection'))
                    ->onQueue(config('wame-telescope-dashboard.api_types.sync_queue.queue'));
            });
        });
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
