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
     */
    'api_types' => [
        'provider' => ConfigApiTypeProvider::class,
        'config_key' => 'telescope-api-types',
    ],
];
