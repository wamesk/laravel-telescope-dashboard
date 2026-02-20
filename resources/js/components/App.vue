<template>
    <div class="flex h-screen overflow-hidden">
        <Sidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />

        <main class="flex-1 overflow-y-auto" :class="sidebarCollapsed ? 'ml-16' : 'ml-64'">
            <div class="p-6">
                <router-view />
            </div>
        </main>

        <!-- Mobile overlay -->
        <div
            v-if="!sidebarCollapsed"
            class="fixed inset-0 bg-black/50 z-20 lg:hidden"
            @click="sidebarCollapsed = true"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from './Sidebar.vue';

const sidebarCollapsed = ref(window.innerWidth < 1024);
</script>
