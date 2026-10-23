<template>
    <div v-if="value" class="truncate">
        <span class="block truncate" :title="value.email">{{ value.email || '-' }}</span>
        <span v-if="value.id" class="block truncate text-xs text-gray-400 dark:text-gray-500" :title="value.id">{{ value.id }}</span>
    </div>
    <span v-else>-</span>
</template>

<script setup>
defineProps({
    // Summarized `content.user` of an entry: { email, id }, or null without an authenticated user.
    value: { type: Object, default: null },
});
</script>
