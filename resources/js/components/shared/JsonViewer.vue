<template>
    <div class="font-mono text-sm leading-relaxed">
        <div v-if="data === null || data === undefined" class="json-null">null</div>
        <div v-else-if="typeof data === 'string'" class="json-string">"{{ data }}"</div>
        <div v-else-if="typeof data === 'number'" class="json-number">{{ data }}</div>
        <div v-else-if="typeof data === 'boolean'" class="json-boolean">{{ data }}</div>
        <div v-else-if="Array.isArray(data)">
            <div v-if="data.length === 0" class="text-gray-500">[]</div>
            <div v-else>
                <div
                    v-for="(item, index) in data"
                    :key="index"
                    class="ml-4 border-l border-gray-200 dark:border-telescope-border pl-3 my-1"
                >
                    <span class="json-number text-xs mr-2">{{ index }}</span>
                    <JsonViewer :data="item" :depth="depth + 1" />
                </div>
            </div>
        </div>
        <div v-else-if="typeof data === 'object'">
            <div v-if="Object.keys(data).length === 0" class="text-gray-500">{}</div>
            <div v-else>
                <div
                    v-for="(value, key) in data"
                    :key="key"
                    class="my-1"
                >
                    <template v-if="depth < maxDepth || typeof value !== 'object' || value === null">
                        <div class="flex">
                            <span class="json-key flex-shrink-0 mr-2">"{{ key }}":</span>
                            <div class="flex-1 min-w-0">
                                <template v-if="isLongString(value)">
                                    <div
                                        class="json-string cursor-pointer hover:opacity-80"
                                        @click="toggleExpand(key)"
                                    >
                                        <template v-if="expandedKeys[key]">
                                            "{{ value }}"
                                        </template>
                                        <template v-else>
                                            "{{ truncate(value) }}"
                                            <span class="text-gray-500 text-xs ml-1">({{ value.length }} chars)</span>
                                        </template>
                                    </div>
                                </template>
                                <template v-else-if="typeof value === 'object' && value !== null">
                                    <div class="ml-4 border-l border-gray-200 dark:border-telescope-border pl-3">
                                        <JsonViewer :data="value" :depth="depth + 1" :max-depth="maxDepth" />
                                    </div>
                                </template>
                                <template v-else>
                                    <span v-if="value === null" class="json-null">null</span>
                                    <span v-else-if="typeof value === 'boolean'" class="json-boolean">{{ value }}</span>
                                    <span v-else-if="typeof value === 'number'" class="json-number">{{ value }}</span>
                                    <span v-else class="json-string">"{{ value }}"</span>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex items-center">
                            <span class="json-key mr-2">"{{ key }}":</span>
                            <button
                                class="text-xs text-telescope-accent hover:text-telescope-accent-light"
                                @click="toggleExpand(key)"
                            >
                                {{ expandedKeys[key] ? 'Collapse' : `{...} (${Object.keys(value).length} keys)` }}
                            </button>
                        </div>
                        <div v-if="expandedKeys[key]" class="ml-4 border-l border-gray-200 dark:border-telescope-border pl-3">
                            <JsonViewer :data="value" :depth="depth + 1" :max-depth="maxDepth + 3" />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const props = defineProps({
    data: { default: null },
    depth: { type: Number, default: 0 },
    maxDepth: { type: Number, default: 4 },
});

const expandedKeys = reactive({});

function toggleExpand(key) {
    expandedKeys[key] = !expandedKeys[key];
}

function isLongString(value) {
    return typeof value === 'string' && value.length > 200;
}

function truncate(value, length = 200) {
    if (typeof value !== 'string') return value;
    return value.length > length ? value.substring(0, length) + '...' : value;
}
</script>
