<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';
import { BOARD_THEMES, PIECE_STYLES } from '../composables/useBoardTheme';
import api from '../services/api';

const authStore = useAuthStore();
const { notify } = useNotification();
const { t, locale } = useI18n();
const eloHistory = ref([]);

const eloHistoryError = ref(false);

onMounted(async () => {
    try {
        const { data } = await api.get('/elo/history', { limit: 20 });
        eloHistory.value = data?.history || [];
    } catch (err) {
        // Don't fail silently — log the error so it's discoverable in dev tools,
        // and set a flag so the UI can show a small "couldn't load ELO history"
        // indicator instead of a blank chart.
        console.warn('[profile] failed to load ELO history:', err);
        eloHistoryError.value = true;
    }
});

const profileForm = reactive({
    name: authStore.user?.name || '',
    email: authStore.user?.email || '',
});
const profileErrors = ref({});
const isSavingProfile = ref(false);

const saveProfile = async () => {
    profileErrors.value = {};
    isSavingProfile.value = true;
    try {
        await authStore.updateProfile(profileForm);
        notify(t('profile.updated'), 'success');
    } catch (err) {
        profileErrors.value = err?.errors || {};
        notify(err?.message || t('profile.update_error'), 'error');
    } finally {
        isSavingProfile.value = false;
    }
};

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordErrors = ref({});
const isChangingPassword = ref(false);

const changePassword = async () => {
    passwordErrors.value = {};
    if (passwordForm.password !== passwordForm.password_confirmation) {
        passwordErrors.value = { password: [t('auth.passwords_mismatch')] };
        return;
    }
    isChangingPassword.value = true;
    try {
        await authStore.changePassword(passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        notify(t('profile.password_changed'), 'success');
    } catch (err) {
        passwordErrors.value = err?.errors || {};
        notify(err?.message || t('profile.password_error'), 'error');
    } finally {
        isChangingPassword.value = false;
    }
};

const settings = reactive({
    preferred_color: authStore.user?.preferred_color || 'white',
    dark_mode: authStore.user?.dark_mode ?? true,
    sound_enabled: authStore.user?.sound_enabled ?? true,
    font_size: authStore.user?.font_size || 'medium',
    high_contrast: authStore.user?.high_contrast ?? false,
    board_coordinates: authStore.user?.board_coordinates ?? true,
    move_confirmation: authStore.user?.move_confirmation ?? false,
    auto_queen: authStore.user?.auto_queen ?? true,
    default_difficulty: authStore.user?.default_difficulty ?? 5,
    show_elo_opponent: authStore.user?.show_elo_opponent ?? true,
    board_theme: authStore.user?.board_theme || 'classic',
    piece_style: authStore.user?.piece_style || 'standard',
    email_friend_requests: authStore.user?.email_friend_requests ?? true,
    email_game_invites: authStore.user?.email_game_invites ?? true,
    email_weekly_digest: authStore.user?.email_weekly_digest ?? true,
    animation_speed: authStore.user?.animation_speed || 'normal',
});

const SKILL_LABELS = {
    0: 'Beginner (400)', 3: 'Weak (700)', 5: 'Medium (1000)', 8: 'Strong (1400)',
    10: 'Experienced (1600)', 13: 'Expert (1900)', 16: 'Master (2200)', 20: 'Grandmaster (2800)',
};
const difficultyLabel = computed(() => {
    const keys = Object.keys(SKILL_LABELS).map(Number).sort((a, b) => a - b);
    let label = SKILL_LABELS[0];
    for (const k of keys) { if (settings.default_difficulty >= k) label = SKILL_LABELS[k]; }
    return label;
});

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
const isSavingSettings = ref(false);

const saveSettings = async () => {
    isSavingSettings.value = true;
    try {
        await authStore.updateSettings(settings);
        notify(t('profile.settings_saved'), 'success');
    } catch {
        notify(t('profile.settings_error'), 'error');
    } finally {
        isSavingSettings.value = false;
    }
};

const initial = computed(() => authStore.user?.name?.charAt(0).toUpperCase() || '?');
const registeredDate = computed(() => authStore.user?.created_at?.split(/[T ]/)[0] || '—');

const deleteForm = reactive({
    password: '',
    confirm: '', // typed acknowledgement, e.g. "DZĒST"
});
const deleteErrors = ref({});
const isDeleting = ref(false);
const showDeleteConfirm = ref(false);

const canDelete = computed(
    () => deleteForm.password.length > 0 && deleteForm.confirm.trim().toUpperCase() === t('profile.delete_keyword')
);

const deleteAccount = async () => {
    deleteErrors.value = {};
    if (!canDelete.value) return;
    isDeleting.value = true;
    try {
        await authStore.deleteAccount(deleteForm.password);
        notify(t('profile.account_deleted'), 'success');
    } catch (err) {
        deleteErrors.value = err?.errors || {};
        notify(err?.message || t('profile.account_delete_error'), 'error');
    } finally {
        isDeleting.value = false;
    }
};

const twoFAStep = ref('idle'); // 'idle' | 'setup' | 'confirm' | 'enabled'
const twoFAPassword = ref('');
const twoFACode = ref('');
const twoFAQrSvg = ref('');
const twoFASecret = ref('');
const twoFARecoveryCodes = ref([]);
const twoFAError = ref('');
const twoFALoading = ref(false);

const is2FAEnabled = computed(() => authStore.user?.two_factor_enabled);

async function setup2FA() {
    twoFAError.value = '';
    twoFALoading.value = true;
    try {
        const { data } = await api.post('/2fa/setup', { password: twoFAPassword.value });
        twoFAQrSvg.value = data.qr_svg || data.payload?.qr_svg || '';
        twoFASecret.value = data.secret || data.payload?.secret || '';
        twoFAStep.value = 'confirm';
        twoFAPassword.value = '';
    } catch (err) {
        twoFAError.value = err?.message || t('profile.2fa_error_setup');
    } finally {
        twoFALoading.value = false;
    }
}

async function confirm2FA() {
    twoFAError.value = '';
    twoFALoading.value = true;
    try {
        const { data } = await api.post('/2fa/confirm', { code: twoFACode.value });
        twoFARecoveryCodes.value = data.recovery_codes || data.payload?.recovery_codes || [];
        twoFAStep.value = 'enabled';
        twoFACode.value = '';
        if (authStore.fetchUser) await authStore.fetchUser();
        notify(t('profile.2fa_activated'), 'success');
    } catch (err) {
        twoFAError.value = err?.message || 'Nepareizs kods';
    } finally {
        twoFALoading.value = false;
    }
}

async function disable2FA() {
    twoFAError.value = '';
    twoFALoading.value = true;
    try {
        await api.post('/2fa/disable', { password: twoFAPassword.value });
        twoFAStep.value = 'idle';
        twoFAPassword.value = '';
        twoFARecoveryCodes.value = [];
        if (authStore.fetchUser) await authStore.fetchUser();
        notify(t('profile.2fa_deactivated'), 'success');
    } catch (err) {
        twoFAError.value = err?.message || t('profile.2fa_error_generic');
    } finally {
        twoFALoading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8 sm:mb-10">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                    <span class="text-amber-400">◉</span> {{ $t('nav.profile') }}
                </h1>
                <p class="text-zinc-500 text-sm mt-2">{{ $t('profile.subtitle') }}</p>
            </div>

            <!-- Identity card -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 mb-8">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center text-2xl sm:text-3xl font-black text-black shadow-xl shadow-amber-500/20 shrink-0">
                        {{ initial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl sm:text-2xl font-black text-white truncate">{{ authStore.user?.name }}</h2>
                        <p class="text-zinc-500 text-sm truncate">{{ authStore.user?.email }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="text-[10px] sm:text-xs font-bold text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
                                ELO {{ authStore.user?.elo_rating }}
                            </span>
                            <span class="text-[10px] sm:text-xs font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ authStore.user?.role }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <div class="bg-black/30 rounded-2xl p-3 sm:p-4 text-center">
                        <p class="text-xl sm:text-2xl font-black text-white">♔</p>
                        <p class="text-[10px] sm:text-xs text-zinc-500 mt-1 font-bold uppercase tracking-wider">{{ $t('profile.member_since') }}</p>
                        <p class="text-xs sm:text-sm text-zinc-300 font-bold mt-1">{{ registeredDate }}</p>
                    </div>
                    <div class="bg-black/30 rounded-2xl p-3 sm:p-4 text-center">
                        <p class="text-xl sm:text-2xl font-black text-white">{{ authStore.user?.role === 'admin' ? '⚙' : '♟' }}</p>
                        <p class="text-[10px] sm:text-xs text-zinc-500 mt-1 font-bold uppercase tracking-wider">{{ $t('profile.role_label') }}</p>
                        <p class="text-xs sm:text-sm text-zinc-300 font-bold mt-1 capitalize">{{ authStore.user?.role }}</p>
                    </div>
                </div>
            </section>

            <!-- ELO History — show data, or a small error indicator if the fetch failed -->
            <section v-if="eloHistory.length" class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('elo.history') }}</h3>
                <div class="space-y-2">
                    <div v-for="entry in eloHistory" :key="entry.id"
                        class="flex items-center justify-between py-2 px-3 rounded-xl hover:bg-white/[0.02] transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">{{ entry.source === 'game' ? '♚' : '⚡' }}</span>
                            <div>
                                <p class="text-sm font-bold text-zinc-300">{{ entry.source === 'game' ? $t('elo.source_game') : $t('elo.source_training') }}</p>
                                <p class="text-[10px] text-zinc-600">{{ entry.created_at?.replace('T', ' ').slice(0, 16) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black px-2 py-0.5 rounded-full"
                                :class="entry.change > 0 ? 'bg-emerald-500/10 text-emerald-400' : entry.change < 0 ? 'bg-red-500/10 text-red-400' : 'bg-zinc-800 text-zinc-400'">
                                {{ entry.change > 0 ? '+' : '' }}{{ entry.change }}
                            </span>
                            <p class="text-[10px] text-zinc-600 mt-0.5">{{ entry.old_elo }} → {{ entry.new_elo }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ELO history error state — shown only if the fetch failed AND no data is present -->
            <div v-else-if="eloHistoryError" class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-4 mb-6 sm:mb-8 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-amber-300/80">{{ $t('errors.load_failed') }} — ELO history</p>
            </div>

            <!-- Account details form -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.account') }}</h3>

                <form @submit.prevent="saveProfile" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.username_label') }}</label>
                        <input :aria-label="$t('profile.username_label')" v-model="profileForm.name" type="text" required
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': profileErrors.name }"
                            placeholder="lietotājvārds" />
                        <p v-if="profileErrors.name" class="text-xs text-red-400 mt-1">{{ profileErrors.name[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">E-pasts</label>
                        <input v-model="profileForm.email" type="email" required :aria-label="$t('profile.email_label')"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': profileErrors.email }"
                            placeholder="tu@example.com" />
                        <p v-if="profileErrors.email" class="text-xs text-red-400 mt-1">{{ profileErrors.email[0] }}</p>
                    </div>

                    <button type="submit" :disabled="isSavingProfile"
                        class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500">
                        {{ isSavingProfile ? $t('common.saving') : $t('common.save_profile') }}
                    </button>
                </form>
            </section>

            <!-- Password change form -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.change_password') }}</h3>

                <form @submit.prevent="changePassword" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.current_password') }}</label>
                        <input :aria-label="$t('profile.current_password')" v-model="passwordForm.current_password" type="password" required autocomplete="current-password"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': passwordErrors.current_password }" />
                        <p v-if="passwordErrors.current_password" class="text-xs text-red-400 mt-1">
                            {{ passwordErrors.current_password[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.new_password') }}</label>
                        <input :aria-label="$t('profile.new_password')" v-model="passwordForm.password" type="password" required autocomplete="new-password" minlength="8"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': passwordErrors.password }" />
                        <p v-if="passwordErrors.password" class="text-xs text-red-400 mt-1">{{ passwordErrors.password[0] }}</p>
                        <p v-else class="text-[10px] text-zinc-600 mt-1">{{ $t('profile.min_chars') }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.confirm_new_password') }}</label>
                        <input :aria-label="$t('profile.confirm_new_password')" v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all" />
                    </div>

                    <button type="submit" :disabled="isChangingPassword"
                        class="w-full py-3 bg-zinc-800 text-white font-black rounded-xl disabled:opacity-40 transition-all border border-white/10 hover:border-amber-500/30 hover:text-amber-400 uppercase tracking-wider text-sm">
                        {{ isChangingPassword ? 'Maina...' : 'Nomainīt paroli' }}
                    </button>
                </form>
            </section>

            <!-- Preferences -->
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
                                    <button type="button" v-for="c in ['white', 'black']" :key="c"
                                        @click="settings.preferred_color = c"
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
                                <input type="range" v-model.number="settings.default_difficulty" min="0" max="20" step="1"
                                    class="w-full accent-amber-500" />
                                <div class="flex justify-between text-[10px] text-zinc-600 mt-1">
                                    <span>{{ $t('settings.easier') }}</span>
                                    <span>{{ $t('settings.harder') }}</span>
                                </div>
                            </div>
                            <!-- Sound effects -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.sound') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.sound_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.sound_enabled = !settings.sound_enabled"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.sound_enabled ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.sound_enabled ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <!-- Board coordinates -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.board_coordinates') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.board_coordinates_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.board_coordinates = !settings.board_coordinates"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.board_coordinates ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.board_coordinates ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <!-- Auto-promote to queen -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.auto_queen') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.auto_queen_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.auto_queen = !settings.auto_queen"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.auto_queen ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.auto_queen ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <!-- Move confirmation -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.move_confirmation') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.move_confirmation_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.move_confirmation = !settings.move_confirmation"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.move_confirmation ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.move_confirmation ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <!-- Show opponent ELO -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.show_elo') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.show_elo_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.show_elo_opponent = !settings.show_elo_opponent"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.show_elo_opponent ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.show_elo_opponent ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>

                            <!-- Board theme -->
                            <div>
                                <p class="text-sm font-bold text-zinc-300 mb-2">{{ $t('settings.board_theme') }}</p>
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                    <button v-for="(theme, key) in BOARD_THEMES" :key="key" type="button"
                                        @click="settings.board_theme = key"
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
                                    <button v-for="(style, key) in PIECE_STYLES" :key="key" type="button"
                                        @click="settings.piece_style = key"
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
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.email_friends') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.email_friends_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.email_friend_requests = !settings.email_friend_requests"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.email_friend_requests ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.email_friend_requests ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.email_invites') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.email_invites_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.email_game_invites = !settings.email_game_invites"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.email_game_invites ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.email_game_invites ? 'left-[22px]' : 'left-0.5']"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-300">{{ $t('settings.email_digest') }}</p>
                                    <p class="text-xs text-zinc-600">{{ $t('settings.email_digest_desc') }}</p>
                                </div>
                                <button type="button" @click="settings.email_weekly_digest = !settings.email_weekly_digest"
                                    :class="['w-12 h-7 rounded-full transition-all relative shrink-0', settings.email_weekly_digest ? 'bg-amber-500' : 'bg-zinc-700']">
                                    <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all', settings.email_weekly_digest ? 'left-[22px]' : 'left-0.5']"></span>
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

            <!-- Two-Factor Authentication -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mt-6 sm:mt-8"
                aria-labelledby="2fa-heading">
                <div class="flex items-start gap-3 mb-5">
                    <span class="text-2xl" aria-hidden="true">🔐</span>
                    <div>
                        <h3 id="2fa-heading" class="text-xs font-black uppercase tracking-widest text-amber-400">
                            {{ $t('profile.2fa_title') }}
                        </h3>
                        <p class="text-sm text-zinc-400 mt-1">
                            {{ is2FAEnabled ? 'Aktīva — tavs konts ir aizsargāts ar papildu verifikācijas kodu.' : 'Papildu aizsardzības slānis tavām pieslēgšanās sesijām.' }}
                        </p>
                    </div>
                </div>

                <p v-if="twoFAError" role="alert" class="text-xs text-red-400 mb-4 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-2">
                    {{ twoFAError }}
                </p>

                <!-- Status: enabled -->
                <div v-if="is2FAEnabled && twoFAStep !== 'enabled'" class="space-y-4">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                        <span class="text-emerald-400 font-bold">2FA ir aktīva</span>
                    </div>
                    <div>
                        <label for="2fa-disable-pw" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">{{ $t('profile.2fa_disable_label') }}</label>
                        <input id="2fa-disable-pw" v-model="twoFAPassword" type="password" autocomplete="current-password"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                    </div>
                    <button type="button" @click="disable2FA" :disabled="!twoFAPassword || twoFALoading"
                        class="px-5 py-2.5 bg-red-500/10 text-red-400 font-bold rounded-xl border border-red-500/20 hover:bg-red-500/20 disabled:opacity-30 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/50">
                        {{ twoFALoading ? 'Izslēdz…' : 'Izslēgt 2FA' }}
                    </button>
                </div>

                <!-- Step 1: enter password to start setup -->
                <div v-else-if="twoFAStep === 'idle' && !is2FAEnabled" class="space-y-4">
                    <div>
                        <label for="2fa-setup-pw" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">{{ $t('profile.2fa_setup_label') }}</label>
                        <input id="2fa-setup-pw" v-model="twoFAPassword" type="password" autocomplete="current-password"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                    </div>
                    <button type="button" @click="setup2FA" :disabled="!twoFAPassword || twoFALoading"
                        class="px-5 py-2.5 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 disabled:opacity-30 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                        {{ twoFALoading ? 'Ģenerē…' : 'Ieslēgt 2FA' }}
                    </button>
                </div>

                <!-- Step 2: scan QR + enter code -->
                <div v-else-if="twoFAStep === 'confirm'" class="space-y-5 animate-fade-in-up">
                    <div class="bg-black/40 border border-white/10 rounded-xl p-5 flex flex-col items-center gap-4">
                        <p class="text-xs text-zinc-400 text-center">{{ $t('profile.2fa_scan_qr') }}</p>
                        <div v-html="twoFAQrSvg" class="bg-white rounded-lg p-2 w-[216px] h-[216px]"></div>
                        <p class="text-[10px] text-zinc-600 font-mono break-all text-center max-w-xs">
                            Manuālais kods: <span class="text-zinc-400 select-all">{{ twoFASecret }}</span>
                        </p>
                    </div>
                    <div>
                        <label for="2fa-code" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">
                            Ievadi 6 ciparu verifikācijas kodu
                        </label>
                        <input id="2fa-code" v-model="twoFACode" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            autocomplete="one-time-code" placeholder="000000"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-lg text-center text-white font-mono tracking-[0.5em] focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="confirm2FA" :disabled="twoFACode.length !== 6 || twoFALoading"
                            class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-30 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            {{ twoFALoading ? 'Pārbauda…' : 'Apstiprināt un aktivizēt' }}
                        </button>
                        <button type="button" @click="twoFAStep = 'idle'; twoFACode = ''"
                            class="px-5 py-3 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-white text-xs uppercase tracking-wider transition-all">
                            Atcelt
                        </button>
                    </div>
                </div>

                <!-- Step 3: show recovery codes -->
                <div v-else-if="twoFAStep === 'enabled'" class="space-y-4 animate-pop-success">
                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4">
                        <p class="text-sm font-black text-emerald-400 mb-1">✓ 2FA aktivizēta!</p>
                        <p class="text-xs text-zinc-400">{{ $t('profile.2fa_save_codes') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <code v-for="code in twoFARecoveryCodes" :key="code"
                            class="bg-zinc-800 text-zinc-300 text-sm font-mono text-center px-3 py-2 rounded-lg select-all border border-white/5">
                            {{ code }}
                        </code>
                    </div>
                    <button type="button" @click="twoFAStep = 'idle'"
                        class="w-full py-2.5 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-white text-xs uppercase tracking-wider transition-all">
                        Sapratu, es tos saglabāju
                    </button>
                </div>
            </section>

            <!-- Danger zone — GDPR right to erasure -->
            <section class="bg-red-950/20 border border-red-500/20 rounded-3xl p-6 sm:p-8 mt-6 sm:mt-8"
                aria-labelledby="danger-zone-heading">
                <div class="flex items-start gap-3 mb-6">
                    <span class="text-2xl" aria-hidden="true">⚠</span>
                    <div>
                        <h3 id="danger-zone-heading" class="text-xs font-black uppercase tracking-widest text-red-400">{{ $t('profile.danger_zone') }}</h3>
                        <p class="text-sm text-zinc-400 mt-1">
                            Konta dzēšana ir neatgriezeniska. Visas tavas partijas, analīzes, treniņu sesijas un personīgie dati tiks neatgriezeniski izdzēsti saskaņā ar VDAR (GDPR) "tiesībām tikt aizmirstam".
                        </p>
                    </div>
                </div>

                <button v-if="!showDeleteConfirm" type="button" @click="showDeleteConfirm = true"
                    class="w-full sm:w-auto px-6 py-3 bg-transparent text-red-400 font-bold rounded-xl border border-red-500/30 hover:bg-red-500/10 hover:border-red-500/50 transition-all uppercase tracking-wider text-xs">
                    {{ $t('profile.delete_account') }}
                </button>

                <form v-else @submit.prevent="deleteAccount" class="space-y-4">
                    <div>
                        <label for="delete-password" class="block text-xs font-bold text-red-400 mb-2 uppercase tracking-wider">{{ $t('profile.2fa_setup_label') }}</label>
                        <input id="delete-password" v-model="deleteForm.password" type="password" required autocomplete="current-password"
                            class="w-full bg-black/40 border border-red-500/30 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/60 focus-visible:ring-2 focus-visible:ring-red-500/50 transition-all"
                            :class="{ '!border-red-500': deleteErrors.password }"
                            :aria-invalid="!!deleteErrors.password"
                            :aria-describedby="deleteErrors.password ? 'delete-password-error' : undefined" />
                        <p v-if="deleteErrors.password" id="delete-password-error" role="alert" class="text-xs text-red-400 mt-1">
                            {{ deleteErrors.password[0] }}
                        </p>
                    </div>

                    <div>
                        <label for="delete-confirm" class="block text-xs font-bold text-red-400 mb-2 uppercase tracking-wider">
                            Lai turpinātu, ievadi <span class="font-mono bg-red-500/10 px-2 py-0.5 rounded">DZĒST</span>
                        </label>
                        <input id="delete-confirm" v-model="deleteForm.confirm" type="text" required autocomplete="off"
                            class="w-full bg-black/40 border border-red-500/30 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-red-500/60 focus-visible:ring-2 focus-visible:ring-red-500/50 transition-all"
                            placeholder="DZĒST" />
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="submit" :disabled="!canDelete || isDeleting"
                            class="flex-1 py-3 bg-red-500/20 text-red-400 font-black rounded-xl border border-red-500/40 hover:bg-red-500/30 disabled:opacity-30 disabled:cursor-not-allowed transition-all uppercase tracking-wider text-xs">
                            {{ isDeleting ? 'Dzēš...' : 'Neatgriezeniski dzēst kontu' }}
                        </button>
                        <button type="button" @click="showDeleteConfirm = false; deleteForm.password = ''; deleteForm.confirm = ''"
                            class="px-6 py-3 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-white transition-all uppercase tracking-wider text-xs">
                            Atcelt
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
