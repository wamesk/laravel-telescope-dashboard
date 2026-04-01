<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Client Requests</h1>
        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Method</label>
                <select
                    v-model="filters.client_method"
                    class="select-field"
                >
                    <option value="">All</option>
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URI</label>
                <input
                    v-model="filters.client_uri"
                    type="text"
                    class="input-field"
                    placeholder="Filter by URI"
                />
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="s in ['2xx','3xx','4xx','5xx']" :key="s" class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            :checked="filters.client_statuses?.includes(s)"
                            class="rounded bg-white dark:bg-telescope-dark border-gray-300 dark:border-telescope-border text-telescope-accent focus:ring-telescope-accent"
                            @change="toggleClientStatus(s)"
                        />
                        {{ s }}
                    </label>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Min Duration (ms)</label>
                <input
                    v-model.number="filters.min_duration"
                    type="number"
                    class="input-field"
                    placeholder="Minimum duration"
                />
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content Search</label>
                <input
                    v-model="filters.content"
                    type="text"
                    class="input-field"
                    placeholder="Search content"
                />
            </div>
            <DateRangeFilter v-model:from="filters.date_from" v-model:to="filters.date_to" />
        </FilterPanel>
        <DataTable
            :columns="columns"
            :entries="entries"
            entry-type="client_request"
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
            <template #detail="{ detail, loading: dl }">
                <EntryDetail :detail="detail" :loading="dl" :tabs="tabs" />
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
import DateRangeFilter from '../shared/DateRangeFilter.vue';
import Badge from '../shared/Badge.vue';

const {
    entries,
    loading,
    loadingMore,
    error,
    hasMore,
    expandedEntry,
    entryDetail,
    loadingDetail,
    fetchEntries,
    loadMore,
    toggleDetail,
    sortBy,
    sortDirection,
    setSort
} = useEntries('client_request');

const { filters, getActiveFilters, resetFilters } = useFilters({
    client_method: '',
    client_uri: '',
    client_statuses: [],
    min_duration: null,
    content: '',
    date_from: '',
    date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, sortBy, sortDirection);

const columns = [
    { key: 'content.method', label: 'Method', width: '80px' },
    { key: 'content.uri', label: 'URI' },
    { key: 'content.response_status', label: 'Status', width: '80px' },
    { key: 'content.duration', label: 'Duration', width: '120px', format: 'duration', sortable: true, sortKey: 'content.duration' },
    { key: 'created_at', label: 'Time', format: 'datetime', width: '180px', sortable: true, sortKey: 'created_at' }
];

const tabs = [
    { key: 'content', label: 'Overview' }
];

function toggleClientStatus(status) {
    const idx = filters.client_statuses.indexOf(status);
    if (idx >= 0) filters.client_statuses.splice(idx, 1);
    else filters.client_statuses.push(status);
}

const handleSort = (column) => {
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
};

const search = () => {
    fetchEntries(getActiveFilters());
    syncToUrl();
};

const reset = () => {
    resetFilters();
    setSort(null, null);
    fetchEntries({});
    syncToUrl();
};

onMounted(() => {
    restoreFromUrl();
    search();
});
</script>
