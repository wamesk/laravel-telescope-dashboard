<?php

declare(strict_types=1);

use Wame\LaravelTelescopeDashboard\ApiTypes\ConfigApiTypeProvider;

return [
    'enabled' => env('TELESCOPE_DASHBOARD_ENABLED', true),
    'path' => env('TELESCOPE_DASHBOARD_PATH', 'telescope-dashboard'),
    'gate' => env('TELESCOPE_DASHBOARD_GATE', 'viewTelescope'),
    'connection' => env('DB_CONNECTION_TELESCOPE', 'mysql_telescope'),
    'per_page' => 50,
    'max_per_page' => 200,
    'route_groups' => [
        'api' => '/api/v*',
        'nova-api' => '/nova-api/*',
        'web' => '/*',
    ],
    'telescope_path' => env('TELESCOPE_PATH', 'telescope'),
    'middleware' => ['web', 'auth'],

    /*
     * "API type" column and filter for Requests and Client Requests.
     *
     * The provider returns a "METHOD mask" => name map per entry type; the default
     * one reads it from the config file named by `config_key`
     * (config/telescope-api-types.php). Without a map the column stays hidden.
     *
     * The resolved name is stored in telescope_entries.api_type: new entries right
     * after Telescope stores them, existing ones by
     * `php artisan telescope-dashboard:sync-api-types`, which is queued
     * automatically after `migrate` whenever the map changed.
     */
    'api_types' => [
        'provider' => ConfigApiTypeProvider::class,
        'config_key' => 'telescope-api-types',

        // Queue a recompute of the stored values after `migrate` when the map changed.
        'sync_after_migrate' => env('TELESCOPE_DASHBOARD_API_TYPES_SYNC_AFTER_MIGRATE', true),

        // Queue connection / name of that job (null = the application defaults).
        'sync_queue' => [
            'connection' => env('TELESCOPE_DASHBOARD_API_TYPES_SYNC_CONNECTION'),
            'queue' => env('TELESCOPE_DASHBOARD_API_TYPES_SYNC_QUEUE'),
        ],

        // Entries read per query while recomputing.
        'sync_chunk' => 1000,

        // Cache store keeping the fingerprint of the last synced map (null = default store).
        'cache_store' => null,
    ],
];
