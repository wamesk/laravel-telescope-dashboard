<template>
    <div class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border p-6 mb-6">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
            <router-link
                :to="'/' + routeSegment"
                class="hover:text-telescope-accent transition-colors"
            >
                {{ typeLabel }}
            </router-link>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-mono text-xs">{{ entry.uuid }}</span>
        </div>

        <!-- Title -->
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-4 break-all">
            {{ title }}
        </h1>

        <!-- Summary fields vertical list -->
        <dl v-if="summaryItems.length" class="space-y-2 mb-4">
            <div v-for="item in summaryItems" :key="item.key" class="flex items-baseline gap-3">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wider w-40 flex-shrink-0">{{ item.key }}</dt>
                <dd v-if="item.badgeType" class="break-all">
                    <Badge :label="item.rawValue" :type="item.badgeType" />
                </dd>
                <dd v-else class="text-sm text-gray-900 dark:text-gray-100 break-all">{{ item.value }}</dd>
            </div>
        </dl>

        <!-- Tags -->
        <div v-if="entry.tags && entry.tags.length" class="flex flex-wrap gap-1 mb-4">
            <span
                v-for="tag in entry.tags"
                :key="tag"
                class="inline-block px-2 py-0.5 text-xs bg-gray-200 dark:bg-telescope-border rounded text-gray-600 dark:text-gray-300"
            >
                {{ tag }}
            </span>
        </div>

        <!-- Metadata row -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-telescope-border">
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="entry.batch_id" class="font-mono">Batch: {{ entry.batch_id }}</span>
                <span v-if="entry.created_at">{{ new Date(entry.created_at).toLocaleString() }}</span>
            </div>
            <div class="flex items-center gap-3">
                <button
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                    @click="copyUuid"
                >
                    {{ copied ? 'Copied!' : 'Copy UUID' }}
                </button>
                <a
                    :href="telescopeUrl"
                    target="_blank"
                    class="text-xs text-telescope-accent hover:text-telescope-accent-light transition-colors"
                >
                    View in Telescope &rarr;
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { getTypeConfig, typeToRouteSegment, getEntryTitle } from '../../entryTypeConfig';
import { getConfig } from '../../api';
import Badge from './Badge.vue';

const props = defineProps({
    entry: { type: Object, required: true },
    dbType: { type: String, required: true },
});

const config = getConfig();
const copied = ref(false);

const typeConf = computed(() => getTypeConfig(props.dbType));
const routeSegment = computed(() => typeToRouteSegment(props.dbType));
const typeLabel = computed(() => typeConf.value?.label || props.dbType);

const title = computed(() => getEntryTitle(props.dbType, props.entry.content));

const fieldLabels = {
    method: 'Method',
    uri: 'Path',
    response_status: 'Status',
    duration: 'Duration',
    memory: 'Memory usage',
    controller_action: 'Controller Action',
    ip_address: 'IP Address',
    hostname: 'Hostname',
    middleware: 'Middleware',
    sql: 'SQL',
    time: 'Time',
    connection: 'Connection',
    file: 'File',
    line: 'Line',
    class: 'Class',
    message: 'Message',
    occurrences: 'Occurrences',
    name: 'Name',
    queue: 'Queue',
    status: 'Status',
    tries: 'Tries',
    level: 'Level',
    mailable: 'Mailable',
    to: 'To',
    subject: 'Subject',
    queued: 'Queued',
    listeners: 'Listeners',
    broadcast: 'Broadcast',
    type: 'Type',
    key: 'Key',
    expiration: 'Expiration',
    command: 'Command',
    arguments: 'Arguments',
    exit_code: 'Exit Code',
    expression: 'Expression',
    timezone: 'Timezone',
    output: 'Output',
    action: 'Action',
    model: 'Model',
    ability: 'Ability',
    result: 'Result',
    dump: 'Dump',
    notification: 'Notification',
    notifiable: 'Notifiable',
    channel: 'Channel',
    response: 'Response',
    total_jobs: 'Total Jobs',
    pending_jobs: 'Pending Jobs',
    failed_jobs: 'Failed Jobs',
};

const badgeFields = {
    method: 'method',
    response_status: 'status',
    status: 'job-status',
    level: 'level',
    type: 'cache-type',
    result: 'gate-result',
    action: 'model-action',
    exit_code: 'exit-code',
};

const summaryItems = computed(() => {
    if (!typeConf.value?.summaryFields || !props.entry.content) return [];
    return typeConf.value.summaryFields
        .filter(key => key !== 'user' && props.entry.content[key] !== null && props.entry.content[key] !== undefined)
        .map(key => ({
            key: fieldLabels[key] || key,
            value: formatSummaryValue(key, props.entry.content[key]),
            rawValue: props.entry.content[key],
            badgeType: badgeFields[key] || null,
        }));
});

const telescopeUrl = computed(() => {
    const basePath = config.telescopePath || '/telescope';
    return `${basePath}/${props.dbType}/${props.entry.uuid}`;
});

const fieldUnits = {
    duration: 'ms',
    memory: 'MB',
    time: 'ms',
};

function formatSummaryValue(key, value) {
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'object') return JSON.stringify(value);
    const unit = fieldUnits[key];
    return unit ? `${value} ${unit}` : String(value);
}

function copyUuid() {
    navigator.clipboard.writeText(props.entry.uuid).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    });
}
</script>
