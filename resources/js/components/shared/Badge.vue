<template>
    <span
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
        :class="colorClasses"
    >
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: [String, Number], default: '' },
    type: { type: String, default: 'default' },
    // Presets: method, status, level, job-status, cache-type, gate-result, model-action
});

const colorClasses = computed(() => {
    const v = String(props.label).toLowerCase();

    // HTTP Methods
    if (props.type === 'method') {
        const map = {
            get: 'bg-green-500/20 text-green-400',
            post: 'bg-blue-500/20 text-blue-400',
            put: 'bg-yellow-500/20 text-yellow-400',
            patch: 'bg-orange-500/20 text-orange-400',
            delete: 'bg-red-500/20 text-red-400',
        };
        return map[v] || 'bg-gray-500/20 text-gray-400';
    }

    // HTTP Status
    if (props.type === 'status') {
        const code = Number(props.label);
        if (code >= 200 && code < 300) return 'bg-green-500/20 text-green-400';
        if (code >= 300 && code < 400) return 'bg-blue-500/20 text-blue-400';
        if (code >= 400 && code < 500) return 'bg-yellow-500/20 text-yellow-400';
        if (code >= 500) return 'bg-red-500/20 text-red-400';
        return 'bg-gray-500/20 text-gray-400';
    }

    // Log levels
    if (props.type === 'level') {
        const map = {
            emergency: 'bg-red-600/20 text-red-400',
            alert: 'bg-red-500/20 text-red-400',
            critical: 'bg-red-500/20 text-red-400',
            error: 'bg-red-400/20 text-red-400',
            warning: 'bg-yellow-500/20 text-yellow-400',
            notice: 'bg-blue-500/20 text-blue-400',
            info: 'bg-cyan-500/20 text-cyan-400',
            debug: 'bg-gray-500/20 text-gray-400',
        };
        return map[v] || 'bg-gray-500/20 text-gray-400';
    }

    // Job status
    if (props.type === 'job-status') {
        const map = {
            completed: 'bg-green-500/20 text-green-400',
            pending: 'bg-yellow-500/20 text-yellow-400',
            failed: 'bg-red-500/20 text-red-400',
        };
        return map[v] || 'bg-gray-500/20 text-gray-400';
    }

    // Cache type
    if (props.type === 'cache-type') {
        const map = {
            hit: 'bg-green-500/20 text-green-400',
            missed: 'bg-red-500/20 text-red-400',
            set: 'bg-blue-500/20 text-blue-400',
            forget: 'bg-yellow-500/20 text-yellow-400',
        };
        return map[v] || 'bg-gray-500/20 text-gray-400';
    }

    // Gate result
    if (props.type === 'gate-result') {
        return v === 'allowed'
            ? 'bg-green-500/20 text-green-400'
            : 'bg-red-500/20 text-red-400';
    }

    // Model action
    if (props.type === 'model-action') {
        const map = {
            created: 'bg-green-500/20 text-green-400',
            updated: 'bg-blue-500/20 text-blue-400',
            deleted: 'bg-red-500/20 text-red-400',
        };
        return map[v] || 'bg-gray-500/20 text-gray-400';
    }

    // Exit code
    if (props.type === 'exit-code') {
        return Number(props.label) === 0
            ? 'bg-green-500/20 text-green-400'
            : 'bg-red-500/20 text-red-400';
    }

    // Default
    return 'bg-gray-500/20 text-gray-400';
});
</script>
