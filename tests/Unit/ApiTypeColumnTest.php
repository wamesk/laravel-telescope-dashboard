<?php

declare(strict_types=1);

use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeColumn;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeMatcher;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

/**
 * @param  array<string, array<string, mixed>>  $maps
 */
function apiTypeColumn(array $maps): ApiTypeColumn
{
    $provider = new class($maps) implements ApiTypeProvider
    {
        public function __construct(private array $maps) {}

        public function map(string $entryType): array
        {
            return $this->maps[$entryType] ?? [];
        }
    };

    return new ApiTypeColumn(new ApiTypeMatcher($provider), $provider);
}

/**
 * A stand-in for Telescope's IncomingEntry (its constructor needs the application clock).
 */
function storedEntry(string $uuid, string $type, array|string $content): object
{
    return (object) ['uuid' => $uuid, 'type' => $type, 'content' => $content];
}

it('resolves request and client request entries, also when the content is already JSON encoded', function () {
    $column = apiTypeColumn([
        'request' => ['GET /api/v1/point/{pod}/check' => 'GetCollectionPointCheck'],
        'client_request' => ['POST */v1/pods' => 'Billien7.getPoints'],
    ]);

    $names = $column->namesFor([
        storedEntry('a', 'request', ['method' => 'GET', 'uri' => '/api/v1/point/SK123/check?lang=sk']),
        storedEntry('b', 'client_request', json_encode(['method' => 'POST', 'uri' => 'https://b7.test/v1/pods'])),
    ]);

    expect($names)->toBe(['a' => 'GetCollectionPointCheck', 'b' => 'Billien7.getPoints']);
});

it('skips other entry types, unmatched entries and entries without uuid or uri', function () {
    $column = apiTypeColumn(['request' => ['GET /api/v1/settings' => 'GetSettings']]);

    $names = $column->namesFor([
        storedEntry('query', 'query', ['sql' => 'select 1', 'uri' => '/api/v1/settings']),
        storedEntry('unmatched', 'request', ['method' => 'GET', 'uri' => '/api/v1/unknown']),
        storedEntry('', 'request', ['method' => 'GET', 'uri' => '/api/v1/settings']),
        storedEntry('no-uri', 'request', ['method' => 'GET']),
        storedEntry('broken-json', 'request', '{not json'),
        storedEntry('matched', 'request', ['method' => 'GET', 'uri' => '/api/v1/settings']),
    ]);

    expect($names)->toBe(['matched' => 'GetSettings']);
});

it('does no work without any map', function () {
    $names = apiTypeColumn([])->namesFor([
        storedEntry('a', 'request', ['method' => 'GET', 'uri' => '/api/v1/settings']),
    ]);

    expect($names)->toBe([]);
});

it('changes the fingerprint only when a map changes', function () {
    $map = ['request' => ['GET /api/v1/a' => 'a'], 'client_request' => ['GET */v1/b' => 'b']];

    $fingerprint = apiTypeColumn($map)->fingerprint();

    expect(apiTypeColumn($map)->fingerprint())->toBe($fingerprint)
        ->and(apiTypeColumn(['request' => ['GET /api/v1/a' => 'renamed']] + $map)->fingerprint())->not->toBe($fingerprint)
        ->and(apiTypeColumn($map + ['query' => ['x' => 'ignored']])->fingerprint())->toBe($fingerprint);
});
