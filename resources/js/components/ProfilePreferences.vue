<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotification } from '../composables/useNotification';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';
import { useBoardTheme, BOARD_THEMES, PIECE_STYLES } from '../composables/useBoardTheme';
import api from '../services/api';

const { t, locale } = useI18n();
const { notify } = useNotification();
const authStore = useAuthStore();
const { theme, toggleTheme } = useTheme();
const { setTheme, setPieceStyle, themeKey, pieceKey, boardColors, pieceColors } = useBoardTheme();

const settings = defineModel({ type: Object, required: true });

const SKILL_LABELS = {
    0: 'Iesācējs (400)', 3: 'Vājš (700)', 5: 'Vidējs (1000)', 8: 'Spēcīgs (1400)',
    10: 'Pieredzējis (1600)', 13: 'Eksperts (1900)', 16: 'Meistars (2200)', 20: 'Lielmeistars (2800)',
};

function getDifficultyLabel(val) {
    const keys = Object.keys(SKILL_LABELS).map(Number).sort((a, b) => a - b);
    let label = SKILL_LABELS[0];
    for (const k of keys) { if (val >= k) label = SKILL_LABELS[k]; }
    return label;
}

const isSavingSettings = ref(false);
const saveSettings = async () => {
    isSavingSettings.value = true;
    try {
        settings.value.dark_mode = theme.value === 'dark';
        settings.value.board_theme = themeKey.value;
        settings.value.piece_style = pieceKey.value;
        await authStore.updateSettings(settings.value);
        notify(t('profile.settings_saved'), 'success');
    } catch {
        notify(t('profile.settings_error'), 'error');
    } finally {
        isSavingSettings.value = false;
    }
};

function handleDarkModeToggle() {
    toggleTheme();
    settings.value.dark_mode = theme.value === 'dark';
}

function handleBoardTheme(key) {
    setTheme(key);
    settings.value.board_theme = key;
}

function handlePieceStyle(key) {
    setPieceStyle(key);
    settings.value.piece_style = key;
}

const isExporting = ref(false);
async function exportAllGames() {
    isExporting.value = true;
    try {
        const { data } = await api.get('/games', { perPage: 999 });
        const games = data?.games?.data || data?.data || [];
        if (!games.length) { notify(t('profile.export_empty'), 'info'); return; }
        const pgns = games.filter(g => g.pgn).map(g => g.pgn).join('\n\n');
        const blob = new Blob([pgns], { type: 'application/x-chess-pgn' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url; link.download = 'all-games.pgn';
        document.body.appendChild(link); link.click(); link.remove();
        URL.revokeObjectURL(url);
        notify(t('profile.export_done', { count: games.length }), 'success');
    } catch {
        notify(t('profile.export_error'), 'error');
    } finally {
        isExporting.value = false;
    }
}

const themeLabelKey = (key) => locale.value === 'lv' ? (BOARD_THEMES[key]?.label_lv || key) : (BOARD_THEMES[key]?.label || key);
const pieceLabelKey = (key) => locale.value === 'lv' ? (PIECE_STYLES[key]?.label_lv || key) : (PIECE_STYLES[key]?.label || key);
</script>

<template>
    <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8">
        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.settings') }}</h3>

        <div class="space-y-8">
            <!-- ═══ Appearance ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">🎨</span> {{ $t('settings.appearance') }}
                </h4>
                <div class="space-y-5 pl-1">
                    <!-- Dark mode -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">{{ $t('settings.dark_mode') }}</p>
                            <p class="text-xs text-zinc-600">{{ $t('settings.dark_mode_desc') }}</p>
                        </div>
                        <button type="button" @click="handleDarkModeToggle"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', theme === 'dark' ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', theme === 'dark' ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>

                    <!-- Sound -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">{{ $t('settings.sound') || 'Skaņas' }}</p>
                            <p class="text-xs text-zinc-600">{{ $t('settings.sound_desc') || 'Spēles skaņas efekti' }}</p>
                        </div>
                        <button type="button" @click="settings.sound_enabled = !settings.sound_enabled"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.sound_enabled ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.sound_enabled ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>

                    <!-- Font size -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.font_size') || 'Fonta lielums' }}</p>
                        <div class="flex gap-2">
                            <button v-for="fs in ['small', 'medium', 'large']" :key="fs"
                                @click="settings.font_size = fs"
                                :class="['px-4 py-2 rounded-xl font-bold text-sm border transition-all',
                                    settings.font_size === fs ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ fs === 'small' ? ($t('settings.font_small') || 'S') : fs === 'medium' ? ($t('settings.font_medium') || 'M') : ($t('settings.font_large') || 'L') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Board & Pieces ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">♜</span> {{ $t('settings.board_style') || 'Galdiņa stils' }}
                </h4>
                <div class="space-y-5 pl-1">
                    <!-- Board theme -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-3">{{ $t('settings.board_theme') || 'Galdiņa tēma' }}</p>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <button v-for="(bt, key) in BOARD_THEMES" :key="key" @click="handleBoardTheme(key)"
                                :class="['rounded-xl border-2 p-1 transition-all',
                                    themeKey === key ? 'border-amber-400 shadow-lg shadow-amber-400/10' : 'border-transparent hover:border-white/10']">
                                <div class="grid grid-cols-2 rounded-lg overflow-hidden aspect-square">
                                    <div :style="{ backgroundColor: bt.light }" class="aspect-square"></div>
                                    <div :style="{ backgroundColor: bt.dark }" class="aspect-square"></div>
                                    <div :style="{ backgroundColor: bt.dark }" class="aspect-square"></div>
                                    <div :style="{ backgroundColor: bt.light }" class="aspect-square"></div>
                                </div>
                                <p class="text-[9px] font-bold text-center mt-1" :class="themeKey === key ? 'text-amber-400' : 'text-zinc-500'">{{ themeLabelKey(key) }}</p>
                            </button>
                        </div>
                    </div>

                    <!-- Piece style -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-3">{{ $t('settings.piece_style') || 'Figūru stils' }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button v-for="(ps, key) in PIECE_STYLES" :key="key" @click="handlePieceStyle(key)"
                                :class="['rounded-xl border-2 p-3 transition-all flex items-center gap-2',
                                    pieceKey === key ? 'border-amber-400 bg-amber-500/5' : 'border-white/5 hover:border-white/10']">
                                <span class="text-2xl" :style="{ color: ps.white.fill, textShadow: `0 0 2px ${ps.white.stroke}` }">♔</span>
                                <span class="text-2xl" :style="{ color: ps.black.fill, textShadow: `0 0 2px ${ps.black.stroke}` }">♚</span>
                                <span class="text-[10px] font-bold ml-auto" :class="pieceKey === key ? 'text-amber-400' : 'text-zinc-500'">{{ pieceLabelKey(key) }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Game Defaults ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">♚</span> {{ $t('settings.game') }}
                </h4>
                <div class="space-y-5 pl-1">
                    <!-- Preferred color -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.preferred_color') }}</p>
                        <div class="flex gap-3">
                            <button type="button" v-for="c in ['white', 'black']" :key="c" @click="settings.preferred_color = c"
                                :class="['flex-1 sm:flex-none px-5 py-2.5 rounded-xl font-bold text-sm border transition-all',
                                    settings.preferred_color === c ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ c === 'white' ? '♔ ' + $t('common.white') : '♚ ' + $t('common.black') }}
                            </button>
                        </div>
                    </div>
                    <!-- Default difficulty -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-bold text-zinc-300">{{ $t('settings.default_difficulty') }}</p>
                            <span class="text-xs font-bold text-amber-400">{{ getDifficultyLabel(settings.default_difficulty) }}</span>
                        </div>
                        <input type="range" v-model.number="settings.default_difficulty" min="0" max="20" step="1" class="w-full accent-amber-500" />
                        <div class="flex justify-between text-[10px] text-zinc-600 mt-1">
                            <span>{{ $t('settings.easier') }}</span>
                            <span>{{ $t('settings.harder') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Data ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">📦</span> {{ $t('settings.data_tools') }}
                </h4>
                <button type="button" @click="exportAllGames" :disabled="isExporting"
                    class="w-full flex items-center justify-between p-4 rounded-xl border border-white/5 hover:border-amber-500/20 transition-all group">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📤</span>
                        <div class="text-left">
                            <p class="text-sm font-bold text-zinc-300 group-hover:text-amber-400 transition-colors">{{ $t('settings.export_games') }}</p>
                            <p class="text-xs text-zinc-600">{{ $t('settings.export_games_desc') }}</p>
                        </div>
                    </div>
                    <span v-if="isExporting" class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin shrink-0"></span>
                    <span v-else class="text-zinc-600 group-hover:text-amber-400 transition-colors">→</span>
                </button>
            </div>

            <button type="button" @click="saveSettings" :disabled="isSavingSettings"
                class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500">
                {{ isSavingSettings ? $t('common.saving') : $t('profile.save_settings') }}
            </button>
        </div>
    </section>
</template>
