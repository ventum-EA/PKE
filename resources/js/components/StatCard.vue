<script setup>
import { toRef } from 'vue';
import { useCountUp } from '../composables/useCountUp';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: { type: String, default: '◈' },
    color: { type: String, default: 'amber' },
});

const valueRef = toRef(props, 'value');
const { display } = useCountUp(() => valueRef.value);
</script>

<template>
    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 hover:border-white/10 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/20 transition-all duration-300 group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl opacity-60 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300" aria-hidden="true">{{ icon }}</span>
            <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600">{{ title }}</span>
        </div>
        <p class="text-2xl font-black tabular-nums" :class="{
            'text-amber-400': color === 'amber',
            'text-emerald-400': color === 'emerald',
            'text-red-400': color === 'red',
            'text-blue-400': color === 'blue',
            'text-purple-400': color === 'purple',
        }">{{ display }}</p>
    </div>
</template>
