<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
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
