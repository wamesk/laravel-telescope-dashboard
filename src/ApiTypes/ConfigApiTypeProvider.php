<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\ApiTypes;

use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

/**
 * Reads the API type map from a project config file.
 *
 * The file is selected by `wame-telescope-dashboard.api_types.config_key`
 * (default `telescope-api-types`, i.e. `config/telescope-api-types.php`)
 * and holds one map per entry type: `['request' => [...], 'client_request' => [...]]`.
 */
class ConfigApiTypeProvider implements ApiTypeProvider
{
    public function map(string $entryType): array
    {
        $configKey = config('wame-telescope-dashboard.api_types.config_key', 'telescope-api-types');
        $map = config($configKey.'.'.$entryType, []);

        return is_array($map) ? $map : [];
    }
}
