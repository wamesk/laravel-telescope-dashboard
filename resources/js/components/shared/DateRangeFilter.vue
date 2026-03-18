<template>
    <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Date Range</label>
        <div class="flex flex-wrap gap-2">
            <input
                type="datetime-local"
                class="input-field text-xs flex-1 min-w-0"
                :value="from"
                placeholder="From"
                @input="$emit('update:from', $event.target.value || null)"
            />
            <input
                type="datetime-local"
                class="input-field text-xs flex-1 min-w-0"
                :value="to"
                placeholder="To"
                @input="$emit('update:to', $event.target.value || null)"
            />
        </div>
    </div>
</template>

<script setup>
defineProps({
    from: { type: String, default: null },
    to: { type: String, default: null },
});

defineEmits(['update:from', 'update:to']);
</script>
