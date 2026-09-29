<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Contracts;

interface ApiTypeProvider
{
    /**
     * Get the API type map for the given Telescope entry type.
     *
     * Keys are "METHOD mask" (the method is optional), values are display names.
     * Several masks may share one name to group them. Order matters: the first
     * matching mask wins.
     *
     * @param  string  $entryType  Telescope entry type, e.g. "request" or "client_request".
     * @return array<string, string>
     */
    public function map(string $entryType): array;
}
