<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxPerPage = config('wame-telescope-dashboard.max_per_page', 200);

        return [
            'type' => ['required', 'string', 'in:request,query,exception,job,log,mail,event,cache,command,schedule,model,gate,dump,notification,redis,client_request,batch,view'],
            'before_sequence' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$maxPerPage],
            'sort_by' => ['nullable', 'string', 'in:sequence,created_at,content.duration,content.time'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'content' => ['nullable', 'string', 'max:500'],
            // Request-specific
            'methods' => ['nullable', 'array'],
            'methods.*' => ['string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'uri' => ['nullable', 'string', 'max:500'],
            'route_group' => ['nullable', 'string'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string'],
            'min_duration' => ['nullable', 'numeric', 'min:0'],
            'user_email' => ['nullable', 'string', 'max:255'],
            // Query-specific
            'slow_query' => ['nullable', 'boolean'],
            'query_type' => ['nullable', 'string', 'in:select,insert,update,delete'],
            // Exception-specific
            'exception_class' => ['nullable', 'string', 'max:255'],
            // Job-specific
            'job_status' => ['nullable', 'string', 'in:pending,completed,failed'],
            'job_name' => ['nullable', 'string', 'max:255'],
            // Log-specific
            'log_level' => ['nullable', 'string'],
            // Model-specific
            'model_action' => ['nullable', 'string', 'in:created,updated,deleted'],
            'model_type' => ['nullable', 'string', 'max:255'],
            // Mail-specific
            'mailable' => ['nullable', 'string', 'max:255'],
            'mail_to' => ['nullable', 'string', 'max:255'],
            'mail_subject' => ['nullable', 'string', 'max:255'],
            // Command-specific
            'command_name' => ['nullable', 'string', 'max:255'],
            'exit_code' => ['nullable', 'integer'],
            // Event-specific
            'event_name' => ['nullable', 'string', 'max:255'],
            // Cache-specific
            'cache_type' => ['nullable', 'string', 'in:hit,missed,set,forget'],
            'cache_key' => ['nullable', 'string', 'max:255'],
            // Gate-specific
            'ability' => ['nullable', 'string', 'max:255'],
            'gate_result' => ['nullable', 'string', 'in:allowed,denied'],
            // Schedule-specific
            'schedule_command' => ['nullable', 'string', 'max:255'],
            // Notification-specific
            'notification_channel' => ['nullable', 'string', 'max:255'],
            'notification_class' => ['nullable', 'string', 'max:255'],
            // Redis-specific
            'redis_command' => ['nullable', 'string', 'max:255'],
            // Client request-specific
            'client_method' => ['nullable', 'string'],
            'client_uri' => ['nullable', 'string', 'max:500'],
            'client_status' => ['nullable', 'string'],
            // Batch-specific
            'batch_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
