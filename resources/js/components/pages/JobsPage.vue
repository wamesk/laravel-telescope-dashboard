<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Jobs</h1>

    <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Job Name</label>
        <input
          v-model="filters.job_name"
          type="text"
          placeholder="Job name..."
          class="input-field"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Job Status</label>
        <select v-model="filters.job_status" class="select-field">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Content</label>
        <input
          v-model="filters.content"
          type="text"
          placeholder="Search..."
          class="input-field"
        />
      </div>

      <DateRangeFilter
        v-model:from="filters.date_from"
        v-model:to="filters.date_to"
      />
    </FilterPanel>

    <DataTable
      :columns="columns"
      entry-type="job"
      :entries="entries"
      :loading="loading"
      :error="error"
      :expanded-entry="expandedEntry"
      :entry-detail="entryDetail"
      :loading-detail="loadingDetail"
      @toggle-detail="toggleDetail"
      @retry="search"
    >
      <template #cell-content.status="{ value }">
        <Badge type="job-status" :label="value" />
      </template>
      <template #detail="{ detail, loading: dl }">
        <EntryDetail
          :detail="detail"
          :loading="dl"
          :tabs="[
            { key: 'content', label: 'Overview' },
            { key: 'data', label: 'Job Data' }
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
import DateRangeFilter from '../shared/DateRangeFilter.vue';
import Badge from '../shared/Badge.vue';

const {
  entries,
  loading,
  loadingMore,
  hasMore,
  error,
  expandedEntry,
  entryDetail,
  loadingDetail,
  fetchEntries,
  loadMore,
  toggleDetail,
} = useEntries('job');

const {
  filters,
  getActiveFilters,
  resetFilters
} = useFilters({
  job_name: '',
  job_status: '',
  content: '',
  date_from: '',
  date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, null, null);

const columns = [
  { key: 'content.name', label: 'Job Name' },
  { key: 'content.queue', label: 'Queue', width: '120px' },
  { key: 'content.status', label: 'Status', width: '100px' },
  { key: 'content.tries', label: 'Tries', width: '80px' },
  { key: 'created_at', label: 'Time', format: 'datetime', width: '180px' }
];

function search() {
  fetchEntries(getActiveFilters());
  syncToUrl();
}

function reset() {
  resetFilters();
  fetchEntries({});
  syncToUrl();
}

onMounted(() => {
  restoreFromUrl();
  search();
});
</script>
