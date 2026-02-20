<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TelescopeQueryService
{
    protected string $connection;

    protected int $perPage;

    public function __construct()
    {
        $this->connection = config('wame-telescope-dashboard.connection', 'mysql_telescope');
        $this->perPage = config('wame-telescope-dashboard.per_page', 50);
    }

    public function search(array $filters): array
    {
        $perPage = min(
            $filters['per_page'] ?? $this->perPage,
            config('wame-telescope-dashboard.max_per_page', 200)
        );

        $sortBy = $filters['sort_by'] ?? 'sequence';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $isCustomSort = $sortBy !== 'sequence';

        $query = $this->buildQuery($filters, $isCustomSort);

        $this->applySorting($query, $sortBy, $sortDirection);

        if ($isCustomSort && isset($filters['offset'])) {
            $query->offset((int) $filters['offset']);
        }

        $query->limit($perPage + 1);

        $entries = $query->get();

        $hasMore = $entries->count() > $perPage;
        if ($hasMore) {
            $entries = $entries->slice(0, $perPage);
        }

        $currentOffset = $isCustomSort ? (($filters['offset'] ?? 0) + $entries->count()) : null;

        $results = $entries->map(function ($entry) {
            $content = is_string($entry->content) ? json_decode($entry->content, true) : $entry->content;

            return [
                'uuid' => $entry->uuid,
                'sequence' => $entry->sequence,
                'batch_id' => $entry->batch_id,
                'type' => $entry->type,
                'family_hash' => $entry->family_hash,
                'content' => $this->summarizeContent($entry->type, $content),
                'created_at' => $entry->created_at,
            ];
        })->values();

        return [
            'entries' => $results,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore && ! $isCustomSort ? $entries->last()->sequence : null,
            'total_offset' => $currentOffset,
        ];
    }

    protected function applySorting(Builder $query, string $sortBy, string $sortDirection): void
    {
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        match ($sortBy) {
            'content.duration' => $query->orderBy('c_duration', $direction),
            'content.time' => $query->orderBy('c_time', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->orderBy('sequence', $direction),
        };
    }

    public function find(string $uuid): ?array
    {
        $entry = DB::connection($this->connection)
            ->table('telescope_entries')
            ->where('uuid', $uuid)
            ->first();

        if (! $entry) {
            return null;
        }

        $content = is_string($entry->content) ? json_decode($entry->content, true) : $entry->content;

        $tags = DB::connection($this->connection)
            ->table('telescope_entries_tags')
            ->where('entry_uuid', $uuid)
            ->pluck('tag')
            ->toArray();

        return [
            'uuid' => $entry->uuid,
            'sequence' => $entry->sequence,
            'batch_id' => $entry->batch_id,
            'type' => $entry->type,
            'family_hash' => $entry->family_hash,
            'content' => $content,
            'tags' => $tags,
            'created_at' => $entry->created_at,
        ];
    }

    public function findWithBatch(string $uuid): ?array
    {
        $entry = $this->find($uuid);

        if (! $entry) {
            return null;
        }

        $batch = [];

        if ($entry['batch_id']) {
            $batchEntries = DB::connection($this->connection)
                ->table('telescope_entries')
                ->where('batch_id', $entry['batch_id'])
                ->where('uuid', '!=', $uuid)
                ->orderBy('sequence')
                ->get();

            $batch = $batchEntries->map(function ($batchEntry) {
                $content = is_string($batchEntry->content)
                    ? json_decode($batchEntry->content, true)
                    : $batchEntry->content;

                return [
                    'uuid' => $batchEntry->uuid,
                    'sequence' => $batchEntry->sequence,
                    'type' => $batchEntry->type,
                    'content' => $this->summarizeContent($batchEntry->type, $content),
                    'created_at' => $batchEntry->created_at,
                ];
            })->values()->toArray();
        }

        return [
            'entry' => $entry,
            'batch' => $batch,
        ];
    }

    public function getFilterValues(string $type): array
    {
        $connection = $this->connection;

        return match ($type) {
            'request' => $this->getRequestFilterValues($connection),
            'query' => $this->getQueryFilterValues($connection),
            'job' => $this->getJobFilterValues($connection),
            'log' => $this->getLogFilterValues($connection),
            'cache' => $this->getCacheFilterValues($connection),
            'model' => $this->getModelFilterValues($connection),
            'gate' => $this->getGateFilterValues($connection),
            default => [],
        };
    }

    protected function buildQuery(array $filters, bool $isCustomSort = false): Builder
    {
        $query = DB::connection($this->connection)
            ->table('telescope_entries')
            ->where('type', $filters['type'])
            ->where('should_display_on_index', true);

        if (! $isCustomSort && ! empty($filters['before_sequence'])) {
            $query->where('sequence', '<', $filters['before_sequence']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        if (! empty($filters['content'])) {
            $query->where('content', 'LIKE', '%'.$filters['content'].'%');
        }

        $this->applyTypeSpecificFilters($query, $filters);

        return $query;
    }

    protected function applyTypeSpecificFilters(Builder $query, array $filters): void
    {
        $type = $filters['type'];

        match ($type) {
            'request' => $this->applyRequestFilters($query, $filters),
            'query' => $this->applyQueryFilters($query, $filters),
            'exception' => $this->applyExceptionFilters($query, $filters),
            'job' => $this->applyJobFilters($query, $filters),
            'log' => $this->applyLogFilters($query, $filters),
            'mail' => $this->applyMailFilters($query, $filters),
            'event' => $this->applyEventFilters($query, $filters),
            'cache' => $this->applyCacheFilters($query, $filters),
            'command' => $this->applyCommandFilters($query, $filters),
            'schedule' => $this->applyScheduleFilters($query, $filters),
            'model' => $this->applyModelFilters($query, $filters),
            'gate' => $this->applyGateFilters($query, $filters),
            'notification' => $this->applyNotificationFilters($query, $filters),
            'redis' => $this->applyRedisFilters($query, $filters),
            'client_request' => $this->applyClientRequestFilters($query, $filters),
            'batch' => $this->applyBatchFilters($query, $filters),
            default => null,
        };
    }

    protected function applyRequestFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['methods'])) {
            $query->whereIn('c_method', $filters['methods']);
        }

        if (! empty($filters['uri'])) {
            $query->where('c_uri', 'LIKE', '%'.$filters['uri'].'%');
        }

        if (! empty($filters['statuses'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['statuses'] as $status) {
                    if (str_ends_with($status, 'xx')) {
                        $prefix = (int) substr($status, 0, 1);
                        $q->orWhere(function ($inner) use ($prefix) {
                            $inner->where('c_response_status', '>=', $prefix * 100)
                                ->where('c_response_status', '<', $prefix * 100 + 100);
                        });
                    } else {
                        $q->orWhere('c_response_status', (int) $status);
                    }
                }
            });
        }

        if (! empty($filters['min_duration'])) {
            $query->where('c_duration', '>=', $filters['min_duration']);
        }

        if (! empty($filters['user_email'])) {
            $query->where('content->user->email', 'LIKE', '%'.$filters['user_email'].'%');
        }

        if (! empty($filters['route_group'])) {
            $groups = config('wame-telescope-dashboard.route_groups', []);
            if (isset($groups[$filters['route_group']])) {
                $pattern = str_replace('*', '%', $groups[$filters['route_group']]);
                $query->where('c_uri', 'LIKE', $pattern);
            }
        }
    }

    protected function applyQueryFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['slow_query'])) {
            $query->where('c_time', '>=', 100);
        }

        if (! empty($filters['query_type'])) {
            $prefix = strtoupper($filters['query_type']);
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.sql')) LIKE ?", [$prefix.'%']);
        }

        if (! empty($filters['min_duration'])) {
            $query->where('c_time', '>=', $filters['min_duration']);
        }
    }

    protected function applyExceptionFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['exception_class'])) {
            $query->where('content->class', 'LIKE', '%'.$filters['exception_class'].'%');
        }
    }

    protected function applyJobFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['job_status'])) {
            $query->where('content->status', $filters['job_status']);
        }

        if (! empty($filters['job_name'])) {
            $query->where('content->name', 'LIKE', '%'.$filters['job_name'].'%');
        }
    }

    protected function applyLogFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['log_level'])) {
            $query->where('content->level', $filters['log_level']);
        }
    }

    protected function applyMailFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['mailable'])) {
            $query->where('content->mailable', 'LIKE', '%'.$filters['mailable'].'%');
        }

        if (! empty($filters['mail_to'])) {
            $query->where('content', 'LIKE', '%'.$filters['mail_to'].'%');
        }

        if (! empty($filters['mail_subject'])) {
            $query->where('content->subject', 'LIKE', '%'.$filters['mail_subject'].'%');
        }
    }

    protected function applyEventFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['event_name'])) {
            $query->where('content->name', 'LIKE', '%'.$filters['event_name'].'%');
        }
    }

    protected function applyCacheFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['cache_type'])) {
            $query->where('content->type', $filters['cache_type']);
        }

        if (! empty($filters['cache_key'])) {
            $query->where('content->key', 'LIKE', '%'.$filters['cache_key'].'%');
        }
    }

    protected function applyCommandFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['command_name'])) {
            $query->where('content->command', 'LIKE', '%'.$filters['command_name'].'%');
        }

        if (isset($filters['exit_code']) && $filters['exit_code'] !== null && $filters['exit_code'] !== '') {
            $query->where('content->exit_code', (int) $filters['exit_code']);
        }
    }

    protected function applyScheduleFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['schedule_command'])) {
            $query->where('content->command', 'LIKE', '%'.$filters['schedule_command'].'%');
        }
    }

    protected function applyModelFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['model_action'])) {
            $query->where('content->action', $filters['model_action']);
        }

        if (! empty($filters['model_type'])) {
            $query->where('content->model', 'LIKE', '%'.$filters['model_type'].'%');
        }
    }

    protected function applyGateFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['ability'])) {
            $query->where('content->ability', 'LIKE', '%'.$filters['ability'].'%');
        }

        if (! empty($filters['gate_result'])) {
            $query->where('content->result', $filters['gate_result']);
        }
    }

    protected function applyNotificationFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['notification_channel'])) {
            $query->where('content->channel', $filters['notification_channel']);
        }

        if (! empty($filters['notification_class'])) {
            $query->where('content->notification', 'LIKE', '%'.$filters['notification_class'].'%');
        }
    }

    protected function applyRedisFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['redis_command'])) {
            $query->where('content->command', 'LIKE', '%'.$filters['redis_command'].'%');
        }

        if (! empty($filters['min_duration'])) {
            $query->where('c_time', '>=', $filters['min_duration']);
        }
    }

    protected function applyClientRequestFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['client_method'])) {
            $query->where('c_method', $filters['client_method']);
        }

        if (! empty($filters['client_uri'])) {
            $query->where('c_uri', 'LIKE', '%'.$filters['client_uri'].'%');
        }

        if (! empty($filters['client_status'])) {
            $query->where('c_response_status', (int) $filters['client_status']);
        }

        if (! empty($filters['min_duration'])) {
            $query->where('c_duration', '>=', $filters['min_duration']);
        }
    }

    protected function applyBatchFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['batch_name'])) {
            $query->where('content->name', 'LIKE', '%'.$filters['batch_name'].'%');
        }
    }

    protected function summarizeContent(string $type, ?array $content): array
    {
        if (! $content) {
            return [];
        }

        return match ($type) {
            'request' => [
                'method' => $content['method'] ?? null,
                'uri' => $content['uri'] ?? null,
                'response_status' => $content['response_status'] ?? null,
                'duration' => $content['duration'] ?? null,
                'memory' => $content['memory'] ?? null,
                'controller_action' => $content['controller_action'] ?? null,
                'user' => $content['user']['email'] ?? $content['user']['name'] ?? null,
                'ip_address' => $content['ip_address'] ?? null,
            ],
            'query' => [
                'sql' => $content['sql'] ?? null,
                'time' => $content['time'] ?? null,
                'connection' => $content['connection'] ?? null,
                'file' => $content['file'] ?? null,
                'line' => $content['line'] ?? null,
                'hash' => $content['hash'] ?? null,
            ],
            'exception' => [
                'class' => $content['class'] ?? null,
                'file' => $content['file'] ?? null,
                'line' => $content['line'] ?? null,
                'message' => $content['message'] ?? null,
                'occurrences' => $content['occurrences'] ?? null,
            ],
            'job' => [
                'name' => $content['name'] ?? null,
                'queue' => $content['queue'] ?? null,
                'connection' => $content['connection'] ?? null,
                'status' => $content['status'] ?? null,
                'tries' => $content['tries'] ?? null,
            ],
            'log' => [
                'level' => $content['level'] ?? null,
                'message' => $content['message'] ?? null,
            ],
            'mail' => [
                'mailable' => $content['mailable'] ?? null,
                'to' => $content['to'] ?? null,
                'subject' => $content['subject'] ?? null,
                'queued' => $content['queued'] ?? null,
            ],
            'event' => [
                'name' => $content['name'] ?? null,
                'listeners' => isset($content['listeners']) ? count($content['listeners']) : 0,
                'broadcast' => $content['broadcast'] ?? false,
            ],
            'cache' => [
                'type' => $content['type'] ?? null,
                'key' => $content['key'] ?? null,
                'expiration' => $content['expiration'] ?? null,
            ],
            'command' => [
                'command' => $content['command'] ?? null,
                'arguments' => $content['arguments'] ?? null,
                'exit_code' => $content['exit_code'] ?? null,
                'duration' => $content['duration'] ?? null,
            ],
            'schedule' => [
                'command' => $content['command'] ?? null,
                'expression' => $content['expression'] ?? null,
                'timezone' => $content['timezone'] ?? null,
                'output' => isset($content['output']) ? mb_substr($content['output'], 0, 200) : null,
            ],
            'model' => [
                'action' => $content['action'] ?? null,
                'model' => $content['model'] ?? null,
            ],
            'gate' => [
                'ability' => $content['ability'] ?? null,
                'result' => $content['result'] ?? null,
                'arguments' => $content['arguments'] ?? null,
            ],
            'dump' => [
                'dump' => isset($content['dump']) ? mb_substr($content['dump'], 0, 300) : null,
            ],
            'notification' => [
                'notification' => $content['notification'] ?? null,
                'notifiable' => $content['notifiable'] ?? null,
                'channel' => $content['channel'] ?? null,
                'response' => $content['response'] ?? null,
            ],
            'redis' => [
                'command' => $content['command'] ?? null,
                'time' => $content['time'] ?? null,
            ],
            'client_request' => [
                'method' => $content['method'] ?? null,
                'uri' => $content['uri'] ?? null,
                'response_status' => $content['response_status'] ?? null,
                'duration' => $content['duration'] ?? null,
            ],
            'batch' => [
                'name' => $content['name'] ?? null,
                'total_jobs' => $content['totalJobs'] ?? $content['total_jobs'] ?? null,
                'pending_jobs' => $content['pendingJobs'] ?? $content['pending_jobs'] ?? null,
                'failed_jobs' => $content['failedJobs'] ?? $content['failed_jobs'] ?? null,
            ],
            default => array_slice($content, 0, 10),
        };
    }

    protected function getRequestFilterValues(string $connection): array
    {
        return [
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'route_groups' => array_keys(config('wame-telescope-dashboard.route_groups', [])),
            'status_groups' => ['2xx', '3xx', '4xx', '5xx'],
        ];
    }

    protected function getQueryFilterValues(string $connection): array
    {
        return [
            'query_types' => ['select', 'insert', 'update', 'delete'],
        ];
    }

    protected function getJobFilterValues(string $connection): array
    {
        return [
            'statuses' => ['pending', 'completed', 'failed'],
        ];
    }

    protected function getLogFilterValues(string $connection): array
    {
        return [
            'levels' => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
        ];
    }

    protected function getCacheFilterValues(string $connection): array
    {
        return [
            'types' => ['hit', 'missed', 'set', 'forget'],
        ];
    }

    protected function getModelFilterValues(string $connection): array
    {
        return [
            'actions' => ['created', 'updated', 'deleted'],
        ];
    }

    protected function getGateFilterValues(string $connection): array
    {
        return [
            'results' => ['allowed', 'denied'],
        ];
    }
}
