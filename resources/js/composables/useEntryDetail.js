import { ref, computed } from 'vue';
import { apiGet } from '../api';

export function useEntryDetail() {
    const entry = ref(null);
    const batch = ref([]);
    const loading = ref(false);
    const error = ref(null);

    async function fetchEntry(uuid) {
        loading.value = true;
        error.value = null;
        entry.value = null;
        batch.value = [];

        try {
            const result = await apiGet(`/entries/${uuid}/detail`);
            entry.value = result.entry;
            batch.value = result.batch || [];
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    const batchByType = computed(() => {
        const grouped = {};
        for (const item of batch.value) {
            if (!grouped[item.type]) {
                grouped[item.type] = [];
            }
            grouped[item.type].push(item);
        }
        return grouped;
    });

    const batchTypes = computed(() => Object.keys(batchByType.value));

    const batchCount = computed(() => batch.value.length);

    return {
        entry,
        batch,
        loading,
        error,
        batchByType,
        batchTypes,
        batchCount,
        fetchEntry,
    };
}
