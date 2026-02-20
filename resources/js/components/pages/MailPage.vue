<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Mail</h1>
        <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mailable Class</label>
                <input v-model="filters.mailable" type="text" placeholder="e.g. App\Mail\WelcomeMail" class="input-field">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To</label>
                <input v-model="filters.mail_to" type="text" placeholder="Recipient email address" class="input-field">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                <input v-model="filters.mail_subject" type="text" placeholder="Email subject" class="input-field">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Content</label>
                <input v-model="filters.content" type="text" placeholder="Search in email content..." class="input-field">
            </div>
            <DateRangeFilter v-model:from="filters.date_from" v-model:to="filters.date_to" />
        </FilterPanel>
        <DataTable :columns="columns" :entries="entries" :loading="loading" :error="error" :expanded-entry="expandedEntry" :entry-detail="entryDetail" :loading-detail="loadingDetail" entry-type="mail" @toggle-detail="toggleDetail" @retry="search">
            <template #content.queued="{ value }">
                <span>{{ value ? 'Yes' : 'No' }}</span>
            </template>
            <template #detail="{ detail, loading: dl }">
                <EntryDetail :detail="detail" :loading="dl" :tabs="[{ key: 'content', label: 'Overview' }]" />
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

const columns = [
    { key: 'content.mailable', label: 'Mailable' },
    { key: 'content.to', label: 'To', width: '200px' },
    { key: 'content.subject', label: 'Subject' },
    { key: 'content.queued', label: 'Queued', width: '80px' },
    { key: 'created_at', label: 'Time', format: 'datetime', width: '180px' }
];

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
} = useEntries('mail');

const {
    filters,
    getActiveFilters,
    resetFilters
} = useFilters({
    mailable: '',
    mail_to: '',
    mail_subject: '',
    content: '',
    date_from: '',
    date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, null, null);

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
