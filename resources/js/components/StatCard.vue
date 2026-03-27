<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: { type: String, default: '◈' },
    color: { type: String, default: 'amber' },
});
</script>

<template>
    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl opacity-60 group-hover:opacity-100 transition-opacity">{{ icon }}</span>
            <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600">{{ title }}</span>
        </div>
        <p class="text-2xl font-black" :class="{
            'text-amber-400': color === 'amber',
            'text-emerald-400': color === 'emerald',
            'text-red-400': color === 'red',
            'text-blue-400': color === 'blue',
            'text-purple-400': color === 'purple',
        }">{{ value }}</p>
    </div>
</template>
