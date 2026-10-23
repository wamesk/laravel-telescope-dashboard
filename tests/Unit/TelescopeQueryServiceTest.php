<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Wame\LaravelTelescopeDashboard\ApiTypes\ApiTypeMatcher;
use Wame\LaravelTelescopeDashboard\Services\TelescopeQueryService;

function telescopeConnectionAvailable(): bool
{
    try {
        DB::connection(config('wame-telescope-dashboard.connection', 'mysql_telescope'))->getPdo();

        return true;
    } catch (\Exception) {
        return false;
    }
}

beforeEach(function () {
    $this->service = new TelescopeQueryService;
});

it('returns empty results when no entries exist', function () {
    $result = $this->service->search(['type' => 'request']);

    expect($result)->toHaveKeys(['entries', 'has_more', 'next_cursor'])
        ->and($result['entries'])->toBeEmpty()
        ->and($result['has_more'])->toBeFalse()
        ->and($result['next_cursor'])->toBeNull();
})->skip(fn () => ! telescopeConnectionAvailable(), 'Telescope DB connection not available');

it('returns null when finding non-existent entry', function () {
    $result = $this->service->find('non-existent-uuid');

    expect($result)->toBeNull();
})->skip(fn () => ! telescopeConnectionAvailable(), 'Telescope DB connection not available');

it('returns filter values for request type', function () {
    $values = $this->service->getFilterValues('request');

    expect($values)->toHaveKeys(['methods', 'route_groups', 'status_groups'])
        ->and($values['methods'])->toContain('GET', 'POST', 'PUT', 'PATCH', 'DELETE');
});

it('returns filter values for log type', function () {
    $values = $this->service->getFilterValues('log');

    expect($values)->toHaveKey('levels')
        ->and($values['levels'])->toContain('error', 'warning', 'info', 'debug');
});

it('returns filter values for job type', function () {
    $values = $this->service->getFilterValues('job');

    expect($values)->toHaveKey('statuses')
        ->and($values['statuses'])->toContain('pending', 'completed', 'failed');
});

it('returns filter values for cache type', function () {
    $values = $this->service->getFilterValues('cache');

    expect($values)->toHaveKey('types')
        ->and($values['types'])->toContain('hit', 'missed', 'set', 'forget');
});

it('returns filter values for model type', function () {
    $values = $this->service->getFilterValues('model');

    expect($values)->toHaveKey('actions')
        ->and($values['actions'])->toContain('created', 'updated', 'deleted');
});

it('returns filter values for gate type', function () {
    $values = $this->service->getFilterValues('gate');

    expect($values)->toHaveKey('results')
        ->and($values['results'])->toContain('allowed', 'denied');
});

it('returns empty array for unsupported filter type', function () {
    $values = $this->service->getFilterValues('dump');

    expect($values)->toBeEmpty();
});

it('respects per_page limit', function () {
    $result = $this->service->search([
        'type' => 'request',
        'per_page' => 10,
    ]);

    expect($result['entries'])->toHaveCount(0);
})->skip(fn () => ! telescopeConnectionAvailable(), 'Telescope DB connection not available');

it('filters the API type on the stored column', function (string $apiType, string $where, array $bindings) {
    $service = new class extends TelescopeQueryService
    {
        public function query(array $filters): Builder
        {
            return $this->buildQuery($filters);
        }
    };

    foreach (['request', 'client_request'] as $type) {
        $query = $service->query(['type' => $type, 'api_type' => $apiType]);

        expect($query->toSql())->toEndWith($where)
            ->and($query->getBindings())->toBe([$type, true, ...$bindings]);
    }
})->with([
    'name' => ['GetCollectionPointCheck', 'and `api_type` = ?', ['GetCollectionPointCheck']],
    'unmatched' => [ApiTypeMatcher::UNMATCHED, 'and `api_type` is null', []],
]);

it('searches requests and client requests by the authenticated user', function (string $value, string $where, array $bindings) {
    $service = new class extends TelescopeQueryService
    {
        public function query(array $filters): Builder
        {
            return $this->buildQuery($filters);
        }
    };

    foreach (['request', 'client_request'] as $type) {
        $query = $service->query(['type' => $type, 'user_email' => $value]);

        expect($query->toSql())->toEndWith($where)
            ->and($query->getBindings())->toBe([$type, true, ...$bindings]);
    }
})->with([
    'full Auth UUID' => [
        '992fc05b-f93c-4b6b-afcb-6fa5b856caf2',
        'and exists (select 1 from `telescope_entries_tags` where `telescope_entries_tags`.`entry_uuid` = `telescope_entries`.`uuid` and `tag` = ?)',
        ['Auth:992fc05b-f93c-4b6b-afcb-6fa5b856caf2'],
    ],
    'Auth UUID prefix' => [
        '992fc05b',
        'and exists (select 1 from `telescope_entries_tags` where `telescope_entries_tags`.`entry_uuid` = `telescope_entries`.`uuid` and `tag` LIKE ?)',
        ['Auth:992fc05b%'],
    ],
    'email' => [
        'tester@tester.sk',
        'and json_unquote(json_extract(`content`, \'$."user"."email"\')) LIKE ?',
        ['%tester@tester.sk%'],
    ],
    'other text' => [
        'tester',
        'and (json_unquote(json_extract(`content`, \'$."user"."email"\')) LIKE ? or json_unquote(json_extract(`content`, \'$."user"."id"\')) LIKE ?)',
        ['%tester%', '%tester%'],
    ],
    'whitespace only' => [
        '   ',
        'and `should_display_on_index` = ?',
        [],
    ],
]);

it('summarizes the authenticated user of requests and client requests', function () {
    $service = new class extends TelescopeQueryService
    {
        public function summary(string $type, ?array $content): array
        {
            return $this->summarizeContent($type, $content);
        }
    };

    $content = [
        'method' => 'GET',
        'uri' => 'https://example.test/api/v1/pods',
        'response_status' => 200,
        'duration' => 12,
        'user' => ['id' => '992fc05b-f93c-4b6b-afcb-6fa5b856caf2', 'name' => null, 'email' => 'tester@tester.sk'],
    ];
    $withoutEmail = ['user' => ['id' => 'abc', 'name' => 'WAME Tester']] + $content;
    $withoutUser = array_diff_key($content, ['user' => true]);

    foreach (['request', 'client_request'] as $type) {
        expect($service->summary($type, $content)['user'])
            ->toBe(['email' => 'tester@tester.sk', 'id' => '992fc05b-f93c-4b6b-afcb-6fa5b856caf2'])
            ->and($service->summary($type, $withoutEmail)['user'])->toBe(['email' => 'WAME Tester', 'id' => 'abc'])
            ->and($service->summary($type, $withoutUser)['user'])->toBeNull();
    }
});
