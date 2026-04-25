<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: 'inbox' },
    actionLabel: { type: String, default: '' },
    actionTo: { type: [String, Object], default: null },
});
const emit = defineEmits(['action']);

const onAction = () => emit('action');
</script>

<template>
    <div class="text-center py-12 px-6">
        <div class="w-16 h-16 mx-auto mb-4 bg-zinc-800/50 rounded-2xl flex items-center justify-center" aria-hidden="true">
            <!-- Inbox icon (default) -->
            <svg v-if="icon === 'inbox'" class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-5l-2 2H9l-2-2H4" />
            </svg>
            <!-- Chess board icon -->
            <svg v-else-if="icon === 'board'" class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 3v18m6-18v18M3 9h18M3 15h18M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
            </svg>
            <!-- Trophy icon -->
            <svg v-else-if="icon === 'trophy'" class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 4h14l-1 11a4 4 0 01-4 4h-4a4 4 0 01-4-4L5 4zM9 19v2h6v-2" />
            </svg>
            <!-- Search icon -->
            <svg v-else-if="icon === 'search'" class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <!-- Chart icon -->
            <svg v-else-if="icon === 'chart'" class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </div>

        <h3 class="text-white font-bold text-lg mb-1">{{ title }}</h3>
        <p v-if="description" class="text-zinc-500 text-sm mb-6 max-w-sm mx-auto">{{ description }}</p>

        <router-link
            v-if="actionLabel && actionTo"
            :to="actionTo"
            class="inline-block px-5 py-2.5 bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold rounded-xl hover:bg-amber-500/20 transition-colors text-sm"
        >
            {{ actionLabel }}
        </router-link>
        <button
            v-else-if="actionLabel"
            type="button"
            @click="onAction"
            class="inline-block px-5 py-2.5 bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold rounded-xl hover:bg-amber-500/20 transition-colors text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
        >
            {{ actionLabel }}
        </button>
    </div>
</template>
