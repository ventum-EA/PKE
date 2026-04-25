<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t } = useI18n();
const report = ref(null);
const loading = ref(true);

const categoryLabels = {
    tactical: { icon: '⚔', key: 'training.cat_tactical' },
    positional: { icon: '♟', key: 'training.cat_positional' },
    opening: { icon: '📖', key: 'training.cat_opening' },
    endgame: { icon: '♚', key: 'training.cat_endgame' },
};

const hasImprovement = computed(() =>
    report.value?.comparison?.some(c => c.improved) ?? false
);

onMounted(async () => {
    try {
        const { data } = await api.get('/training/progress-report');
        report.value = data;
    } catch {
        report.value = { has_data: false };
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="w-8 h-8 border-3 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- No data -->
        <div v-else-if="!report?.has_data"
            class="text-center py-8 px-4 rounded-2xl bg-white/[0.03] border border-white/5">
            <p class="text-zinc-500 text-sm">{{ $t('training.progress_no_data') }}</p>
            <router-link to="/games"
                class="inline-block mt-3 text-amber-400 text-sm font-bold hover:text-amber-300 transition-colors">
                {{ $t('common.go_to_games') }} →
            </router-link>
        </div>

        <template v-else>
            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3 sm:p-4 rounded-xl bg-white/[0.03] border border-white/5 text-center">
                    <div class="text-2xl sm:text-3xl font-black text-amber-400">{{ report.totals.sessions }}</div>
                    <div class="text-[10px] sm:text-xs text-zinc-500 mt-1 uppercase tracking-wider">{{ $t('training.report_sessions') }}</div>
                </div>
                <div class="p-3 sm:p-4 rounded-xl bg-white/[0.03] border border-white/5 text-center">
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400">{{ report.totals.correct }}</div>
                    <div class="text-[10px] sm:text-xs text-zinc-500 mt-1 uppercase tracking-wider">{{ $t('training.report_correct') }}</div>
                </div>
                <div class="p-3 sm:p-4 rounded-xl bg-white/[0.03] border border-white/5 text-center">
                    <div class="text-2xl sm:text-3xl font-black text-blue-400">{{ report.totals.accuracy }}%</div>
                    <div class="text-[10px] sm:text-xs text-zinc-500 mt-1 uppercase tracking-wider">{{ $t('training.report_accuracy') }}</div>
                </div>
            </div>

            <!-- Before/After comparison -->
            <div class="rounded-2xl bg-white/[0.03] border border-white/5 p-4 sm:p-6">
                <h4 class="text-sm font-black uppercase tracking-wider text-zinc-400 mb-4">
                    {{ $t('training.report_comparison') }}
                </h4>

                <div class="space-y-3">
                    <div v-for="cat in report.comparison" :key="cat.category"
                        class="flex items-center gap-3 py-2">
                        <span class="text-lg w-6 text-center flex-shrink-0" aria-hidden="true">
                            {{ categoryLabels[cat.category]?.icon || '📊' }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-bold text-white">
                                    {{ $t(categoryLabels[cat.category]?.key || 'training.cat_positional') }}
                                </span>
                                <span
                                    class="text-xs font-bold"
                                    :class="cat.improved ? 'text-emerald-400' : cat.change_pct === 0 ? 'text-zinc-500' : 'text-red-400'"
                                >
                                    {{ cat.improved ? '↓' : cat.change_pct === 0 ? '—' : '↑' }}
                                    {{ Math.abs(cat.change_pct) }}%
                                </span>
                            </div>
                            <!-- Bar comparison -->
                            <div class="flex gap-1 items-center">
                                <div class="flex-1 bg-zinc-800 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full bg-red-500/60 transition-all duration-700"
                                        :style="{ width: Math.min(cat.before * 10, 100) + '%' }"></div>
                                </div>
                                <span class="text-[10px] text-zinc-600 w-6 text-right flex-shrink-0">{{ $t('training.report_before_short') }}</span>
                            </div>
                            <div class="flex gap-1 items-center mt-0.5">
                                <div class="flex-1 bg-zinc-800 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500/60 transition-all duration-700"
                                        :style="{ width: Math.min(cat.after * 10, 100) + '%' }"></div>
                                </div>
                                <span class="text-[10px] text-zinc-600 w-6 text-right flex-shrink-0">{{ $t('training.report_after_short') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="hasImprovement" class="mt-4 text-xs text-emerald-400/80">
                    ✓ {{ $t('training.report_improved') }}
                </p>
            </div>

            <!-- Weekly trend -->
            <div v-if="report.weekly_trend?.length > 1"
                class="rounded-2xl bg-white/[0.03] border border-white/5 p-4 sm:p-6">
                <h4 class="text-sm font-black uppercase tracking-wider text-zinc-400 mb-3">
                    {{ $t('training.report_trend') }}
                </h4>
                <div class="flex items-end gap-1 h-24">
                    <div v-for="week in report.weekly_trend" :key="week.week"
                        class="flex-1 bg-amber-500/30 rounded-t hover:bg-amber-500/50 transition-colors relative group"
                        :style="{ height: Math.max(week.errors_per_game * 8, 4) + 'px' }">
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-1.5 py-0.5 bg-zinc-800 rounded text-[9px] text-zinc-300 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            {{ week.errors_per_game }} {{ $t('training.report_errors_game') }} · {{ week.games }} {{ $t('games.moves_short') }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-[10px] text-zinc-600">{{ $t('training.report_90_days_ago') }}</span>
                    <span class="text-[10px] text-zinc-600">{{ $t('training.report_today') }}</span>
                </div>
            </div>
        </template>
    </div>
</template>
