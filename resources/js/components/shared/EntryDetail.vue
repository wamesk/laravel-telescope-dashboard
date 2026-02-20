<template>
    <div class="bg-gray-50 dark:bg-telescope-darker p-4">
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-8">
            <div class="flex items-center gap-2 text-gray-400">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm">Loading detail...</span>
            </div>
        </div>

        <template v-else-if="detail">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-telescope-border mb-4 gap-1">
                <button
                    v-for="tab in availableTabs"
                    :key="tab.key"
                    class="px-3 py-2 text-sm rounded-t-md transition-colors"
                    :class="activeTab === tab.key
                        ? 'text-gray-900 dark:text-white bg-white dark:bg-telescope-card border border-gray-200 dark:border-telescope-border border-b-white dark:border-b-telescope-card -mb-px'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Copy JSON button -->
            <div v-if="isJsonCopyable(activeTab)" class="flex justify-end mb-2">
                <button
                    @click="copyTabJson"
                    class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-telescope-border text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
                >
                    {{ copyLabel }}
                </button>
            </div>

            <!-- Tab content -->
            <div :class="fullPage ? 'overflow-y-auto' : 'max-h-96 overflow-y-auto'">
                <slot :name="'tab-' + activeTab" :detail="detail">
                    <JsonViewer :data="getTabData(activeTab)" />
                </slot>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-telescope-border">
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span v-if="detail.uuid">UUID: {{ detail.uuid }}</span>
                    <span v-if="detail.batch_id">Batch: {{ detail.batch_id }}</span>
                    <span v-if="detail.created_at">{{ new Date(detail.created_at).toLocaleString() }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="detail.tags && detail.tags.length" class="flex gap-1 flex-wrap">
                        <span
                            v-for="tag in detail.tags"
                            :key="tag"
                            class="inline-block px-2 py-0.5 text-xs bg-gray-200 dark:bg-telescope-border rounded text-gray-600 dark:text-gray-300"
                        >
                            {{ tag }}
                        </span>
                    </span>
                    <router-link
                        v-if="!fullPage && detailRoute"
                        :to="detailRoute"
                        class="text-xs text-telescope-accent hover:text-telescope-accent-light transition-colors"
                    >
                        Open full detail &rarr;
                    </router-link>
                    <span v-if="!fullPage && detailRoute" class="text-gray-300 dark:text-gray-600">|</span>
                    <a
                        :href="telescopeUrl"
                        target="_blank"
                        class="text-xs text-telescope-accent hover:text-telescope-accent-light transition-colors"
                    >
                        View in Telescope &rarr;
                    </a>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import JsonViewer from './JsonViewer.vue';
import { getConfig } from '../../api';
import { getDetailRoute } from '../../entryTypeConfig';

const props = defineProps({
    detail: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    tabs: { type: Array, default: () => [{ key: 'content', label: 'Content' }] },
    fullPage: { type: Boolean, default: false },
});

const config = getConfig();
const activeTab = ref(props.tabs[0]?.key || 'content');
const copyLabel = ref('Copy JSON');

const detailRoute = computed(() => {
    if (!props.detail?.uuid || !props.detail?.type) return null;
    return getDetailRoute(props.detail.type, props.detail.uuid);
});

const availableTabs = computed(() => {
    return props.tabs.filter((tab) => {
        if (!props.detail?.content) return tab.key === 'content';
        return true;
    });
});

const telescopeUrl = computed(() => {
    if (!props.detail) return '#';
    const basePath = config.telescopePath || '/telescope';
    const type = props.detail.type || 'requests';
    return `${basePath}/${type}/${props.detail.uuid}`;
});

function getTabData(tabKey) {
    if (!props.detail?.content) return null;
    if (tabKey === 'content') return props.detail.content;
    return props.detail.content[tabKey] ?? null;
}

function isJsonCopyable(tabKey) {
    const data = getTabData(tabKey);
    return data !== null && data !== undefined && typeof data === 'object';
}

function copyTabJson() {
    const data = getTabData(activeTab.value);
    if (!data) return;
    navigator.clipboard.writeText(JSON.stringify(data, null, 2)).then(() => {
        copyLabel.value = 'Copied!';
        setTimeout(() => { copyLabel.value = 'Copy JSON'; }, 2000);
    });
}
</script>
