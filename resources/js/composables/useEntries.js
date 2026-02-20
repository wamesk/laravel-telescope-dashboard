import { ref } from 'vue';
import { apiPost, apiGet } from '../api';

export function useEntries(type) {
    const entries = ref([]);
    const loading = ref(false);
    const loadingMore = ref(false);
    const hasMore = ref(false);
    const nextCursor = ref(null);
    const totalOffset = ref(null);
    const error = ref(null);
    const expandedEntry = ref(null);
    const entryDetail = ref(null);
    const loadingDetail = ref(false);

    const sortBy = ref(null);
    const sortDirection = ref(null);

    function setSort(column, direction) {
        sortBy.value = column;
        sortDirection.value = direction;
        totalOffset.value = null;
        nextCursor.value = null;
    }

    async function fetchEntries(filters = {}, append = false) {
        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
            entries.value = [];
            nextCursor.value = null;
            totalOffset.value = null;
        }

        error.value = null;

        try {
            const payload = {
                type,
                ...filters,
            };

            if (sortBy.value) {
                payload.sort_by = sortBy.value;
                payload.sort_direction = sortDirection.value;
            }

            const isCustomSort = sortBy.value && sortBy.value !== 'sequence';

            if (append) {
                if (isCustomSort && totalOffset.value !== null) {
                    payload.offset = totalOffset.value;
                } else if (nextCursor.value) {
                    payload.before_sequence = nextCursor.value;
                }
            }

            const result = await apiPost('/entries', payload);

            if (append) {
                entries.value = [...entries.value, ...result.entries];
            } else {
                entries.value = result.entries;
            }

            hasMore.value = result.has_more;
            nextCursor.value = result.next_cursor;
            totalOffset.value = result.total_offset ?? null;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
            loadingMore.value = false;
        }
    }

    async function loadMore(filters = {}) {
        await fetchEntries(filters, true);
    }

    async function toggleDetail(uuid) {
        if (expandedEntry.value === uuid) {
            expandedEntry.value = null;
            entryDetail.value = null;
            return;
        }

        expandedEntry.value = uuid;
        loadingDetail.value = true;

        try {
            entryDetail.value = await apiGet(`/entries/${uuid}`);
        } catch (e) {
            error.value = e.message;
        } finally {
            loadingDetail.value = false;
        }
    }

    return {
        entries,
        loading,
        loadingMore,
        hasMore,
        nextCursor,
        totalOffset,
        error,
        expandedEntry,
        entryDetail,
        loadingDetail,
        sortBy,
        sortDirection,
        setSort,
        fetchEntries,
        loadMore,
        toggleDetail,
    };
}
