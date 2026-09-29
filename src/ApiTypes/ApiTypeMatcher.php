<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\ApiTypes;

use Illuminate\Database\Query\Builder;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

/**
 * Resolves the "API type" of request / client request entries from a map of URL masks.
 *
 * The same compiled rules drive the list column (PHP preg_match) and the filter
 * (SQL REGEXP on the c_method / c_uri virtual columns), so both always agree:
 * the first matching mask wins.
 */
class ApiTypeMatcher
{
    /**
     * Filter value selecting entries that match no mask at all.
     */
    public const UNMATCHED = '__unmatched__';

    /**
     * Length of the c_uri virtual column; resolve() mirrors its truncation.
     */
    protected const URI_COLUMN_LENGTH = 500;

    protected const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * @var array<string, list<array{method: ?string, regex: string, name: string}>>
     */
    protected array $rules = [];

    public function __construct(protected ApiTypeProvider $provider) {}

    /**
     * Get the name of the first mask matching the entry, or null when none matches.
     */
    public function resolve(string $entryType, ?string $method, ?string $uri): ?string
    {
        if ($uri === null || $uri === '') {
            return null;
        }

        $uri = mb_substr($uri, 0, static::URI_COLUMN_LENGTH);

        foreach ($this->rules($entryType) as $rule) {
            if ($rule['method'] !== null && strtoupper((string) $method) !== $rule['method']) {
                continue;
            }

            if (preg_match('#'.$rule['regex'].'#i', $uri) === 1) {
                return $rule['name'];
            }
        }

        return null;
    }

    /**
     * Get the distinct API type names sorted alphabetically (case-insensitive) for the filter.
     *
     * @return list<string>
     */
    public function names(string $entryType): array
    {
        $names = array_values(array_unique(array_column($this->rules($entryType), 'name')));

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * Restrict the query to entries resolved to the given name (or to UNMATCHED).
     */
    public function applyFilter(Builder $query, string $entryType, string $name): void
    {
        $rules = $this->rules($entryType);

        if ($name === static::UNMATCHED) {
            if ($rules !== []) {
                $query->whereNot(fn (Builder $any) => $this->whereAnyRule($any, $rules));
            }

            return;
        }

        if (! in_array($name, array_column($rules, 'name'), true)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $candidates) use ($rules, $name) {
            foreach ($rules as $index => $rule) {
                if ($rule['name'] !== $name) {
                    continue;
                }

                // Earlier masks of other names win over this one (first match wins).
                $shadowing = array_values(array_filter(
                    array_slice($rules, 0, $index),
                    fn (array $earlier) => $earlier['name'] !== $name
                        && ($earlier['method'] === null || $rule['method'] === null || $earlier['method'] === $rule['method'])
                ));

                $candidates->orWhere(function (Builder $candidate) use ($rule, $shadowing) {
                    $this->whereAnyRule($candidate, [$rule]);

                    if ($shadowing !== []) {
                        $candidate->whereNot(fn (Builder $earlier) => $this->whereAnyRule($earlier, $shadowing));
                    }
                });
            }
        });
    }

    /**
     * Split a map key into its optional HTTP method and the URL mask.
     *
     * @return array{0: ?string, 1: string}
     */
    public function parseKey(string $key): array
    {
        $parts = preg_split('/\s+/', trim($key), 2);

        if (count($parts) === 2 && in_array(strtoupper($parts[0]), static::METHODS, true)) {
            return [strtoupper($parts[0]), trim($parts[1])];
        }

        return [null, trim($key)];
    }

    /**
     * Compile a URL mask into a regex body (no delimiters, no flags).
     *
     * The result must be valid both for PHP preg_match (wrapped in "#…#i")
     * and for SQL REGEXP on MySQL 8 (ICU) and MariaDB (PCRE):
     * - `{param}` matches exactly one path segment,
     * - `*` matches anything up to the query string,
     * - the mask is anchored at both ends,
     * - without `?` in the mask the query string is ignored,
     * - with `?` the query is matched too and `*` there matches anything.
     *
     * Examples:
     *   "/api/v1/point/{pod}/check" matches "/api/v1/point/123/check?x=1",
     *   a leading "*" in front of "/v1/pods" matches any scheme and host ("https://b7.test/v1/pods"),
     *   "/v1/pods/{pod}/meters?meterSN=*" (with the leading "*") matches ".../v1/pods/1/meters?meterSN=9".
     */
    public function compileMask(string $mask): string
    {
        [$path, $query] = array_pad(explode('?', $mask, 2), 2, null);

        $regex = '^';

        foreach (preg_split('/(\{[^}]+\}|\*)/', $path, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $regex .= match (true) {
                $part === '*' => '[^?]*',
                $part[0] === '{' => '[^/?]+',
                default => preg_quote($part, '#'),
            };
        }

        if ($query === null) {
            return $regex.'(?:\?.*)?$';
        }

        return $regex.'\?'.implode('.*', array_map(fn (string $literal) => preg_quote($literal, '#'), explode('*', $query))).'$';
    }

    /**
     * Add a condition that is true when the entry matches any of the given rules.
     *
     * Rules are grouped by HTTP method so each row needs at most one REGEXP per method.
     *
     * @param  list<array{method: ?string, regex: string, name: string}>  $rules
     */
    protected function whereAnyRule(Builder $query, array $rules): void
    {
        $patternsByMethod = [];

        foreach ($rules as $rule) {
            $patternsByMethod[$rule['method'] ?? ''][] = '(?:'.$rule['regex'].')';
        }

        $query->where(function (Builder $any) use ($patternsByMethod) {
            foreach ($patternsByMethod as $method => $patterns) {
                $any->orWhere(function (Builder $group) use ($method, $patterns) {
                    if ($method !== '') {
                        $group->where('c_method', $method);
                    }

                    $group->where('c_uri', 'REGEXP', implode('|', $patterns));
                });
            }
        });
    }

    /**
     * @return list<array{method: ?string, regex: string, name: string}>
     */
    protected function rules(string $entryType): array
    {
        if (isset($this->rules[$entryType])) {
            return $this->rules[$entryType];
        }

        $rules = [];

        foreach ($this->provider->map($entryType) as $key => $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            [$method, $mask] = $this->parseKey((string) $key);

            if ($mask === '') {
                continue;
            }

            $rules[] = ['method' => $method, 'regex' => $this->compileMask($mask), 'name' => $name];
        }

        return $this->rules[$entryType] = $rules;
    }
}
