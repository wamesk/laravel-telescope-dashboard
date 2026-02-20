<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Notifications</h1>
        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notification Class</label>
                <input
                    v-model="filters.notification_class"
                    type="text"
                    class="input-field"
                    placeholder="Filter by notification class"
                />
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel</label>
                <input
                    v-model="filters.notification_channel"
                    type="text"
                    class="input-field"
                    placeholder="Filter by channel"
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
            entry-type="notification"
            :loading="loading"
            :error="error"
            :expanded-entry="expandedEntry"
            :entry-detail="entryDetail"
            :loading-detail="loadingDetail"
            @toggle-detail="toggleDetail"
            @retry="search"
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
    toggleDetail
} = useEntries('notification');

const { filters, getActiveFilters, resetFilters } = useFilters({
    notification_class: '',
    notification_channel: '',
    content: '',
    date_from: '',
    date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, null, null);

const columns = [
    { key: 'content.notification', label: 'Notification' },
    { key: 'content.notifiable', label: 'Notifiable', width: '200px' },
    { key: 'content.channel', label: 'Channel', width: '120px' },
    { key: 'content.response', label: 'Response', width: '120px' },
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
