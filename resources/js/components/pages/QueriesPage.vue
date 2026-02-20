<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Database Queries</h1>

    <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Content</label>
        <input
          v-model="filters.content"
          type="text"
          placeholder="Search SQL..."
          class="input-field"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Slow Queries</label>
        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300 mt-2">
          <input
            v-model="filters.slow_query"
            type="checkbox"
            class="rounded bg-white dark:bg-telescope-dark border-gray-300 dark:border-telescope-border text-telescope-accent focus:ring-telescope-accent"
          />
          Slow Queries Only
        </label>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Query Type</label>
        <select v-model="filters.query_type" class="select-field">
          <option value="">All</option>
          <option value="select">SELECT</option>
          <option value="insert">INSERT</option>
          <option value="update">UPDATE</option>
          <option value="delete">DELETE</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Min Duration (ms)</label>
        <input
          v-model.number="filters.min_duration"
          type="number"
          placeholder="Min duration..."
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
      entry-type="query"
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
      <template #cell-content.time="{ value }">
        <span
          :class="{
            'badge badge-red': Number(value) > 100,
            'badge badge-yellow': Number(value) > 50 && Number(value) <= 100,
            'badge badge-gray': Number(value) <= 50
          }"
        >
          {{ value != null ? Number(value).toFixed(2) : '-' }} ms
        </span>
      </template>
      <template #detail="{ detail, loading: dl }">
        <EntryDetail
          :detail="detail"
          :loading="dl"
          :tabs="[
            { key: 'content', label: 'SQL' },
            { key: 'bindings', label: 'Bindings' }
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
  sortBy,
  sortDirection,
  setSort,
} = useEntries('query');

const {
  filters,
  getActiveFilters,
  resetFilters
} = useFilters({
  content: '',
  slow_query: false,
  query_type: '',
  min_duration: null,
  date_from: '',
  date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, sortBy, sortDirection);

const columns = [
  { key: 'content.sql', label: 'SQL' },
  { key: 'content.time', label: 'Time (ms)', sortable: true, sortKey: 'content.time' },
  { key: 'content.connection', label: 'Connection', width: '120px' },
  { key: 'content.file', label: 'File' },
  { key: 'created_at', label: 'Time', format: 'datetime', width: '180px', sortable: true, sortKey: 'created_at' }
];

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
