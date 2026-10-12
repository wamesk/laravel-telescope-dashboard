<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeColumn;

/**
 * Recompute the stored API types after the map changed (dispatched after `migrate`).
 *
 * Unique, so several servers migrating at once never run parallel full passes;
 * a job that runs after the map was already synced finishes without work.
 */
class SyncApiTypesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Release the unique lock after an hour even if the job never finished.
     */
    public int $uniqueFor = 3600;

    public function handle(ApiTypeColumn $column): void
    {
        $chunk = config('wame-telescope-dashboard.api_types.sync_chunk');

        $column->syncIfStale($chunk === null ? null : (int) $chunk);
    }
}
