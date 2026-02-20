<template>
    <div class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border overflow-hidden">
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm">Loading...</span>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center py-16">
            <div class="text-center">
                <p class="text-red-400 text-sm">{{ error }}</p>
                <button class="mt-2 text-sm text-telescope-accent hover:underline" @click="$emit('retry')">Retry</button>
            </div>
        </div>

        <!-- Empty -->
        <div v-else-if="!entries.length" class="flex items-center justify-center py-16">
            <p class="text-gray-500 text-sm">No entries found</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-telescope-dark">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="table-header"
                            :class="{
                                'cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-telescope-border/50 transition-colors': col.sortable,
                                'bg-gray-100 dark:bg-telescope-border/30': col.sortable && sortBy === col.sortKey
                            }"
                            :style="col.width ? { width: col.width } : {}"
                            @click="col.sortable ? $emit('sort', col.sortKey || col.key) : null"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <svg v-if="col.sortable && sortBy !== col.sortKey" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                                <span v-if="col.sortable && sortBy === col.sortKey" class="text-telescope-accent text-xs font-bold">
                                    {{ sortDirection === 'desc' ? '▼' : '▲' }}
                                </span>
                            </span>
                        </th>
                        <th class="table-header w-20"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="entry in entries" :key="entry.uuid">
                        <tr
                            class="border-t border-gray-200 dark:border-telescope-border hover:bg-gray-50 dark:hover:bg-telescope-dark/50 cursor-pointer transition-colors"
                            @click="$emit('toggle-detail', entry.uuid)"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="table-cell"
                            >
                                <slot :name="'cell-' + col.key" :entry="entry" :value="getNestedValue(entry, col.key)">
                                    <span class="truncate block max-w-xs" :title="String(getNestedValue(entry, col.key) ?? '')">
                                        {{ formatValue(getNestedValue(entry, col.key), col) }}
                                    </span>
                                </slot>
                            </td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-3">
                                    <router-link
                                        v-if="entryType"
                                        :to="getEntryDetailRoute(entry)"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-telescope-accent hover:text-telescope-accent-light bg-telescope-accent/10 hover:bg-telescope-accent/20 rounded transition-colors"
                                        title="Open detail page"
                                        @click.stop
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        Detail
                                    </router-link>
                                    <svg
                                        class="w-4 h-4 text-gray-500 transition-transform flex-shrink-0"
                                        :class="{ 'rotate-180': expandedEntry === entry.uuid }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="expandedEntry === entry.uuid" class="border-t border-gray-200 dark:border-telescope-border">
                            <td :colspan="columns.length + 1" class="p-0">
                                <slot name="detail" :entry="entry" :detail="entryDetail" :loading="loadingDetail" />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { getDetailRoute } from '../../entryTypeConfig';

const props = defineProps({
    columns: { type: Array, required: true },
    entries: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    expandedEntry: { type: String, default: null },
    entryDetail: { type: Object, default: null },
    loadingDetail: { type: Boolean, default: false },
    entryType: { type: String, default: null },
    sortBy: { type: String, default: null },
    sortDirection: { type: String, default: null },
});

defineEmits(['toggle-detail', 'retry', 'sort']);

function getEntryDetailRoute(entry) {
    const type = props.entryType || entry.type;
    return getDetailRoute(type, entry.uuid);
}

function getNestedValue(obj, path) {
    return path.split('.').reduce((acc, key) => acc?.[key], obj);
}

function formatValue(value, col) {
    if (value === null || value === undefined) return '-';
    if (col.format === 'duration') return `${Number(value).toFixed(2)} ms`;
    if (col.format === 'memory') return `${(Number(value) / 1024 / 1024).toFixed(1)} MB`;
    if (col.format === 'datetime') {
        const d = new Date(value);
        return d.toLocaleString();
    }
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}
</script>
