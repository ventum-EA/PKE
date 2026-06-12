<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const props = defineProps({ data: Array });

const maxGames = computed(() => Math.max(...(props.data || []).map(d => d.total_games), 1));
const chartData = computed(() => (props.data || []).slice(-30));

// Localized short date for axis labels and bar tooltips
// (raw ISO strings like "2026-06-07" are a backend representation,
// not something users should see).
const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    if (isNaN(d)) return iso;
    return d.toLocaleDateString(locale.value === 'lv' ? 'lv-LV' : 'en-GB', {
        day: 'numeric',
        month: 'short',
    });
};
</script>

<template>
    <div v-if="chartData.length" class="space-y-3">
        <div class="flex items-end gap-[2px] h-32">
            <!-- h-full on each column is required: the bar heights are
                 percentages and need a definite parent height to resolve. -->
            <div v-for="day in chartData" :key="day.date"
                class="flex-1 h-full flex flex-col items-center justify-end gap-[1px]"
                :title="`${formatDate(day.date)} · ${day.total_games} / ${day.wins}`">
                <div class="w-full rounded-t bg-zinc-700/50 relative overflow-hidden"
                    :style="{ height: Math.max(4, (day.total_games / maxGames) * 100) + '%' }">
                    <div class="absolute bottom-0 w-full bg-gradient-to-t from-amber-500 to-amber-400 rounded-t"
                        :style="{ height: (day.wins / Math.max(day.total_games, 1)) * 100 + '%' }"></div>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between text-[10px] text-zinc-600">
            <span>{{ formatDate(chartData[0]?.date) }}</span>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ t('dashboard.wins') }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-zinc-700"></span> {{ t('common.total') }}</span>
            </div>
            <span>{{ formatDate(chartData[chartData.length - 1]?.date) }}</span>
        </div>
    </div>
    <div v-else class="text-center py-8 text-zinc-600 text-sm">{{ t('dashboard.no_progress_data') }}</div>
</template>
