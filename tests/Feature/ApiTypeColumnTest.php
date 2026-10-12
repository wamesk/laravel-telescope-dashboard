<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeColumn;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeMatcher;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

function telescopeStorage(): Connection
{
    return DB::connection(config('telescope.storage.database.connection'));
}

function telescopeApiTypeColumnAvailable(): bool
{
    try {
        return Schema::connection(config('telescope.storage.database.connection'))
            ->hasColumn('telescope_entries', ApiTypeColumn::COLUMN);
    } catch (Exception) {
        return false;
    }
}

/**
 * Insert a stored Telescope entry and return its uuid.
 */
function insertTelescopeEntry(string $type, string $method, string $uri, ?string $apiType = null, bool $displayOnIndex = true): string
{
    $uuid = (string) Str::orderedUuid();

    telescopeStorage()->table('telescope_entries')->insert([
        'uuid' => $uuid,
        'batch_id' => (string) Str::orderedUuid(),
        'type' => $type,
        'should_display_on_index' => $displayOnIndex,
        'content' => json_encode(['method' => $method, 'uri' => $uri]),
        'created_at' => now()->toDateTimeString(),
        ApiTypeColumn::COLUMN => $apiType,
    ]);

    return $uuid;
}

function storedApiType(string $uuid): ?string
{
    return telescopeStorage()->table('telescope_entries')->where('uuid', $uuid)->value(ApiTypeColumn::COLUMN);
}

beforeEach(function () {
    config([
        'telescope-api-types.request' => [
            'GET /api/v1/point/{pod}/check' => 'GetCollectionPointCheck',
            'GET /api/v1/settings' => 'GetSettings',
        ],
        'telescope-api-types.client_request' => [
            'POST */v1/pods' => 'Billien7.getPoints',
        ],
    ]);

    if (! telescopeApiTypeColumnAvailable()) {
        return;
    }

    telescopeStorage()->beginTransaction();

    // Start from a known table: the sync walks every request / client request.
    telescopeStorage()->table('telescope_entries')->whereIn('type', ApiTypeColumn::ENTRY_TYPES)->delete();
});

afterEach(function () {
    if (telescopeApiTypeColumnAvailable() && telescopeStorage()->transactionLevel() > 0) {
        telescopeStorage()->rollBack();
    }
});

it('adds the indexed api_type column', function () {
    $schema = Schema::connection(config('telescope.storage.database.connection'));

    expect($schema->hasColumn('telescope_entries', 'api_type'))->toBeTrue()
        ->and($schema->hasIndex('telescope_entries', 'idx_te_api_type'))->toBeTrue();
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');

it('stores the API type of a stored batch with a single query', function () {
    $request = insertTelescopeEntry('request', 'GET', '/api/v1/point/SK1/check');
    $client = insertTelescopeEntry('client_request', 'POST', 'https://b7.test/v1/pods');
    $unmatched = insertTelescopeEntry('request', 'GET', '/api/v1/unknown');

    $entries = telescopeStorage()->table('telescope_entries')
        ->whereIn('uuid', [$request, $client, $unmatched])
        ->get(['uuid', 'type', 'content'])
        ->all();

    telescopeStorage()->enableQueryLog();
    telescopeStorage()->flushQueryLog();

    app(ApiTypeColumn::class)->record($entries);

    $queries = telescopeStorage()->getQueryLog();
    telescopeStorage()->disableQueryLog();

    expect($queries)->toHaveCount(1)
        ->and(storedApiType($request))->toBe('GetCollectionPointCheck')
        ->and(storedApiType($client))->toBe('Billien7.getPoints')
        ->and(storedApiType($unmatched))->toBeNull();
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');

it('runs no query for a batch without a match', function () {
    telescopeStorage()->enableQueryLog();
    telescopeStorage()->flushQueryLog();

    app(ApiTypeColumn::class)->record([
        (object) ['uuid' => 'x', 'type' => 'query', 'content' => ['sql' => 'select 1']],
        (object) ['uuid' => 'y', 'type' => 'request', 'content' => ['method' => 'GET', 'uri' => '/api/v1/unknown']],
    ]);

    expect(telescopeStorage()->getQueryLog())->toBe([]);

    telescopeStorage()->disableQueryLog();
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');

it('fills the column when Telescope stores its entries', function () {
    // Every booted test application registered a hook; keep only this one's.
    Telescope::$afterStoringHooks = array_slice(Telescope::$afterStoringHooks, -1);

    $entry = IncomingEntry::make(['method' => 'GET', 'uri' => '/api/v1/settings', 'response_status' => 200])->type('request');

    Telescope::$entriesQueue = [$entry];
    Telescope::store(app(EntriesRepository::class));

    expect(storedApiType($entry->uuid))->toBe('GetSettings');
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');

it('keeps the following Telescope hooks running when recording fails', function () {
    Exceptions::fake();

    $failingProvider = new class implements ApiTypeProvider
    {
        public function map(string $entryType): array
        {
            throw new RuntimeException('map unavailable');
        }
    };

    $column = new ApiTypeColumn(new ApiTypeMatcher($failingProvider), $failingProvider);

    expect($column->afterStoring([(object) ['uuid' => 'a', 'type' => 'request', 'content' => []]]))->toBeTrue();

    Exceptions::assertReported(RuntimeException::class);
});

it('recomputes stale, missing and outdated values and only touches changed rows', function () {
    $missing = insertTelescopeEntry('request', 'GET', '/api/v1/settings');
    $hidden = insertTelescopeEntry('request', 'GET', '/api/v1/settings', null, false);
    $renamed = insertTelescopeEntry('client_request', 'POST', 'https://b7.test/v1/pods', 'Billien7.oldName');
    $removed = insertTelescopeEntry('request', 'GET', '/api/v1/removed', 'RemovedEndpoint');
    $current = insertTelescopeEntry('request', 'GET', '/api/v1/point/SK1/check', 'GetCollectionPointCheck');

    $changed = app(ApiTypeColumn::class)->sync(2);

    expect($changed)->toBe(4)
        ->and(storedApiType($missing))->toBe('GetSettings')
        ->and(storedApiType($hidden))->toBe('GetSettings')
        ->and(storedApiType($renamed))->toBe('Billien7.getPoints')
        ->and(storedApiType($removed))->toBeNull()
        ->and(storedApiType($current))->toBe('GetCollectionPointCheck');
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');

it('syncs only after the map changed unless forced', function () {
    $entry = insertTelescopeEntry('request', 'GET', '/api/v1/settings');

    $this->artisan('telescope-dashboard:sync-api-types')
        ->expectsOutputToContain('API type updated on 1 entries')
        ->assertSuccessful();

    expect(app(ApiTypeColumn::class)->isStale())->toBeFalse()
        ->and(app(ApiTypeColumn::class)->syncIfStale())->toBeNull();

    $this->artisan('telescope-dashboard:sync-api-types')
        ->expectsOutputToContain('already match the current map')
        ->assertSuccessful();

    $this->artisan('telescope-dashboard:sync-api-types --force')
        ->expectsOutputToContain('API type updated on 0 entries')
        ->assertSuccessful();

    config(['telescope-api-types.request.GET /api/v1/settings' => 'GetAppSettings']);
    app()->forgetScopedInstances();

    expect(app(ApiTypeColumn::class)->isStale())->toBeTrue()
        ->and(app(ApiTypeColumn::class)->syncIfStale())->toBe(1)
        ->and(storedApiType($entry))->toBe('GetAppSettings');
})->skip(fn () => ! telescopeApiTypeColumnAvailable(), 'Telescope DB with the api_type column not available');
