<script setup>
import { ref } from 'vue';
const emit = defineEmits(['search']);
const query = ref('');
const handleInput = () => emit('search', query.value);
</script>

<template>
    <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-600">🔍</span>
        <input v-model="query" @input="handleInput" type="text" placeholder="Meklēt..."
            class="w-full bg-zinc-900 border border-white/10 rounded-xl pl-11 pr-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 transition-all" />
    </div>
</template>
