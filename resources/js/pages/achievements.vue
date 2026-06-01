<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t, locale } = useI18n();

const achievements = ref([]);
const isLoading = ref(true);
const activeCategory = ref('all');

const categories = computed(() => [
    { key: 'all', icon: '◈' },
    { key: 'games', icon: '♟' },
    { key: 'analysis', icon: '🔍' },
    { key: 'training', icon: '⚡' },
    { key: 'streaks', icon: '🔥' },
    { key: 'openings', icon: '📖' },
]);

const tierStyle = {
    bronze: {
        card: 'bg-gradient-to-br from-orange-500/[0.04] to-transparent border-orange-500/15 hover:border-orange-500/25',
        cardLocked: 'bg-zinc-900/30 border-white/[0.04]',
        text: 'text-orange-400',
        bar: 'bg-gradient-to-r from-orange-400 to-orange-500',
        badge: 'text-orange-400/80 bg-orange-500/8 border-orange-500/15',
        glow: 'shadow-orange-500/5',
    },
    silver: {
        card: 'bg-gradient-to-br from-zinc-400/[0.04] to-transparent border-zinc-400/15 hover:border-zinc-400/25',
        cardLocked: 'bg-zinc-900/30 border-white/[0.04]',
        text: 'text-zinc-300',
        bar: 'bg-gradient-to-r from-zinc-300 to-zinc-400',
        badge: 'text-zinc-300/80 bg-zinc-400/8 border-zinc-400/15',
        glow: 'shadow-zinc-400/5',
    },
    gold: {
        card: 'bg-gradient-to-br from-amber-400/[0.06] to-transparent border-amber-400/20 hover:border-amber-400/30',
        cardLocked: 'bg-zinc-900/30 border-white/[0.04]',
        text: 'text-amber-400',
        bar: 'bg-gradient-to-r from-amber-400 to-amber-500',
        badge: 'text-amber-400/80 bg-amber-400/8 border-amber-400/20',
        glow: 'shadow-amber-400/10',
    },
};

const filtered = computed(() => {
    if (activeCategory.value === 'all') return achievements.value;
    return achievements.value.filter(a => a.category === activeCategory.value);
});

const unlockedCount = computed(() => achievements.value.filter(a => a.user_unlocked).length);
const totalCount = computed(() => achievements.value.length);
const completionPct = computed(() => totalCount.value > 0 ? Math.round((unlockedCount.value / totalCount.value) * 100) : 0);

function catCount(key) {
    if (key === 'all') return achievements.value.filter(a => a.user_unlocked).length;
    return achievements.value.filter(a => a.category === key && a.user_unlocked).length;
}
function catTotal(key) {
    if (key === 'all') return achievements.value.length;
    return achievements.value.filter(a => a.category === key).length;
}

function name(a) { return locale.value === 'lv' ? a.name_lv : a.name; }
function desc(a) { return locale.value === 'lv' ? a.description_lv : a.description; }
function pct(a) {
    if (a.user_unlocked) return 100;
    return a.threshold > 0 ? Math.min(100, Math.round((a.user_progress / a.threshold) * 100)) : 0;
}
function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString(locale.value === 'lv' ? 'lv-LV' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

onMounted(async () => {
    try {
        const { data } = await api.get('/achievements');
        achievements.value = data.achievements || [];
    } catch { /* intentionally silenced */ }
    isLoading.value = false;
});
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-6 sm:mb-10">
                <h1 class="text-2xl sm:text-4xl font-black tracking-tight">
                    <span class="text-amber-400">🏆</span> {{ $t('achievements.title') }}
                </h1>
                <p class="text-zinc-500 text-xs sm:text-sm mt-1 uppercase tracking-widest font-bold">
                    {{ unlockedCount }} / {{ totalCount }} {{ $t('achievements.unlocked_label') }}
                </p>
            </div>

            <!-- Overall progress -->
            <div class="mb-6 sm:mb-8 bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-5 sm:p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-zinc-500">{{ $t('achievements.overall_progress') }}</span>
                    <span class="text-base sm:text-lg font-black text-amber-400 tabular-nums">{{ completionPct }}%</span>
                </div>
                <div class="h-2.5 bg-zinc-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full transition-all duration-1000 ease-out" :style="{ width: completionPct + '%' }"></div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-[10px] text-zinc-600">0</span>
                    <span class="text-[10px] text-zinc-600">{{ totalCount }}</span>
                </div>
            </div>

            <!-- Category filter pills -->
            <div class="flex flex-wrap gap-2 mb-6 sm:mb-8">
                <button v-for="cat in categories" :key="cat.key" @click="activeCategory = cat.key"
                    :class="['px-3 sm:px-4 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5',
                        activeCategory === cat.key
                            ? 'text-amber-400 bg-amber-500/10 border-amber-500/20 shadow-lg shadow-amber-500/5'
                            : 'text-zinc-500 border-white/5 hover:text-zinc-300 hover:border-white/10']">
                    <span>{{ cat.icon }}</span>
                    <span>{{ $t('achievements.cat_' + cat.key) }}</span>
                    <span class="text-[9px] font-mono tabular-nums opacity-60 ml-0.5">{{ catCount(cat.key) }}/{{ catTotal(cat.key) }}</span>
                </button>
            </div>

            <!-- Loading skeleton -->
            <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4" aria-busy="true">
                <div v-for="i in 8" :key="i" class="bg-zinc-900/30 border border-white/5 rounded-2xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-zinc-800/40 rounded-xl animate-pulse"></div>
                        <div class="flex-1">
                            <div class="h-4 w-28 bg-zinc-800/40 rounded animate-pulse mb-2"></div>
                            <div class="h-3 w-40 bg-zinc-800/30 rounded animate-pulse mb-3"></div>
                            <div class="h-1.5 bg-zinc-800/30 rounded-full animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievement grid -->
            <div v-else-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div v-for="a in filtered" :key="a.slug"
                    :class="['rounded-2xl border p-4 sm:p-5 transition-all duration-300',
                        a.user_unlocked
                            ? (tierStyle[a.tier]?.card || tierStyle.bronze.card) + ' hover:-translate-y-0.5 hover:shadow-xl ' + (tierStyle[a.tier]?.glow || '')
                            : (tierStyle[a.tier]?.cardLocked || 'bg-zinc-900/30 border-white/5')]">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <!-- Icon -->
                        <div :class="['w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0 transition-all duration-300',
                            a.user_unlocked ? 'bg-black/20' : 'bg-black/10 grayscale opacity-40']">
                            {{ a.icon }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <!-- Title + tier badge -->
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-bold truncate" :class="a.user_unlocked ? 'text-white' : 'text-zinc-500'">{{ name(a) }}</p>
                                <span :class="['text-[8px] font-black uppercase px-1.5 py-0.5 rounded-full border tracking-wider shrink-0',
                                    tierStyle[a.tier]?.badge || tierStyle.bronze.badge]">
                                    {{ a.tier }}
                                </span>
                            </div>

                            <!-- Description -->
                            <p class="text-[11px] leading-relaxed mb-2.5" :class="a.user_unlocked ? 'text-zinc-400' : 'text-zinc-600'">{{ desc(a) }}</p>

                            <!-- Progress bar -->
                            <div class="flex items-center gap-2.5">
                                <div class="flex-1 h-1.5 bg-zinc-800/80 rounded-full overflow-hidden">
                                    <div :class="['h-full rounded-full transition-all duration-700 ease-out', a.user_unlocked ? (tierStyle[a.tier]?.bar || 'bg-orange-400') : 'bg-zinc-600']"
                                        :style="{ width: pct(a) + '%' }"></div>
                                </div>
                                <span class="text-[10px] font-mono tabular-nums shrink-0 min-w-[2.5rem] text-right" :class="a.user_unlocked ? (tierStyle[a.tier]?.text || 'text-orange-400') + ' font-bold' : 'text-zinc-600'">
                                    {{ a.user_unlocked ? '✓' : a.user_progress + '/' + a.threshold }}
                                </span>
                            </div>

                            <!-- Unlocked date -->
                            <p v-if="a.unlocked_at" class="text-[9px] text-zinc-600 mt-2">
                                {{ $t('achievements.unlocked_on') }} {{ fmtDate(a.unlocked_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state for category -->
            <div v-else class="text-center py-16">
                <p class="text-3xl mb-3 opacity-30">🏆</p>
                <p class="text-sm text-zinc-600">{{ $t('achievements.empty_category') }}</p>
            </div>
        </div>
    </div>
</template>
