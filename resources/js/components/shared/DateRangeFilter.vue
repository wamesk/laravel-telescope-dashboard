<template>
    <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Date Range</label>
        <div class="flex gap-2">
            <input
                type="date"
                class="input-field text-xs"
                :value="from"
                placeholder="From"
                @input="$emit('update:from', $event.target.value || null)"
            />
            <input
                type="date"
                class="input-field text-xs"
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
