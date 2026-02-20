<template>
    <div v-if="sortedTypes.length" class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border overflow-hidden mt-6">
        <!-- Type tabs -->
        <div class="flex border-b border-gray-200 dark:border-telescope-border px-4 gap-1 overflow-x-auto">
            <button
                v-for="type in sortedTypes"
                :key="type"
                class="px-3 py-2 text-sm rounded-t-md transition-colors whitespace-nowrap flex items-center gap-2"
                :class="activeType === type
                    ? 'text-gray-900 dark:text-white bg-gray-50 dark:bg-telescope-dark border border-gray-200 dark:border-telescope-border border-b-white dark:border-b-telescope-dark -mb-px'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                @click="activeType = type"
            >
                {{ getTypeLabel(type) }}
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-telescope-border text-gray-600 dark:text-gray-300">
                    {{ batchByType[type]?.length || 0 }}
                </span>
            </button>
        </div>

        <!-- Entries table for active type -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-telescope-dark">
                    <tr>
                        <th
                            v-for="col in activeColumns"
                            :key="col.key"
                            class="table-header"
                            :style="col.width ? { width: col.width } : {}"
                        >
                            {{ col.label }}
                        </th>
                        <th class="table-header w-20">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in visibleEntries"
                        :key="item.uuid"
                        class="border-t border-gray-200 dark:border-telescope-border hover:bg-gray-50 dark:hover:bg-telescope-dark/50 cursor-pointer transition-colors"
                        @click="navigateToDetail(item)"
                    >
                        <td
                            v-for="col in activeColumns"
                            :key="col.key"
                            class="table-cell"
                        >
                            <component
                                v-if="col.badge"
                                :is="Badge"
                                :label="getNestedValue(item, col.key)"
                                :type="col.badge"
                            />
                            <span v-else class="truncate block max-w-xs" :title="String(getNestedValue(item, col.key) ?? '')">
                                {{ formatValue(getNestedValue(item, col.key), col) }}
                            </span>
                        </td>
                        <td class="table-cell text-xs text-gray-500">
                            {{ formatTime(item.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Show all toggle -->
        <div
            v-if="activeEntries.length > initialLimit"
            class="px-6 py-3 border-t border-gray-200 dark:border-telescope-border text-center"
        >
            <button
                class="text-sm text-telescope-accent hover:text-telescope-accent-light transition-colors"
                @click="showAll = !showAll"
            >
                {{ showAll ? 'Show less' : `Show all ${activeEntries.length} entries` }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { getTypeConfig, getDetailRoute } from '../../entryTypeConfig';
import { getTranslations } from '../../api';
import Badge from './Badge.vue';

const props = defineProps({
    batchByType: { type: Object, required: true },
    batchTypes: { type: Array, required: true },
    batchCount: { type: Number, default: 0 },
});

const router = useRouter();
const t = getTranslations();
const initialLimit = 50;
const showAll = ref(false);

const preferredOrder = ['view', 'query', 'model', 'event', 'job', 'cache', 'dump'];

const sortedTypes = computed(() => {
    const known = props.batchTypes.filter(t => preferredOrder.includes(t));
    const unknown = props.batchTypes.filter(t => !preferredOrder.includes(t));
    known.sort((a, b) => preferredOrder.indexOf(a) - preferredOrder.indexOf(b));
    return [...known, ...unknown];
});

const activeType = ref(sortedTypes.value[0] || '');

function getTypeLabel(dbType) {
    const conf = getTypeConfig(dbType);
    return conf?.label || dbType;
}

const columnsByType = {
    request: [
        { key: 'content.method', label: 'Method', width: '80px', badge: 'method' },
        { key: 'content.uri', label: 'URI' },
        { key: 'content.response_status', label: 'Status', width: '80px', badge: 'status' },
        { key: 'content.duration', label: 'Duration', width: '100px', format: 'duration' },
    ],
    query: [
        { key: 'content.sql', label: 'SQL' },
        { key: 'content.time', label: 'Time', width: '100px', format: 'duration' },
        { key: 'content.connection', label: 'Connection', width: '120px' },
    ],
    exception: [
        { key: 'content.class', label: 'Class' },
        { key: 'content.message', label: 'Message' },
    ],
    job: [
        { key: 'content.name', label: 'Name' },
        { key: 'content.status', label: 'Status', width: '100px', badge: 'job-status' },
        { key: 'content.queue', label: 'Queue', width: '120px' },
    ],
    log: [
        { key: 'content.level', label: 'Level', width: '100px', badge: 'level' },
        { key: 'content.message', label: 'Message' },
    ],
    mail: [
        { key: 'content.mailable', label: 'Mailable' },
        { key: 'content.subject', label: 'Subject' },
    ],
    event: [
        { key: 'content.name', label: 'Event' },
        { key: 'content.listeners', label: 'Listeners', width: '100px' },
    ],
    cache: [
        { key: 'content.type', label: 'Type', width: '80px', badge: 'cache-type' },
        { key: 'content.key', label: 'Key' },
    ],
    command: [
        { key: 'content.command', label: 'Command' },
        { key: 'content.exit_code', label: 'Exit Code', width: '100px' },
    ],
    schedule: [
        { key: 'content.command', label: 'Command' },
        { key: 'content.expression', label: 'Expression', width: '120px' },
    ],
    model: [
        { key: 'content.action', label: 'Action', width: '100px', badge: 'model-action' },
        { key: 'content.model', label: 'Model' },
    ],
    gate: [
        { key: 'content.ability', label: 'Ability' },
        { key: 'content.result', label: 'Result', width: '100px', badge: 'gate-result' },
    ],
    dump: [
        { key: 'content.dump', label: 'Dump' },
    ],
    view: [
        { key: 'content.name', label: 'View' },
        { key: 'content.composer', label: 'Composer', width: '120px' },
    ],
    notification: [
        { key: 'content.notification', label: 'Notification' },
        { key: 'content.channel', label: 'Channel', width: '120px' },
    ],
    redis: [
        { key: 'content.command', label: 'Command' },
        { key: 'content.time', label: 'Time', width: '100px', format: 'duration' },
    ],
    client_request: [
        { key: 'content.method', label: 'Method', width: '80px', badge: 'method' },
        { key: 'content.uri', label: 'URI' },
        { key: 'content.response_status', label: 'Status', width: '80px', badge: 'status' },
        { key: 'content.duration', label: 'Duration', width: '100px', format: 'duration' },
    ],
    batch: [
        { key: 'content.name', label: 'Name' },
        { key: 'content.total_jobs', label: 'Total Jobs', width: '100px' },
    ],
};

const activeColumns = computed(() => columnsByType[activeType.value] || [{ key: 'uuid', label: 'UUID' }]);

const activeEntries = computed(() => props.batchByType[activeType.value] || []);

const visibleEntries = computed(() => {
    if (showAll.value) return activeEntries.value;
    return activeEntries.value.slice(0, initialLimit);
});

function getNestedValue(obj, path) {
    return path.split('.').reduce((acc, key) => acc?.[key], obj);
}

function formatValue(value, col) {
    if (value === null || value === undefined) return '-';
    if (col.format === 'duration') return `${Number(value).toFixed(2)} ms`;
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    const str = String(value);
    return str.length > 80 ? str.substring(0, 80) + '...' : str;
}

function formatTime(value) {
    if (!value) return '-';
    const d = new Date(value);
    return d.toLocaleTimeString();
}

function navigateToDetail(item) {
    const route = getDetailRoute(item.type, item.uuid);
    router.push(route);
}
</script>
