<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t, locale } = useI18n();
const patterns = ref([]);
const isLoading = ref(true);
const isDetecting = ref(false);

const severityStyle = {
    1: { bg: 'bg-blue-500/5', border: 'border-blue-500/10', text: 'text-blue-400', label: '●' },
    2: { bg: 'bg-amber-500/5', border: 'border-amber-500/10', text: 'text-amber-400', label: '●●' },
    3: { bg: 'bg-red-500/5', border: 'border-red-500/10', text: 'text-red-400', label: '●●●' },
};

const hasPatterns = computed(() => patterns.value.length > 0);

function desc(p) { return locale.value === 'lv' && p.description_lv ? p.description_lv : p.description; }
function sug(p) { return locale.value === 'lv' && p.suggestion_lv ? p.suggestion_lv : p.suggestion; }

async function loadPatterns() {
    try {
        const { data } = await api.get('/patterns');
        patterns.value = data.patterns || [];
    } catch {}
    isLoading.value = false;
}

async function runDetection() {
    isDetecting.value = true;
    try {
        const { data } = await api.post('/patterns/detect');
        patterns.value = data.patterns || [];
    } catch {}
    isDetecting.value = false;
}

onMounted(loadPatterns);
</script>

<template>
    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-zinc-500 flex items-center gap-2">
                <span class="text-sm">🎯</span> {{ $t('patterns.title') }}
            </h3>
            <button @click="runDetection" :disabled="isDetecting"
                class="text-[10px] font-bold text-zinc-600 hover:text-amber-400 transition-colors disabled:opacity-40">
                {{ isDetecting ? '...' : '↻ ' + $t('patterns.refresh') }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="p-5 space-y-3">
            <div v-for="i in 3" :key="i" class="h-16 bg-zinc-800/30 rounded-xl animate-pulse"></div>
        </div>

        <!-- Patterns list -->
        <div v-else-if="hasPatterns" class="divide-y divide-white/5">
            <div v-for="p in patterns.slice(0, 5)" :key="p.id || p.pattern_type"
                :class="['px-5 py-4', severityStyle[p.severity]?.bg || 'bg-transparent']">
                <div class="flex items-start gap-3">
                    <span :class="['text-[10px] font-black mt-0.5 shrink-0', severityStyle[p.severity]?.text || 'text-zinc-500']">
                        {{ severityStyle[p.severity]?.label || '●' }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-300 leading-relaxed">{{ desc(p) }}</p>
                        <p v-if="sug(p)" class="text-[11px] text-zinc-500 mt-1.5 flex items-start gap-1.5">
                            <span class="text-amber-400/60 shrink-0">💡</span>
                            {{ sug(p) }}
                        </p>
                        <p class="text-[10px] text-zinc-600 mt-1 tabular-nums">
                            {{ p.occurrences }} {{ $t('patterns.occurrences') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="px-5 py-8 text-center">
            <p class="text-2xl mb-2 opacity-30">🎯</p>
            <p class="text-sm text-zinc-600 mb-3">{{ $t('patterns.empty') }}</p>
            <p class="text-[11px] text-zinc-700">{{ $t('patterns.empty_hint') }}</p>
        </div>
    </div>
</template>
