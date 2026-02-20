<template>
    <div v-if="hasMore" class="flex justify-center py-4 mt-4">
        <button
            class="btn-secondary"
            :disabled="loading"
            @click="$emit('load-more')"
        >
            <template v-if="loading">
                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Loading...
            </template>
            <template v-else>
                Load More
            </template>
        </button>
    </div>
</template>

<script setup>
defineProps({
    hasMore: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

defineEmits(['load-more']);
</script>
