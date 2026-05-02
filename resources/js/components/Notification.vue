<script setup>
import { useI18n } from 'vue-i18n';
import { useNotification } from '../composables/useNotification';

const { locale } = useI18n();
const { toasts, dismiss } = useNotification();

const typeStyles = {
    success: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
    error:   'bg-red-500/10 border-red-500/20 text-red-400',
    info:    'bg-blue-500/10 border-blue-500/20 text-blue-400',
    warning: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
};

const typeIcons = {
    success: '✓',
    error:   '✕',
    info:    'ℹ',
    warning: '⚠',
};

const tierGradient = {
    bronze: 'bg-gradient-to-r from-orange-900/60 via-orange-950/40 to-zinc-900/80 border-orange-500/25',
    silver: 'bg-gradient-to-r from-zinc-600/30 via-zinc-800/40 to-zinc-900/80 border-zinc-400/25',
    gold:   'bg-gradient-to-r from-amber-700/40 via-amber-900/30 to-zinc-900/80 border-amber-400/30',
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-[70] flex flex-col-reverse gap-2.5 max-w-sm w-full pointer-events-none"
            aria-live="polite" aria-atomic="false">
            <TransitionGroup
                enter-active-class="transition-all duration-400 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                enter-from-class="translate-x-[110%] opacity-0 scale-95"
                enter-to-class="translate-x-0 opacity-100 scale-100"
                leave-active-class="transition-all duration-250 ease-in"
                leave-from-class="translate-x-0 opacity-100 scale-100"
                leave-to-class="translate-x-[110%] opacity-0 scale-90">

                <template v-for="toast in toasts" :key="toast.id">
                    <!-- Achievement toast -->
                    <div v-if="toast.type === 'achievement'"
                        class="pointer-events-auto rounded-2xl border overflow-hidden shadow-2xl backdrop-blur-xl"
                        :class="tierGradient[toast.achievement?.tier] || tierGradient.bronze"
                        @click="dismiss(toast.id)">
                        <div class="p-4 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-black/20 flex items-center justify-center text-2xl shrink-0">
                                {{ toast.achievement?.icon }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase tracking-[0.15em] text-amber-400/80 leading-none">
                                    {{ $t('notifications.achievement_unlocked') }}
                                </p>
                                <p class="text-sm font-bold text-white mt-1 truncate">
                                    {{ locale === 'lv' ? toast.achievement?.name_lv : toast.achievement?.name }}
                                </p>
                            </div>
                        </div>
                        <div class="h-0.5 bg-black/20">
                            <div class="h-full bg-amber-400/40 toast-progress" :style="{ '--duration': toast.duration + 'ms' }"></div>
                        </div>
                    </div>

                    <!-- Standard toast -->
                    <div v-else
                        class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-xl border text-sm font-bold backdrop-blur-xl cursor-pointer"
                        :class="typeStyles[toast.type] || typeStyles.info"
                        :role="toast.type === 'error' ? 'alert' : 'status'"
                        @click="dismiss(toast.id)">
                        <span class="shrink-0 mt-0.5 text-xs opacity-80">{{ typeIcons[toast.type] || 'ℹ' }}</span>
                        <span class="flex-1 min-w-0">{{ toast.message }}</span>
                    </div>
                </template>

            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-progress {
    animation: toast-shrink var(--duration, 5000ms) linear forwards;
}
@keyframes toast-shrink {
    from { width: 100%; }
    to { width: 0%; }
}
@media (prefers-reduced-motion: reduce) {
    .toast-progress { animation: none; width: 0%; }
}
</style>
