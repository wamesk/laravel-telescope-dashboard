<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Redis</h1>
        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Command</label>
                <input
                    v-model="filters.redis_command"
                    type="text"
                    class="input-field"
                    placeholder="Filter by command"
                />
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
            entry-type="redis"
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
} = useEntries('redis');

const { filters, getActiveFilters, resetFilters } = useFilters({
    redis_command: '',
    min_duration: null,
    content: '',
    date_from: '',
    date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, sortBy, sortDirection);

const columns = [
    { key: 'content.command', label: 'Command' },
    { key: 'content.time', label: 'Duration', width: '120px', format: 'duration', sortable: true, sortKey: 'content.time' },
    { key: 'created_at', label: 'Time', format: 'datetime', width: '180px', sortable: true, sortKey: 'created_at' }
];

const tabs = [
    { key: 'content', label: 'Content' }
];

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
