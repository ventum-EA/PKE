<script setup>
import { ref, computed, toRefs } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFocusTrap } from '../composables/useFocusTrap';

const { t } = useI18n();
const props = defineProps({ show: Boolean });
const emit = defineEmits(['close']);

const dialogRef = ref(null);
const { show } = toRefs(props);
const isActive = computed(() => show.value);

useFocusTrap(dialogRef, {
    active: isActive,
    onEscape: () => emit('close'),
});

const shortcutGroups = [
    {
        title: t('shortcuts.general'),
        items: [
            { keys: ['?'], desc: t('shortcuts.show_help') },
            { keys: ['Esc'], desc: t('shortcuts.close_dialog') },
        ],
    },
    {
        title: t('shortcuts.navigation'),
        items: [
            { keys: ['g', 'd'], desc: t('shortcuts.go_panel') },
            { keys: ['g', 'g'], desc: t('shortcuts.go_game_list') },
            { keys: ['g', 'p'], desc: t('shortcuts.play_stockfish') },
            { keys: ['g', 'm'], desc: t('shortcuts.go_multiplayer') },
            { keys: ['g', 'y'], desc: t('shortcuts.go_daily') },
            { keys: ['g', 't'], desc: t('shortcuts.go_training') },
            { keys: ['g', 'u'], desc: t('shortcuts.go_puzzles') },
            { keys: ['g', 'e'], desc: t('shortcuts.go_endgame') },
            { keys: ['g', 's'], desc: t('shortcuts.go_scenario') },
            { keys: ['g', 'o'], desc: t('shortcuts.go_opening_lib') },
            { keys: ['g', 'a'], desc: t('shortcuts.go_achievements') },
            { keys: ['g', 'r'], desc: t('shortcuts.go_profile') },
        ],
    },
    {
        title: t('shortcuts.analysis_log'),
        items: [
            { keys: ['←'], desc: t('shortcuts.board_prev') },
            { keys: ['→'], desc: t('shortcuts.board_next') },
            { keys: ['Home'], desc: t('shortcuts.board_first') },
            { keys: ['End'], desc: t('shortcuts.board_last') },
        ],
    },
];
</script>

<template>
    <Teleport to="body">
        <Transition name="shortcuts-modal" appear>
            <div v-if="show"
                class="fixed inset-0 z-[65] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                @click.self="emit('close')">
                <div ref="dialogRef"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="shortcuts-modal-title"
                    class="bg-zinc-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl focus:outline-none max-h-[85vh] overflow-y-auto">
                    <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between sticky top-0 bg-zinc-900 z-10">
                        <h2 id="shortcuts-modal-title" class="text-base font-black text-white flex items-center gap-2">
                            <span class="text-amber-400" aria-hidden="true">⌨</span>
                            Tastatūras saīsnes
                        </h2>
                        <button type="button" @click="emit('close')"
                            aria-label="Aizvērt palīdzības logu"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg text-zinc-500 border border-white/5 hover:text-white hover:border-white/20 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <div v-for="group in shortcutGroups" :key="group.title">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-3">
                                {{ group.title }}
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="item in group.items" :key="item.desc"
                                    class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-zinc-300">{{ item.desc }}</span>
                                    <span class="flex items-center gap-1 shrink-0">
                                        <kbd v-for="(k, ki) in item.keys" :key="ki"
                                            class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 text-[11px] font-mono font-bold bg-zinc-800 text-amber-400 border border-white/10 rounded shadow-sm">
                                            {{ k }}
                                        </kbd>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <p class="text-[10px] text-zinc-600 italic leading-relaxed pt-2 border-t border-white/5">
                            Padoms: tastatūras saīsnes nedarbojas, kamēr raksti ievades laukā. Pēc <kbd class="font-mono">g</kbd> tev ir viena sekunde, lai ievadītu otro burtu.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.shortcuts-modal-enter-active,
.shortcuts-modal-leave-active {
    transition: opacity 180ms ease;
}
.shortcuts-modal-enter-active > div,
.shortcuts-modal-leave-active > div {
    transition: transform 220ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity 180ms ease;
}
.shortcuts-modal-enter-from,
.shortcuts-modal-leave-to {
    opacity: 0;
}
.shortcuts-modal-enter-from > div,
.shortcuts-modal-leave-to > div {
    transform: scale(0.92) translateY(8px);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .shortcuts-modal-enter-active,
    .shortcuts-modal-leave-active,
    .shortcuts-modal-enter-active > div,
    .shortcuts-modal-leave-active > div {
        transition: opacity 120ms ease;
    }
    .shortcuts-modal-enter-from > div,
    .shortcuts-modal-leave-to > div {
        transform: none;
    }
}
</style>
