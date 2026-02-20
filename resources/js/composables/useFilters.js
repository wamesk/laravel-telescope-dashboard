import { reactive, computed } from 'vue';

export function useFilters(defaults = {}) {
    const filters = reactive({ ...defaults });

    function setFilter(key, value) {
        filters[key] = value;
    }

    function resetFilters() {
        Object.keys(filters).forEach((key) => {
            if (Array.isArray(defaults[key])) {
                filters[key] = [...defaults[key]];
            } else {
                filters[key] = defaults[key] ?? null;
            }
        });
    }

    function getActiveFilters() {
        const active = {};
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== null && value !== '' && value !== undefined) {
                if (Array.isArray(value) && value.length === 0) return;
                active[key] = value;
            }
        });
        return active;
    }

    const hasActiveFilters = computed(() => {
        return Object.keys(getActiveFilters()).length > 0;
    });

    return {
        filters,
        setFilter,
        resetFilters,
        getActiveFilters,
        hasActiveFilters,
    };
}
