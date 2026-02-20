<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Wame\User\Models\User;

function telescopeDbAvailable(): bool
{
    try {
        DB::connection(config('wame-telescope-dashboard.connection', 'mysql_telescope'))->getPdo();

        return true;
    } catch (\Exception) {
        return false;
    }
}

function createAuthorizedUser(): Authenticatable
{
    $user = User::factory()->create();
    Gate::define('viewTelescope', fn () => true);

    return $user;
}

function createUnauthorizedUser(): Authenticatable
{
    $user = User::factory()->create();
    Gate::define('viewTelescope', fn () => false);

    return $user;
}

it('redirects unauthenticated users', function () {
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->get("/{$path}")
        ->assertRedirect();
});

it('denies access to unauthorized users', function () {
    $user = createUnauthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->get("/{$path}")
        ->assertForbidden();
});

it('allows access to authorized users', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->get("/{$path}")
        ->assertOk()
        ->assertViewIs('telescope-dashboard::dashboard');
});

it('returns JSON for entries API', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", ['type' => 'request'])
        ->assertOk()
        ->assertJsonStructure(['entries', 'has_more', 'next_cursor']);
})->skip(fn () => ! telescopeDbAvailable(), 'Telescope DB connection not available');

it('validates type is required for entries API', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('validates type must be valid', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", ['type' => 'invalid_type'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('returns 404 for non-existent entry detail', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->getJson("/{$path}/api/entries/non-existent-uuid")
        ->assertNotFound();
})->skip(fn () => ! telescopeDbAvailable(), 'Telescope DB connection not available');

it('returns filter values for a type', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->getJson("/{$path}/api/filters/request")
        ->assertOk()
        ->assertJsonStructure(['methods', 'route_groups', 'status_groups']);
});

it('accepts valid request filters', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", [
            'type' => 'request',
            'methods' => ['GET', 'POST'],
            'uri' => '/api/v1',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'min_duration' => 100,
            'per_page' => 25,
        ])
        ->assertOk()
        ->assertJsonStructure(['entries', 'has_more', 'next_cursor']);
})->skip(fn () => ! telescopeDbAvailable(), 'Telescope DB connection not available');

it('accepts valid query filters', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", [
            'type' => 'query',
            'slow_query' => true,
            'query_type' => 'select',
        ])
        ->assertOk();
})->skip(fn () => ! telescopeDbAvailable(), 'Telescope DB connection not available');

it('accepts valid log filters', function () {
    $user = createAuthorizedUser();
    $path = config('wame-telescope-dashboard.path', 'telescope-dashboard');

    $this->actingAs($user)
        ->postJson("/{$path}/api/entries", [
            'type' => 'log',
            'log_level' => 'error',
            'content' => 'search term',
        ])
        ->assertOk();
})->skip(fn () => ! telescopeDbAvailable(), 'Telescope DB connection not available');
