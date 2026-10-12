<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\ApiTypes;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Telescope\Telescope;
use Throwable;
use Wame\LaravelTelescopeDashboard\Contracts\ApiTypeProvider;

/**
 * Keeps the stored telescope_entries.api_type column in line with the API type map.
 *
 * New entries are written right after Telescope stores them (afterStoring()),
 * existing ones are recomputed by sync() once the map fingerprint changes.
 * Both use ApiTypeMatcher::resolve(), so the stored value always equals what
 * the matcher returns for the current map.
 */
class ApiTypeColumn
{
    public const COLUMN = 'api_type';

    /**
     * Telescope entry types that carry an API type.
     */
    public const ENTRY_TYPES = ['request', 'client_request'];

    public const DEFAULT_CHUNK_SIZE = 1000;

    protected const TABLE = 'telescope_entries';

    protected const FINGERPRINT_CACHE_KEY = 'wame-telescope-dashboard:api-types:fingerprint';

    /**
     * Bump when the way names are resolved changes, so stored values get recomputed.
     */
    protected const RESOLVER_VERSION = 1;

    public function __construct(
        protected ApiTypeMatcher $matcher,
        protected ApiTypeProvider $provider,
    ) {}

    /**
     * Telescope "afterStoring" hook: store the API types of the batch.
     *
     * Always returns true — Telescope runs the hooks through `every()`, so a falsy
     * result (or an exception) would skip every hook registered after this one.
     *
     * @param  array<int, object>  $entries  Telescope IncomingEntry objects.
     */
    public function afterStoring(array $entries): bool
    {
        try {
            $this->record($entries);
        } catch (Throwable $e) {
            report($e);
        }

        return true;
    }

    /**
     * Write the API type of the given stored entries with a single UPDATE.
     *
     * @param  array<int, object>  $entries  Telescope IncomingEntry objects.
     */
    public function record(array $entries): void
    {
        $names = $this->namesFor($entries);

        if ($names === []) {
            return;
        }

        $connection = $this->connection();
        $grammar = $connection->getQueryGrammar();
        $uuid = $grammar->wrap('uuid');

        $cases = [];
        $bindings = [];

        foreach ($names as $entryUuid => $name) {
            $cases[] = 'when ? then ?';
            $bindings[] = (string) $entryUuid;
            $bindings[] = $name;
        }

        $sql = sprintf(
            'update %s set %s = case %s %s end where %s in (%s)',
            $grammar->wrapTable(static::TABLE),
            $grammar->wrap(static::COLUMN),
            $uuid,
            implode(' ', $cases),
            $uuid,
            implode(', ', array_fill(0, count($names), '?')),
        );

        $connection->update($sql, [...$bindings, ...array_map('strval', array_keys($names))]);
    }

    /**
     * Resolve the API type of every request / client request entry that matches a mask.
     *
     * Telescope's database repository replaces the content with its JSON string
     * while storing, so both the array and the encoded form are accepted.
     *
     * @param  iterable<object>  $entries  Telescope IncomingEntry objects.
     * @return array<string, string> uuid => API type name
     */
    public function namesFor(iterable $entries): array
    {
        if (! $this->hasMap()) {
            return [];
        }

        $names = [];

        foreach ($entries as $entry) {
            $type = $entry->type ?? null;

            if (! in_array($type, static::ENTRY_TYPES, true) || empty($entry->uuid)) {
                continue;
            }

            $content = is_string($entry->content) ? json_decode($entry->content, true) : $entry->content;

            if (! is_array($content) || ! is_string($content['uri'] ?? null)) {
                continue;
            }

            $method = is_string($content['method'] ?? null) ? $content['method'] : null;
            $name = $this->matcher->resolve($type, $method, $content['uri']);

            if ($name !== null) {
                $names[(string) $entry->uuid] = $name;
            }
        }

        return $names;
    }

    /**
     * Recompute the stored API type of every request and client request entry.
     *
     * Reads only the cheap columns, walks each (type, should_display_on_index)
     * range of Telescope's own index in primary key order and writes only the
     * rows whose value changed, so every chunk is a short transaction.
     *
     * @return int Number of entries whose API type changed.
     */
    public function sync(?int $chunkSize = null): int
    {
        $chunkSize = max(1, $chunkSize ?? static::DEFAULT_CHUNK_SIZE);
        $fingerprint = $this->fingerprint();

        $changed = Telescope::withoutRecording(function () use ($chunkSize): int {
            $changed = 0;

            foreach (static::ENTRY_TYPES as $type) {
                foreach ([true, false] as $displayOnIndex) {
                    $this->table()
                        ->select(['sequence', 'c_method', 'c_uri', static::COLUMN])
                        ->where('type', $type)
                        ->where('should_display_on_index', $displayOnIndex)
                        ->chunkById($chunkSize, function (Collection $rows) use ($type, &$changed): void {
                            $changed += $this->syncRows($type, $rows);
                        }, 'sequence');
                }
            }

            return $changed;
        });

        $this->cache()->forever(static::FINGERPRINT_CACHE_KEY, $fingerprint);

        return $changed;
    }

    /**
     * Run sync() only when the map changed since the last sync and the column exists.
     *
     * @return int|null Number of changed entries, or null when nothing had to run.
     */
    public function syncIfStale(?int $chunkSize = null): ?int
    {
        if (! $this->isStale() || ! $this->columnExists()) {
            return null;
        }

        return $this->sync($chunkSize);
    }

    /**
     * Whether the map changed since the stored values were last recomputed.
     */
    public function isStale(): bool
    {
        return $this->cache()->get(static::FINGERPRINT_CACHE_KEY) !== $this->fingerprint();
    }

    public function columnExists(): bool
    {
        return Schema::connection($this->connectionName())->hasColumn(static::TABLE, static::COLUMN);
    }

    /**
     * Hash of every map the stored values are derived from.
     */
    public function fingerprint(): string
    {
        $maps = [];

        foreach (static::ENTRY_TYPES as $type) {
            $maps[$type] = $this->provider->map($type);
        }

        return sha1(static::RESOLVER_VERSION.'|'.json_encode($maps));
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    protected function syncRows(string $type, Collection $rows): int
    {
        $byName = [];
        $toNull = [];

        foreach ($rows as $row) {
            $name = $this->matcher->resolve($type, $row->c_method, $row->c_uri);

            if ($name === $row->{static::COLUMN}) {
                continue;
            }

            if ($name === null) {
                $toNull[] = $row->sequence;
            } else {
                $byName[$name][] = $row->sequence;
            }
        }

        $changed = 0;

        foreach ($byName as $name => $sequences) {
            $changed += $this->table()->whereIn('sequence', $sequences)->update([static::COLUMN => (string) $name]);
        }

        if ($toNull !== []) {
            $changed += $this->table()->whereIn('sequence', $toNull)->update([static::COLUMN => null]);
        }

        return $changed;
    }

    protected function hasMap(): bool
    {
        foreach (static::ENTRY_TYPES as $type) {
            if ($this->matcher->names($type) !== []) {
                return true;
            }
        }

        return false;
    }

    protected function table(): Builder
    {
        return $this->connection()->table(static::TABLE);
    }

    protected function connection(): Connection
    {
        return DB::connection($this->connectionName());
    }

    /**
     * The connection Telescope stores its entries on (same as the package migrations).
     */
    protected function connectionName(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    protected function cache(): CacheRepository
    {
        return Cache::store(config('wame-telescope-dashboard.api_types.cache_store'));
    }
}
