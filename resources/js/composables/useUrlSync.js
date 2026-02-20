import { useRouter, useRoute } from 'vue-router';

export function useUrlSync(filters, sortBy, sortDirection) {
    const router = useRouter();
    const route = useRoute();

    function restoreFromUrl() {
        const query = route.query;
        for (const key of Object.keys(filters)) {
            if (query[key] !== undefined) {
                if (Array.isArray(filters[key])) {
                    filters[key] = query[key] ? String(query[key]).split(',') : [];
                } else if (typeof filters[key] === 'number' || key === 'min_duration') {
                    filters[key] = query[key] !== '' ? Number(query[key]) : null;
                } else if (typeof filters[key] === 'boolean' || key === 'slow_query') {
                    filters[key] = query[key] === 'true' || query[key] === '1';
                } else {
                    filters[key] = query[key] || null;
                }
            }
        }
        if (sortBy && query.sort_by) sortBy.value = query.sort_by;
        if (sortDirection && query.sort_direction) sortDirection.value = query.sort_direction;
    }

    function syncToUrl() {
        const query = {};
        for (const [key, value] of Object.entries(filters)) {
            if (value === null || value === '' || value === undefined) continue;
            if (Array.isArray(value) && value.length === 0) continue;
            if (value === false) continue;
            query[key] = Array.isArray(value) ? value.join(',') : String(value);
        }
        if (sortBy && sortBy.value) {
            query.sort_by = sortBy.value;
            query.sort_direction = sortDirection.value;
        }
        router.replace({ query });
    }

    return { restoreFromUrl, syncToUrl };
}
