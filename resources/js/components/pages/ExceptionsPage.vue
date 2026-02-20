<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Exceptions</h1>

    <FilterPanel :active-count="Object.keys(getActiveFilters()).length" @search="search" @reset="reset">
      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Exception Class</label>
        <input
          v-model="filters.exception_class"
          type="text"
          placeholder="Exception class..."
          class="input-field"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Content</label>
        <input
          v-model="filters.content"
          type="text"
          placeholder="Search message..."
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
      entry-type="exception"
      :entries="entries"
      :loading="loading"
      :error="error"
      :expanded-entry="expandedEntry"
      :entry-detail="entryDetail"
      :loading-detail="loadingDetail"
      @toggle-detail="toggleDetail"
      @retry="search"
    >
      <template #cell-content.class="{ value }">
        <span :title="value">
          {{ truncateClassName(value) }}
        </span>
      </template>
      <template #detail="{ detail, loading: dl }">
        <EntryDetail
          :detail="detail"
          :loading="dl"
          :tabs="[
            { key: 'content', label: 'Overview' },
            { key: 'trace', label: 'Stack Trace' }
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
} = useEntries('exception');

const {
  filters,
  getActiveFilters,
  resetFilters
} = useFilters({
  exception_class: '',
  content: '',
  date_from: '',
  date_to: ''
});

const { restoreFromUrl, syncToUrl } = useUrlSync(filters, null, null);

const columns = [
  { key: 'content.class', label: 'Class' },
  { key: 'content.message', label: 'Message' },
  { key: 'content.file', label: 'File' },
  { key: 'content.line', label: 'Line', width: '60px' },
  { key: 'content.occurrences', label: 'Occurrences', width: '100px' },
  { key: 'created_at', label: 'Time', format: 'datetime', width: '180px' }
];

const truncateClassName = (className) => {
  if (!className) return '';
  const parts = className.split('\\');
  return parts[parts.length - 1];
};

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
