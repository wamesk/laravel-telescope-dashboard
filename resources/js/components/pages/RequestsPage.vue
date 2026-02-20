<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Requests</h1>

        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <!-- Method -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Method</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="m in ['GET','POST','PUT','PATCH','DELETE']" :key="m" class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            :checked="filters.methods?.includes(m)"
                            class="rounded bg-white dark:bg-telescope-dark border-gray-300 dark:border-telescope-border text-telescope-accent focus:ring-telescope-accent"
                            @change="toggleMethod(m)"
                        />
                        {{ m }}
                    </label>
                </div>
            </div>

            <!-- URI -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">URI</label>
                <input v-model="filters.uri" type="text" class="input-field" placeholder="/api/v1/..." />
            </div>

            <!-- Content Search -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Content Search</label>
                <input v-model="filters.content" type="text" class="input-field" placeholder="Search in request/response..." />
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="s in ['2xx','3xx','4xx','5xx']" :key="s" class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            :checked="filters.statuses?.includes(s)"
                            class="rounded bg-white dark:bg-telescope-dark border-gray-300 dark:border-telescope-border text-telescope-accent focus:ring-telescope-accent"
                            @change="toggleStatus(s)"
                        />
                        {{ s }}
                    </label>
                </div>
            </div>

            <!-- Min Duration -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Min Duration (ms)</label>
                <input v-model.number="filters.min_duration" type="number" class="input-field" placeholder="500" />
            </div>

            <!-- Route Group -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Route Group</label>
                <select v-model="filters.route_group" class="select-field">
                    <option :value="null">All</option>
                    <option value="api">API</option>
                    <option value="nova-api">Nova API</option>
                    <option value="web">Web</option>
                </select>
            </div>

            <!-- User Email -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">User Email</label>
                <input v-model="filters.user_email" type="text" class="input-field" placeholder="john@example.com" />
            </div>

            <!-- Date Range -->
            <DateRangeFilter v-model:from="filters.date_from" v-model:to="filters.date_to" />
        </FilterPanel>

        <DataTable
            :columns="columns"
            entry-type="request"
            :entries="entries"
            :loading="loading"
            :error="error"
            :expanded-entry="expandedEntry"
            :entry-detail="entryDetail"
            :loading-detail="loadingDetail"
            :sort-by="sortBy"
            :sort-direction="sortDirection"
            @toggle-detail="toggleDetail"
            @retry="search"
            @sort="handleSort"
        >
            <template #cell-content.method="{ value }">
                <Badge :label="value" type="method" />
            </template>
            <template #cell-content.response_status="{ value }">
                <Badge :label="value" type="status" />
            </template>
            <template #cell-content.duration="{ value }">
                <span :class="value > 1000 ? 'text-red-400' : value > 500 ? 'text-yellow-400' : 'text-gray-300'">
                    {{ value ? `${Number(value).toFixed(2)} ms` : '-' }}
                </span>
            </template>
            <template #detail="{ detail, loading: dl }">
                <EntryDetail
                    :detail="detail"
                    :loading="dl"
                    :tabs="[
                        { key: 'content', label: 'Overview' },
                        { key: 'headers', label: 'Headers' },
                        { key: 'payload', label: 'Payload' },
                        { key: 'response', label: 'Response' },
                    ]"
                />
            </template>
        </DataTable>

        <LoadMore :has-more="hasMore" :loading="loadingMore" @load-more="loadMore(getActiveFilters())" />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useEntries } from '../../composables/useEntries';
import { useFilters } from '../../composables/useFilters';
import { useUrlSync } from '../../composables/useUrlSync';
import FilterPanel from '../shared/FilterPanel.vue';
import DataTable from '../shared/DataTable.vue';
import EntryDetail from '../shared/EntryDetail.vue';
import LoadMore from '../shared/LoadMore.vue';
import Badge from '../shared/Badge.vue';
import DateRangeFilter from '../shared/DateRangeFilter.vue';

const { entries, loading, loadingMore, hasMore, error, expandedEntry, entryDetail, loadingDetail, fetchEntries, loadMore, toggleDetail, sortBy, sortDirection, setSort } = useEntries('request');
const { filters, getActiveFilters, resetFilters } = useFilters({
    methods: [],
    uri: null,
    content: null,
    statuses: [],
    min_duration: null,
    route_group: null,
    user_email: null,
    date_from: null,
    date_to: null,
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, sortBy, sortDirection);

const columns = [
    { key: 'content.method', label: 'Method', width: '80px' },
    { key: 'content.uri', label: 'URI' },
    { key: 'content.response_status', label: 'Status', width: '80px' },
    { key: 'content.duration', label: 'Duration', width: '120px', format: 'duration', sortable: true, sortKey: 'content.duration' },
    { key: 'content.user', label: 'User', width: '150px' },
    { key: 'created_at', label: 'Time', width: '180px', format: 'datetime', sortable: true, sortKey: 'created_at' },
];

function toggleMethod(method) {
    const idx = filters.methods.indexOf(method);
    if (idx >= 0) filters.methods.splice(idx, 1);
    else filters.methods.push(method);
}

function toggleStatus(status) {
    const idx = filters.statuses.indexOf(status);
    if (idx >= 0) filters.statuses.splice(idx, 1);
    else filters.statuses.push(status);
}

function handleSort(column) {
    if (sortBy.value === column) {
        if (sortDirection.value === 'desc') {
            setSort(column, 'asc');
        } else {
            setSort(null, null);
        }
    } else {
        setSort(column, 'desc');
    }
    fetchEntries(getActiveFilters());
    syncToUrl();
}

function search() {
    fetchEntries(getActiveFilters());
    syncToUrl();
}

function reset() {
    resetFilters();
    setSort(null, null);
    fetchEntries({});
    syncToUrl();
}

onMounted(() => {
    restoreFromUrl();
    search();
});
</script>
