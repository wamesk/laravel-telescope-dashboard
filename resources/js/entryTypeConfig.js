/**
 * Centralized configuration for all Telescope entry types.
 * Maps between DB types, URL route segments, labels, and display config.
 */

const typeConfig = {
    request: {
        label: 'Requests',
        singularLabel: 'Request',
        routeSegment: 'requests',
        summaryFields: ['method', 'uri', 'response_status', 'duration', 'memory', 'controller_action', 'middleware', 'hostname', 'user', 'ip_address'],
        tabs: [
            { key: 'content', label: 'Overview' },
            { key: 'headers', label: 'Headers' },
            { key: 'payload', label: 'Payload' },
            { key: 'response', label: 'Response' },
        ],
        titleFn: (content) => {
            const method = content?.method || '';
            const uri = content?.uri || '';
            const status = content?.response_status || '';
            return `${method} ${uri} → ${status}`;
        },
    },
    query: {
        label: 'Queries',
        singularLabel: 'Query',
        routeSegment: 'queries',
        summaryFields: ['sql', 'time', 'connection', 'file', 'line'],
        tabs: [
            { key: 'content', label: 'Content' },
            { key: 'bindings', label: 'Bindings' },
        ],
        titleFn: (content) => {
            const sql = content?.sql || '';
            return sql.length > 80 ? sql.substring(0, 80) + '...' : sql;
        },
    },
    exception: {
        label: 'Exceptions',
        singularLabel: 'Exception',
        routeSegment: 'exceptions',
        summaryFields: ['class', 'file', 'line', 'message', 'occurrences'],
        tabs: [
            { key: 'content', label: 'Content' },
            { key: 'trace', label: 'Stack Trace' },
        ],
        titleFn: (content) => content?.class || 'Exception',
    },
    job: {
        label: 'Jobs',
        singularLabel: 'Job',
        routeSegment: 'jobs',
        summaryFields: ['name', 'queue', 'connection', 'status', 'tries'],
        tabs: [
            { key: 'content', label: 'Content' },
            { key: 'data', label: 'Data' },
        ],
        titleFn: (content) => content?.name || 'Job',
    },
    log: {
        label: 'Logs',
        singularLabel: 'Log',
        routeSegment: 'logs',
        summaryFields: ['level', 'message'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => `[${(content?.level || 'log').toUpperCase()}] ${(content?.message || '').substring(0, 80)}`,
    },
    mail: {
        label: 'Mail',
        singularLabel: 'Mail',
        routeSegment: 'mail',
        summaryFields: ['mailable', 'to', 'subject', 'queued'],
        tabs: [
            { key: 'content', label: 'Content' },
            { key: 'data', label: 'Data' },
        ],
        titleFn: (content) => content?.subject || content?.mailable || 'Mail',
    },
    event: {
        label: 'Events',
        singularLabel: 'Event',
        routeSegment: 'events',
        summaryFields: ['name', 'listeners', 'broadcast'],
        tabs: [
            { key: 'content', label: 'Content' },
            { key: 'listeners', label: 'Listeners' },
        ],
        titleFn: (content) => content?.name || 'Event',
    },
    cache: {
        label: 'Cache',
        singularLabel: 'Cache',
        routeSegment: 'cache',
        summaryFields: ['type', 'key', 'expiration'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => `${(content?.type || 'cache').toUpperCase()} ${content?.key || ''}`,
    },
    command: {
        label: 'Commands',
        singularLabel: 'Command',
        routeSegment: 'commands',
        summaryFields: ['command', 'arguments', 'exit_code', 'duration'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => content?.command || 'Command',
    },
    schedule: {
        label: 'Schedule',
        singularLabel: 'Schedule',
        routeSegment: 'schedule',
        summaryFields: ['command', 'expression', 'timezone', 'output'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => content?.command || 'Schedule',
    },
    model: {
        label: 'Models',
        singularLabel: 'Model',
        routeSegment: 'models',
        summaryFields: ['action', 'model'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => `${content?.action || ''} ${content?.model || 'Model'}`,
    },
    gate: {
        label: 'Gates',
        singularLabel: 'Gate',
        routeSegment: 'gates',
        summaryFields: ['ability', 'result', 'arguments'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => `${content?.ability || 'Gate'} → ${content?.result || ''}`,
    },
    dump: {
        label: 'Dumps',
        singularLabel: 'Dump',
        routeSegment: 'dumps',
        summaryFields: ['dump'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: () => 'Dump',
    },
    notification: {
        label: 'Notifications',
        singularLabel: 'Notification',
        routeSegment: 'notifications',
        summaryFields: ['notification', 'notifiable', 'channel', 'response'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => content?.notification || 'Notification',
    },
    redis: {
        label: 'Redis',
        singularLabel: 'Redis',
        routeSegment: 'redis',
        summaryFields: ['command', 'time'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => content?.command || 'Redis',
    },
    client_request: {
        label: 'Client Requests',
        singularLabel: 'Client Request',
        routeSegment: 'client-requests',
        summaryFields: ['method', 'uri', 'response_status', 'duration'],
        tabs: [
            { key: 'content', label: 'Overview' },
            { key: 'headers', label: 'Headers' },
            { key: 'response', label: 'Response' },
        ],
        titleFn: (content) => {
            const method = content?.method || '';
            const uri = content?.uri || '';
            const status = content?.response_status || '';
            return `${method} ${uri} → ${status}`;
        },
    },
    batch: {
        label: 'Batches',
        singularLabel: 'Batch',
        routeSegment: 'batches',
        summaryFields: ['name', 'total_jobs', 'pending_jobs', 'failed_jobs'],
        tabs: [{ key: 'content', label: 'Content' }],
        titleFn: (content) => content?.name || 'Batch',
    },
};

// Build reverse lookup: route segment -> db type
const segmentToTypeMap = {};
for (const [dbType, config] of Object.entries(typeConfig)) {
    segmentToTypeMap[config.routeSegment] = dbType;
}

export function getTypeConfig(dbType) {
    return typeConfig[dbType] || null;
}

export function typeToRouteSegment(dbType) {
    return typeConfig[dbType]?.routeSegment || dbType;
}

export function routeSegmentToType(segment) {
    return segmentToTypeMap[segment] || segment;
}

export function getDetailRoute(dbType, uuid) {
    const segment = typeToRouteSegment(dbType);
    return `/${segment}/${uuid}`;
}

export function getEntryTitle(dbType, content) {
    const config = typeConfig[dbType];
    if (config?.titleFn) {
        return config.titleFn(content);
    }
    return dbType;
}

export { typeConfig };
