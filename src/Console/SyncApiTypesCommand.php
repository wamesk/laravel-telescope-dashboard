<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Console;

use Illuminate\Console\Command;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeColumn;

class SyncApiTypesCommand extends Command
{
    protected $signature = 'telescope-dashboard:sync-api-types
        {--force : Recompute every entry even when the API type map did not change}
        {--chunk= : Number of entries read per query (default: api_types.sync_chunk)}';

    protected $description = 'Store the API type of Telescope requests and client requests in the api_type column';

    public function handle(ApiTypeColumn $column): int
    {
        if (! $column->columnExists()) {
            $this->components->error('The telescope_entries.api_type column is missing. Run "php artisan migrate" first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $column->isStale()) {
            $this->components->info('The stored API types already match the current map.');

            return self::SUCCESS;
        }

        $chunk = $this->option('chunk') ?? config('wame-telescope-dashboard.api_types.sync_chunk');
        $changed = $column->sync($chunk === null ? null : (int) $chunk);

        $this->components->info("API type updated on {$changed} entries.");

        return self::SUCCESS;
    }
}
