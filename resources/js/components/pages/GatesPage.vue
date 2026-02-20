<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Gates</h1>
        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ability</label>
                    <input v-model="filters.ability" type="text" placeholder="e.g., view-post" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Result</label>
                    <select v-model="filters.gate_result" class="select-field">
                        <option value="">All</option>
                        <option value="allowed">Allowed</option>
                        <option value="denied">Denied</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Content</label>
                <input v-model="filters.content" type="text" placeholder="Search..." class="input-field">
            </div>
            <DateRangeFilter v-model:from="filters.date_from" v-model:to="filters.date_to" />
        </FilterPanel>
        <DataTable :columns="columns" :entries="entries" :loading="loading" :error="error" :expanded-entry="expandedEntry" :entry-detail="entryDetail" :loading-detail="loadingDetail" entry-type="gate" @toggle-detail="toggleDetail" @retry="search">
            <template #cell-content.result="{ value }">
                <Badge :type="'gate-result'" :value="value" />
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
    error,
    hasMore,
    loadingMore,
    expandedEntry,
    entryDetail,
    loadingDetail,
    fetchEntries,
    loadMore,
    toggleDetail
} = useEntries('gate');

const {
    filters,
    getActiveFilters,
    resetFilters
} = useFilters({
    ability: '',
    gate_result: '',
    content: '',
    date_from: '',
    date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, null, null);

const columns = [
    { key: 'content.ability', label: 'Ability' },
    { key: 'content.result', label: 'Result', width: '100px' },
    { key: 'content.arguments', label: 'Arguments' },
    { key: 'created_at', label: 'Time', format: 'datetime', width: '180px' }
];

const tabs = [
    { key: 'content', label: 'Content' }
];

const search = () => {
    fetchEntries(getActiveFilters());
    syncToUrl();
};

const reset = () => {
    resetFilters();
    fetchEntries({});
    syncToUrl();
};

onMounted(() => {
    restoreFromUrl();
    search();
});
</script>
