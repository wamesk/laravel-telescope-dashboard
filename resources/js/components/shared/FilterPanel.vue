<template>
    <div class="bg-white dark:bg-telescope-card rounded-lg border border-gray-200 dark:border-telescope-border mb-6">
        <!-- Header -->
        <button
            class="flex items-center justify-between w-full px-4 py-3 text-left"
            @click="expanded = !expanded"
        >
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
                <span v-if="activeCount > 0" class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-telescope-accent rounded-full">
                    {{ activeCount }}
                </span>
            </div>
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Content -->
        <div v-show="expanded" class="px-4 pb-4 border-t border-gray-200 dark:border-telescope-border">
            <div class="pt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <slot />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-telescope-border">
                <button class="btn-primary" @click="$emit('search')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </button>
                <button class="btn-secondary" @click="$emit('reset')">
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    activeCount: { type: Number, default: 0 },
});

defineEmits(['search', 'reset']);

const expanded = ref(true);
</script>
