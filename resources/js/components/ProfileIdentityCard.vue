<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    user: { type: Object, required: true },
    eloHistory: { type: Array, default: () => [] },
    eloHistoryError: { type: Boolean, default: false },
});

const initial = computed(() => props.user?.name?.charAt(0).toUpperCase() || '?');
const registeredDate = computed(() => props.user?.created_at?.split(/[T ]/)[0] || '—');
</script>

<template>
    <!-- Identity card -->
    <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-black text-2xl sm:text-3xl font-black shrink-0 shadow-lg shadow-amber-500/20"
                aria-hidden="true">
                {{ initial }}
            </div>
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-black text-white truncate">{{ user?.name }}</h2>
                <p class="text-sm text-zinc-500">{{ user?.email }}</p>
                <div class="flex items-center gap-3 mt-1">
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                        ♔ {{ user?.elo_rating || 1200 }} ELO
                    </span>
                    <span class="text-xs text-zinc-600">{{ $t('profile.member_since') }} {{ registeredDate }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ELO History -->
    <section v-if="eloHistory.length" class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('elo.history') }}</h3>
        <div class="flex flex-wrap gap-3">
            <div v-for="entry in eloHistory" :key="entry.id || entry.created_at"
                class="flex items-center gap-2 bg-zinc-800/50 rounded-xl px-3 py-2 text-sm border border-white/5">
                <span :class="[entry.change > 0 ? 'text-green-400' : entry.change < 0 ? 'text-red-400' : 'text-zinc-500', 'font-mono font-bold']">
                    {{ entry.change > 0 ? '+' : '' }}{{ entry.change }}
                </span>
                <span class="text-zinc-500 text-xs">→ {{ entry.new_elo }}</span>
            </div>
        </div>
    </section>

    <!-- ELO history error state -->
    <section v-else-if="eloHistoryError"
        class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
        <p class="text-sm text-zinc-500 text-center">{{ $t('elo.load_error') }}</p>
    </section>
</template>
