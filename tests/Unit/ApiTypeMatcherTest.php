<?php

declare(strict_types=1);

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeMatcher;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

/**
 * @param  array<string, array<string, string>>  $maps
 */
function apiTypeMatcher(array $maps): ApiTypeMatcher
{
    return new ApiTypeMatcher(new class($maps) implements ApiTypeProvider
    {
        public function __construct(private array $maps) {}

        public function map(string $entryType): array
        {
            return $this->maps[$entryType] ?? [];
        }
    });
}

/**
 * A MySQL query builder that never connects (the PDO resolver is never called by toSql()).
 */
function telescopeEntriesQuery(): Builder
{
    return (new MySqlConnection(fn () => throw new LogicException('No DB in unit tests')))->table('telescope_entries');
}

it('splits the map key into an optional method and the mask', function (string $key, ?string $method, string $mask) {
    expect(apiTypeMatcher([])->parseKey($key))->toBe([$method, $mask]);
})->with([
    'with method' => ['POST */v1/pods', 'POST', '*/v1/pods'],
    'lowercase method' => ['get /api/v1/settings', 'GET', '/api/v1/settings'],
    'extra spaces' => ['  PUT    /api/v1/customer  ', 'PUT', '/api/v1/customer'],
    'without method' => ['*/v1/pods', null, '*/v1/pods'],
    'unknown first word stays in the mask' => ['FOO /x', null, 'FOO /x'],
]);

it('resolves request masks with path parameters and ignores the query string', function () {
    $matcher = apiTypeMatcher(['request' => [
        'GET /api/v1/point/{pod}/check' => 'point.check',
        'POST /api/v1/point/{pod}/meter/{sn}/deduction' => 'point.meter.deduction.store',
    ]]);

    expect($matcher->resolve('request', 'GET', '/api/v1/point/SKSPPDIS000123/check'))->toBe('point.check')
        ->and($matcher->resolve('request', 'GET', '/api/v1/point/SKSPPDIS000123/check?lang=sk'))->toBe('point.check')
        ->and($matcher->resolve('request', 'POST', '/api/v1/point/1/meter/2/deduction'))->toBe('point.meter.deduction.store');
});

it('matches exactly one path segment per parameter and anchors both ends', function () {
    $matcher = apiTypeMatcher(['request' => ['GET /api/v1/point/{pod}' => 'point.show']]);

    expect($matcher->resolve('request', 'GET', '/api/v1/point/123'))->toBe('point.show')
        ->and($matcher->resolve('request', 'GET', '/api/v1/point/123/list'))->toBeNull()
        ->and($matcher->resolve('request', 'GET', '/api/v1/point/'))->toBeNull()
        ->and($matcher->resolve('request', 'GET', '/prefix/api/v1/point/123'))->toBeNull();
});

it('respects the method and treats a mask without method as any method', function () {
    $matcher = apiTypeMatcher(['client_request' => [
        'PUT */v1/pods/{pod}' => 'Billien7.updatePoint',
        '*/v1/health' => 'Billien7.health',
    ]]);

    expect($matcher->resolve('client_request', 'PUT', 'https://b7.test/v1/pods/1'))->toBe('Billien7.updatePoint')
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/1'))->toBeNull()
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/health'))->toBe('Billien7.health')
        ->and($matcher->resolve('client_request', 'POST', 'https://b7.test/v1/health'))->toBe('Billien7.health');
});

it('uses a leading wildcard for the scheme and host of client requests', function () {
    $matcher = apiTypeMatcher(['client_request' => ['GET */v1/configurations' => 'Billien7.getConfigurations']]);

    expect($matcher->resolve('client_request', 'GET', 'https://bablsp02:8086/v1/configurations'))->toBe('Billien7.getConfigurations')
        ->and($matcher->resolve('client_request', 'GET', 'http://10.0.0.1/api/v1/configurations?x=1'))->toBe('Billien7.getConfigurations')
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/configurations/extra'))->toBeNull();
});

it('matches the query string when the mask contains one', function () {
    $matcher = apiTypeMatcher(['client_request' => [
        'GET */v1/pods/{pod}/meters?meterSN=*' => 'Billien7.checkMeter',
        'GET */v1/pods/{pod}/meters' => 'Billien7.getPointMeters',
    ]]);

    expect($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/1/meters?meterSN=987'))->toBe('Billien7.checkMeter')
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/1/meters'))->toBe('Billien7.getPointMeters')
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/1/meters?other=1'))->toBe('Billien7.getPointMeters');
});

it('treats regex characters in the mask literally', function () {
    $matcher = apiTypeMatcher(['request' => ['GET /api/v1.0/a+b(c)' => 'literal']]);

    expect($matcher->resolve('request', 'GET', '/api/v1.0/a+b(c)'))->toBe('literal')
        ->and($matcher->resolve('request', 'GET', '/api/v1x0/aab(c)'))->toBeNull();
});

it('lets the first matching mask win', function () {
    $matcher = apiTypeMatcher(['client_request' => [
        'GET */v1/pods/consumerChange' => 'Billien7.deactivatePoints',
        'GET */v1/pods/{pod}' => 'Billien7.pod',
    ]]);

    expect($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/consumerChange'))->toBe('Billien7.deactivatePoints')
        ->and($matcher->resolve('client_request', 'GET', 'https://b7.test/v1/pods/123'))->toBe('Billien7.pod');
});

it('returns null for unmatched or empty URIs and for unknown entry types', function () {
    $matcher = apiTypeMatcher(['request' => ['GET /api/v1/settings' => 'settings.index']]);

    expect($matcher->resolve('request', 'GET', '/api/v1/unknown'))->toBeNull()
        ->and($matcher->resolve('request', 'GET', null))->toBeNull()
        ->and($matcher->resolve('request', 'GET', ''))->toBeNull()
        ->and($matcher->resolve('query', 'GET', '/api/v1/settings'))->toBeNull();
});

it('lists distinct names alphabetically and skips invalid entries', function () {
    $matcher = apiTypeMatcher(['request' => [
        'POST /api/v1/point' => 'PostCollectionPointCreate',
        'GET /api/v1/point' => 'GetCustomerCollectionPointList',
        'POST /api/v1/logout' => 'auth.logout',
        'GET /api/health' => 'getApiHealth',
        'GET /api/v1/customer' => 'GetCustomerDetail',
        'GET /api/v1/customer/details' => 'GetCustomerDetail',
        'GET /api/v1/empty' => '',
        'GET /api/v1/not-a-name' => ['array'],
    ]]);

    expect($matcher->names('request'))->toBe([
        'auth.logout',
        'getApiHealth',
        'GetCustomerCollectionPointList',
        'GetCustomerDetail',
        'PostCollectionPointCreate',
    ])->and($matcher->names('client_request'))->toBe([]);
});

it('builds a REGEXP filter that mirrors first-match-wins', function () {
    $matcher = apiTypeMatcher(['client_request' => [
        'GET */v1/pods/consumerChange' => 'Billien7.deactivatePoints',
        'POST */v1/pods' => 'Billien7.getPoint',
        'GET */v1/pods/{pod}' => 'Billien7.pod',
    ]]);

    $query = telescopeEntriesQuery();
    $matcher->applyFilter($query, 'client_request', 'Billien7.pod');

    // The POST mask cannot shadow a GET rule, so only the earlier GET mask is excluded.
    expect($query->toSql())->toBe(
        'select * from `telescope_entries` where ((((`c_method` = ? and `c_uri` REGEXP ?)) and not (((`c_method` = ? and `c_uri` REGEXP ?)))))'
    )->and($query->getBindings())->toBe([
        'GET', '(?:'.$matcher->compileMask('*/v1/pods/{pod}').')',
        'GET', '(?:'.$matcher->compileMask('*/v1/pods/consumerChange').')',
    ]);
});

it('filters unmatched entries by excluding every mask grouped by method', function () {
    $matcher = apiTypeMatcher(['request' => [
        'GET /api/v1/a' => 'a',
        'GET /api/v1/b' => 'b',
        '/api/v1/any' => 'any',
    ]]);

    $query = telescopeEntriesQuery();
    $matcher->applyFilter($query, 'request', ApiTypeMatcher::UNMATCHED);

    expect($query->toSql())->toBe(
        'select * from `telescope_entries` where not (((`c_method` = ? and `c_uri` REGEXP ?) or (`c_uri` REGEXP ?)))'
    )->and($query->getBindings())->toBe([
        'GET',
        '(?:'.$matcher->compileMask('/api/v1/a').')|(?:'.$matcher->compileMask('/api/v1/b').')',
        '(?:'.$matcher->compileMask('/api/v1/any').')',
    ]);
});

it('matches nothing for an unknown name and nothing is filtered for unmatched without a map', function () {
    $matcher = apiTypeMatcher(['request' => ['GET /api/v1/a' => 'a']]);

    $unknown = telescopeEntriesQuery();
    $matcher->applyFilter($unknown, 'request', 'missing');

    $unmatchedWithoutMap = telescopeEntriesQuery();
    $matcher->applyFilter($unmatchedWithoutMap, 'client_request', ApiTypeMatcher::UNMATCHED);

    expect($unknown->toSql())->toBe('select * from `telescope_entries` where 1 = 0')
        ->and($unmatchedWithoutMap->toSql())->toBe('select * from `telescope_entries`');
});
