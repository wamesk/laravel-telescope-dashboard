<template>
    <div>
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm">Loading...</span>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center py-24">
            <div class="text-center">
                <p class="text-red-400 text-sm mb-2">{{ error }}</p>
                <router-link
                    :to="'/' + routeSegment"
                    class="text-sm text-telescope-accent hover:text-telescope-accent-light transition-colors"
                >
                    &larr; {{ t?.detail?.back_to_list || 'Back to list' }}
                </router-link>
            </div>
        </div>

        <!-- Content -->
        <template v-else-if="entry">
            <EntryHeader :entry="entry" :db-type="dbType" />

            <!-- User -->
            <div v-if="userInfo" class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border overflow-hidden mb-6">
                <div class="px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">User</h2>
                    <div class="space-y-2">
                        <div v-for="(val, key) in userLabeled" :key="key" class="flex items-baseline gap-3">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-40 flex-shrink-0">{{ key }}</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ val }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border overflow-hidden">
                <EntryDetail
                    :detail="entry"
                    :loading="false"
                    :tabs="entryTabs"
                    :full-page="true"
                />
            </div>

            <RelatedEntries
                :batch-by-type="batchByType"
                :batch-types="batchTypes"
                :batch-count="batchCount"
            />
        </template>
    </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useEntryDetail } from '../../composables/useEntryDetail';
import { routeSegmentToType, getTypeConfig, typeToRouteSegment } from '../../entryTypeConfig';
import { getTranslations } from '../../api';
import EntryHeader from '../shared/EntryHeader.vue';
import EntryDetail from '../shared/EntryDetail.vue';
import RelatedEntries from '../shared/RelatedEntries.vue';

const route = useRoute();
const t = getTranslations();
const { entry, loading, error, batchByType, batchTypes, batchCount, fetchEntry } = useEntryDetail();

const routeSegment = computed(() => route.params.type);
const dbType = computed(() => routeSegmentToType(routeSegment.value));

const userInfo = computed(() => {
    const user = entry.value?.content?.user;
    if (!user || typeof user !== 'object') return null;
    return user;
});

const userLabeled = computed(() => {
    if (!userInfo.value) return null;
    const labels = { id: 'ID', name: 'Name', email: 'Email' };
    const result = {};
    for (const [key, val] of Object.entries(userInfo.value)) {
        result[labels[key] || key] = val;
    }
    return result;
});

const entryTabs = computed(() => {
    const config = getTypeConfig(dbType.value);
    return config?.tabs || [{ key: 'content', label: 'Content' }];
});

function loadEntry() {
    const id = route.params.id;
    if (id) {
        fetchEntry(id);
    }
}

watch(() => route.params.id, (newId, oldId) => {
    if (newId && newId !== oldId) {
        loadEntry();
    }
});

onMounted(() => loadEntry());
</script>
