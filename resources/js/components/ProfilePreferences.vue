<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotification } from '../composables/useNotification';
import { useAuthStore } from '../stores/auth';
import { BOARD_THEMES, PIECE_STYLES } from '../composables/useBoardTheme';
import api from '../services/api';

const { t, locale } = useI18n();
const { notify } = useNotification();
const authStore = useAuthStore();

const settings = defineModel({ type: Object, required: true });
const emit = defineEmits(['save']);

const SKILL_LABELS = {
    0: 'Beginner (400)', 3: 'Weak (700)', 5: 'Medium (1000)', 8: 'Strong (1400)',
    10: 'Experienced (1600)', 13: 'Expert (1900)', 16: 'Master (2200)', 20: 'Grandmaster (2800)',
};
const difficultyLabel = computed(() => {
    const keys = Object.keys(SKILL_LABELS).map(Number).sort((a, b) => a - b);
    let label = SKILL_LABELS[0];
    for (const k of keys) { if (settings.value.default_difficulty >= k) label = SKILL_LABELS[k]; }
    return label;
});

const isSavingSettings = ref(false);
const saveSettings = async () => {
    isSavingSettings.value = true;
    try {
        await authStore.updateSettings(settings.value);
        notify(t('profile.settings_saved'), 'success');
        emit('save');
    } catch { /* intentionally silenced */ 
        notify(t('profile.settings_error'), 'error');
    } finally {
        isSavingSettings.value = false;
    }
};

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
                        <button type="button" @click="settings.dark_mode = !settings.dark_mode"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.dark_mode ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.dark_mode ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>
                    <!-- High contrast -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">{{ $t('settings.high_contrast') }}</p>
                            <p class="text-xs text-zinc-600">{{ $t('settings.high_contrast_desc') }}</p>
                        </div>
                        <button type="button" @click="settings.high_contrast = !settings.high_contrast"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.high_contrast ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.high_contrast ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>
                    <!-- Font size -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.font_size') }}</p>
                        <div class="flex gap-2">
                            <button v-for="size in ['small', 'medium', 'large']" :key="size" type="button"
                                @click="settings.font_size = size"
                                :class="['px-4 py-2 rounded-xl text-sm font-bold border transition-all',
                                    settings.font_size === size ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ $t('settings.font_' + size) }}
                            </button>
                        </div>
                    </div>
                    <!-- Animation speed -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.animation_speed') }}</p>
                        <div class="flex gap-2">
                            <button v-for="speed in ['off', 'fast', 'normal']" :key="speed" type="button"
                                @click="settings.animation_speed = speed"
                                :class="['px-4 py-2 rounded-xl text-sm font-bold border transition-all',
                                    settings.animation_speed === speed ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ $t('settings.speed_' + speed) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Game Settings ═══ -->
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
                            <span class="text-xs font-bold text-amber-400">{{ difficultyLabel }}</span>
                        </div>
                        <input type="range" v-model.number="settings.default_difficulty" min="0" max="20" step="1" class="w-full accent-amber-500" />
                        <div class="flex justify-between text-[10px] text-zinc-600 mt-1">
                            <span>{{ $t('settings.easier') }}</span>
                            <span>{{ $t('settings.harder') }}</span>
                        </div>
                    </div>
                    <!-- Toggle settings -->
                    <div v-for="toggle in [
                        { key: 'sound_enabled', label: 'settings.sound', desc: 'settings.sound_desc' },
                        { key: 'board_coordinates', label: 'settings.board_coordinates', desc: 'settings.board_coordinates_desc' },
                        { key: 'auto_queen', label: 'settings.auto_queen', desc: 'settings.auto_queen_desc' },
                        { key: 'move_confirmation', label: 'settings.move_confirmation', desc: 'settings.move_confirmation_desc' },
                        { key: 'show_elo_opponent', label: 'settings.show_elo', desc: 'settings.show_elo_desc' },
                    ]" :key="toggle.key" class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">{{ $t(toggle.label) }}</p>
                            <p class="text-xs text-zinc-600">{{ $t(toggle.desc) }}</p>
                        </div>
                        <button type="button" @click="settings[toggle.key] = !settings[toggle.key]"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings[toggle.key] ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings[toggle.key] ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>
                    <!-- Board theme -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.board_theme') }}</p>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <button v-for="(theme, key) in BOARD_THEMES" :key="key" type="button" @click="settings.board_theme = key"
                                :class="['rounded-xl border p-2 transition-all text-center',
                                    settings.board_theme === key ? 'border-amber-500/30 bg-amber-500/10' : 'border-white/5 hover:border-white/10']">
                                <div class="flex gap-0.5 justify-center mb-1.5">
                                    <div class="w-4 h-4 rounded-sm" :style="{ background: theme.light }"></div>
                                    <div class="w-4 h-4 rounded-sm" :style="{ background: theme.dark }"></div>
                                </div>
                                <p class="text-[9px] font-bold text-zinc-400 truncate">{{ locale === 'lv' ? theme.label_lv : theme.label }}</p>
                            </button>
                        </div>
                    </div>
                    <!-- Piece style -->
                    <div>
                        <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.piece_style') }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button v-for="(style, key) in PIECE_STYLES" :key="key" type="button" @click="settings.piece_style = key"
                                :class="['rounded-xl border p-3 transition-all text-center',
                                    settings.piece_style === key ? 'border-amber-500/30 bg-amber-500/10' : 'border-white/5 hover:border-white/10']">
                                <div class="flex justify-center gap-1 mb-1.5 text-xl">
                                    <span :style="{ color: style.white.fill, textShadow: `0 0 1px ${style.white.stroke}` }">♔</span>
                                    <span :style="{ color: style.black.fill, textShadow: `0 0 1px ${style.black.stroke}` }">♚</span>
                                </div>
                                <p class="text-[9px] font-bold text-zinc-400">{{ locale === 'lv' ? style.label_lv : style.label }}</p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Email Notifications ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">✉</span> {{ $t('settings.email_notifications') }}
                </h4>
                <div class="space-y-3 pl-1">
                    <div v-for="toggle in [
                        { key: 'email_friend_requests', label: 'settings.email_friends', desc: 'settings.email_friends_desc' },
                        { key: 'email_game_invites', label: 'settings.email_invites', desc: 'settings.email_invites_desc' },
                        { key: 'email_weekly_digest', label: 'settings.email_digest', desc: 'settings.email_digest_desc' },
                    ]" :key="toggle.key" class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">{{ $t(toggle.label) }}</p>
                            <p class="text-xs text-zinc-600">{{ $t(toggle.desc) }}</p>
                        </div>
                        <button type="button" @click="settings[toggle.key] = !settings[toggle.key]"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings[toggle.key] ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings[toggle.key] ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="h-px bg-white/5"></div>

            <!-- ═══ Data & Tools ═══ -->
            <div>
                <h4 class="text-sm font-bold text-zinc-300 mb-4 flex items-center gap-2">
                    <span class="text-lg">📦</span> {{ $t('settings.data_tools') }}
                </h4>
                <div class="space-y-3 pl-1">
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
            </div>

            <button type="button" @click="saveSettings" :disabled="isSavingSettings"
                class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500">
                {{ isSavingSettings ? $t('common.saving') : $t('profile.save_settings') }}
            </button>
        </div>
    </section>
</template>
