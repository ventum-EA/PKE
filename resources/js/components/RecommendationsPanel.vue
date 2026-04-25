<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t } = useI18n();
const recommendations = ref([]);
const summary = ref(null);
const loading = ref(true);
const error = ref(false);

const priorityColors = {
    high: 'text-red-400 bg-red-500/10 border-red-500/20',
    medium: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
    low: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
};

const categoryIcons = {
    tactical: '⚔',
    positional: '♟',
    opening: '📖',
    endgame: '♚',
    general: '💡',
};

onMounted(async () => {
    try {
        const { data } = await api.get('/recommendations');
        recommendations.value = data.recommendations || [];
        summary.value = data.summary || null;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <section class="space-y-3">
        <h3 class="text-sm font-black uppercase tracking-wider text-zinc-400">
            {{ $t('recommendations.title') }}
        </h3>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center gap-2 text-zinc-500 text-sm py-4">
            <div class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            {{ $t('common.loading') }}
        </div>

        <!-- Error -->
        <p v-else-if="error" class="text-zinc-500 text-sm py-2">
            {{ $t('recommendations.error') }}
        </p>

        <!-- No recommendations -->
        <p v-else-if="recommendations.length === 0" class="text-zinc-500 text-sm py-2">
            {{ $t('recommendations.none') }}
        </p>

        <!-- Recommendation cards -->
        <div v-else class="space-y-2">
            <router-link
                v-for="(rec, i) in recommendations"
                :key="i"
                :to="rec.action_url || '/training'"
                class="block p-3 sm:p-4 rounded-xl border transition-all hover:scale-[1.01] hover:shadow-lg"
                :class="priorityColors[rec.priority] || priorityColors.low"
            >
                <div class="flex items-start gap-3">
                    <span class="text-xl flex-shrink-0 mt-0.5" aria-hidden="true">
                        {{ categoryIcons[rec.category] || '💡' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <h4 class="font-bold text-sm text-white leading-tight">
                            {{ rec.title }}
                        </h4>
                        <p class="text-xs mt-1 opacity-80 leading-relaxed">
                            {{ rec.message }}
                        </p>
                        <span
                            class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                            :class="{
                                'bg-red-500/20 text-red-300': rec.priority === 'high',
                                'bg-amber-500/20 text-amber-300': rec.priority === 'medium',
                                'bg-emerald-500/20 text-emerald-300': rec.priority === 'low',
                            }"
                        >
                            {{ $t(`recommendations.priority_${rec.priority}`) }}
                        </span>
                    </div>
                </div>
            </router-link>
        </div>
    </section>
</template>
