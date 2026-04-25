<script setup>
defineProps({
    /** Preset shape: 'list', 'card', 'stat', 'board', 'text', 'table' */
    variant: { type: String, default: 'list' },
    /** Number of skeleton rows for list/table variants */
    rows: { type: Number, default: 3 },
    /** Set false to suppress the pulse animation (e.g. while not loading) */
    animate: { type: Boolean, default: true },
});
</script>

<template>
    <div :class="animate ? 'animate-pulse' : ''" aria-busy="true" aria-live="polite">
        <!-- Generic list -->
        <div v-if="variant === 'list'" class="space-y-3">
            <div v-for="i in rows" :key="i" class="h-4 bg-zinc-800 rounded" :style="{ width: `${60 + (i % 3) * 15}%` }"></div>
        </div>

        <!-- Card grid (e.g. games list) -->
        <div v-else-if="variant === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in rows" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                <div class="h-5 bg-zinc-800 rounded w-3/4 mb-3"></div>
                <div class="h-3 bg-zinc-800 rounded w-1/2 mb-4"></div>
                <div class="h-32 bg-zinc-800/50 rounded-xl mb-3"></div>
                <div class="h-3 bg-zinc-800 rounded w-2/3"></div>
            </div>
        </div>

        <!-- Stat cards row (dashboard) -->
        <div v-else-if="variant === 'stat'" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in rows" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                <div class="h-3 bg-zinc-800 rounded w-1/2 mb-3"></div>
                <div class="h-8 bg-zinc-800 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Chess board placeholder -->
        <div v-else-if="variant === 'board'" class="aspect-square w-full max-w-md mx-auto bg-zinc-800 rounded-xl"></div>

        <!-- Text paragraph -->
        <div v-else-if="variant === 'text'" class="space-y-2">
            <div v-for="i in rows" :key="i" class="h-3 bg-zinc-800 rounded" :style="{ width: i === rows ? '50%' : '100%' }"></div>
        </div>

        <!-- Table rows -->
        <div v-else-if="variant === 'table'" class="space-y-2">
            <div v-for="i in rows" :key="i" class="grid grid-cols-4 gap-3 py-2 border-b border-white/5">
                <div class="h-3 bg-zinc-800 rounded col-span-2"></div>
                <div class="h-3 bg-zinc-800 rounded"></div>
                <div class="h-3 bg-zinc-800 rounded"></div>
            </div>
        </div>
    </div>
</template>
