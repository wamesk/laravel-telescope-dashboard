<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wame\LaravelTelescopeDashboard\Providers\LaravelTelescopeDashboardServiceProvider;

/*
 * The migration runs against its own in-memory SQLite connection, so its DDL never
 * reaches the real Telescope database (which an app may share between environments).
 */

const API_TYPE_MIGRATION_CONNECTION = 'telescope_api_type_migration_test';

function apiTypeMigration(): Migration
{
    $root = dirname((new ReflectionClass(LaravelTelescopeDashboardServiceProvider::class))->getFileName(), 3);

    return require $root.'/database/migrations/2026_10_12_000000_add_api_type_column_to_telescope_entries.php';
}

function apiTypeMigrationConnection(): Connection
{
    return DB::connection(API_TYPE_MIGRATION_CONNECTION);
}

function apiTypeMigrationSchema(): Builder
{
    return Schema::connection(API_TYPE_MIGRATION_CONNECTION);
}

/**
 * DDL statements run on the test connection while the callback runs.
 *
 * @return array<int, string>
 */
function apiTypeMigrationDdl(callable $callback): array
{
    $connection = apiTypeMigrationConnection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    $callback();

    $connection->disableQueryLog();

    return collect($connection->getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => (bool) preg_match('/^\s*(alter|create|drop)\b/i', $query))
        ->values()
        ->all();
}

beforeEach(function () {
    config([
        'database.connections.'.API_TYPE_MIGRATION_CONNECTION => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'telescope.storage.database.connection' => API_TYPE_MIGRATION_CONNECTION,
    ]);

    apiTypeMigrationSchema()->create('telescope_entries', function (Blueprint $table) {
        $table->bigIncrements('sequence');
        $table->uuid('uuid');
        $table->string('type', 20);
        $table->boolean('should_display_on_index')->default(true);
    });
});

it('adds the api_type column and its index', function () {
    apiTypeMigration()->up();

    expect(apiTypeMigrationSchema()->hasColumn('telescope_entries', 'api_type'))->toBeTrue()
        ->and(apiTypeMigrationSchema()->hasIndex('telescope_entries', 'idx_te_api_type'))->toBeTrue();
});

it('finishes an interrupted run that added only the column', function () {
    apiTypeMigrationSchema()->table('telescope_entries', function (Blueprint $table) {
        $table->string('api_type', 191)->nullable();
    });

    apiTypeMigration()->up();

    expect(apiTypeMigrationSchema()->hasIndex('telescope_entries', 'idx_te_api_type'))->toBeTrue();
});

it('runs no DDL when the column and the index already exist', function () {
    apiTypeMigration()->up();

    expect(apiTypeMigrationDdl(fn () => apiTypeMigration()->up()))->toBe([])
        ->and(apiTypeMigrationSchema()->hasColumn('telescope_entries', 'api_type'))->toBeTrue()
        ->and(apiTypeMigrationSchema()->hasIndex('telescope_entries', 'idx_te_api_type'))->toBeTrue();
});

it('rolls back a run that added only the column', function () {
    apiTypeMigrationSchema()->table('telescope_entries', function (Blueprint $table) {
        $table->string('api_type', 191)->nullable();
    });

    apiTypeMigration()->down();

    expect(apiTypeMigrationSchema()->hasColumn('telescope_entries', 'api_type'))->toBeFalse();
});

it('rolls back a completed run', function () {
    apiTypeMigration()->up();
    apiTypeMigration()->down();

    expect(apiTypeMigrationSchema()->hasColumn('telescope_entries', 'api_type'))->toBeFalse()
        ->and(apiTypeMigrationSchema()->hasIndex('telescope_entries', 'idx_te_api_type'))->toBeFalse();
});
