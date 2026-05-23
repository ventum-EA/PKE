<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const props = defineProps({ data: Array });

const categories = computed(() => {
    if (!props.data?.length) return [];
    const cats = {};
    props.data.forEach((item) => {
        const cat = item.error_category || 'unknown';
        if (!cats[cat]) cats[cat] = { total: 0, blunders: 0, mistakes: 0, inaccuracies: 0 };
        cats[cat].total += item.total;
        if (item.classification === 'blunder') cats[cat].blunders += item.total;
        if (item.classification === 'mistake') cats[cat].mistakes += item.total;
        if (item.classification === 'inaccuracy') cats[cat].inaccuracies += item.total;
    });
    return Object.entries(cats).map(([name, data]) => ({ name, ...data }));
});

const maxTotal = computed(() => Math.max(...categories.value.map(c => c.total), 1));

const labels = computed(() => ({ tactical: t('errors.cat_tactical'), positional: t('errors.cat_positional'), opening: t('errors.cat_opening'), endgame: t('errors.cat_endgame') }));
const colors = { tactical: 'bg-amber-400', positional: 'bg-blue-400', opening: 'bg-emerald-400', endgame: 'bg-purple-400' };
</script>

<template>
    <div v-if="categories.length" class="space-y-4">
        <div v-for="cat in categories" :key="cat.name" class="space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400">{{ labels[cat.name] || cat.name }}</span>
                <span class="text-xs font-mono text-zinc-600">{{ cat.total }}</span>
            </div>
            <div class="w-full bg-zinc-800 rounded-full h-3 overflow-hidden flex">
                <div :class="['h-3 transition-all duration-700', colors[cat.name] || 'bg-zinc-400']"
                    :style="{ width: (cat.total / maxTotal * 100) + '%' }"></div>
            </div>
            <div class="flex gap-4 text-[10px] text-zinc-600">
                <span>{{ $t('analysis.blunders_short') }} <span class="text-red-400">{{ cat.blunders }}</span></span>
                <span>{{ $t('analysis.mistakes_short') }} <span class="text-orange-400">{{ cat.mistakes }}</span></span>
                <span>{{ $t('analysis.inaccuracies_short') }} <span class="text-yellow-400">{{ cat.inaccuracies }}</span></span>
            </div>
        </div>
    </div>
    <div v-else class="text-center py-8 text-zinc-600 text-sm">{{ $t('errors.no_error_data') }}</div>
</template>
