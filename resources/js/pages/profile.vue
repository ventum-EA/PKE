<script setup>
import { ref, reactive, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';
import api from '../services/api';

const authStore = useAuthStore();
const { notify } = useNotification();

// --- Profile (name + email) ---------------------------------------------
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
        notify('Profils atjaunināts!', 'success');
    } catch (err) {
        profileErrors.value = err?.errors || {};
        notify(err?.message || 'Kļūda saglabājot profilu', 'error');
    } finally {
        isSavingProfile.value = false;
    }
};

// --- Password change ----------------------------------------------------
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
        passwordErrors.value = { password: ['Paroles nesakrīt'] };
        return;
    }
    isChangingPassword.value = true;
    try {
        await authStore.changePassword(passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        notify('Parole nomainīta!', 'success');
    } catch (err) {
        passwordErrors.value = err?.errors || {};
        notify(err?.message || 'Kļūda mainot paroli', 'error');
    } finally {
        isChangingPassword.value = false;
    }
};

// --- Settings (preferred color, sound, dark mode) ------------------------
const settings = reactive({
    preferred_color: authStore.user?.preferred_color || 'white',
    dark_mode: authStore.user?.dark_mode ?? true,
    sound_enabled: authStore.user?.sound_enabled ?? true,
});
const isSavingSettings = ref(false);

const saveSettings = async () => {
    isSavingSettings.value = true;
    try {
        await authStore.updateSettings(settings);
        notify('Iestatījumi saglabāti!', 'success');
    } catch {
        notify('Kļūda saglabājot iestatījumus', 'error');
    } finally {
        isSavingSettings.value = false;
    }
};

const initial = computed(() => authStore.user?.name?.charAt(0).toUpperCase() || '?');
const registeredDate = computed(() => authStore.user?.created_at?.split(/[T ]/)[0] || '—');

// --- Account deletion (GDPR right to erasure) ---------------------------
const deleteForm = reactive({
    password: '',
    confirm: '', // typed acknowledgement, e.g. "DZĒST"
});
const deleteErrors = ref({});
const isDeleting = ref(false);
const showDeleteConfirm = ref(false);

const canDelete = computed(
    () => deleteForm.password.length > 0 && deleteForm.confirm.trim().toUpperCase() === 'DZĒST'
);

const deleteAccount = async () => {
    deleteErrors.value = {};
    if (!canDelete.value) return;
    isDeleting.value = true;
    try {
        await authStore.deleteAccount(deleteForm.password);
        // Redirect happens inside the store action; this notify is unlikely to render
        notify('Konts dzēsts', 'success');
    } catch (err) {
        deleteErrors.value = err?.errors || {};
        notify(err?.message || 'Kļūda dzēšot kontu', 'error');
    } finally {
        isDeleting.value = false;
    }
};

// --- Two-Factor Authentication (2FA) ------------------------------------
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
        twoFAQrSvg.value = data.payload?.qr_svg || '';
        twoFASecret.value = data.payload?.secret || '';
        twoFAStep.value = 'confirm';
        twoFAPassword.value = '';
    } catch (err) {
        twoFAError.value = err?.message || 'Kļūda iestatot 2FA';
    } finally {
        twoFALoading.value = false;
    }
}

async function confirm2FA() {
    twoFAError.value = '';
    twoFALoading.value = true;
    try {
        const { data } = await api.post('/2fa/confirm', { code: twoFACode.value });
        twoFARecoveryCodes.value = data.payload?.recovery_codes || [];
        twoFAStep.value = 'enabled';
        twoFACode.value = '';
        // Refresh user data so two_factor_enabled updates
        await authStore.fetchUser?.() || location.reload();
        notify('2FA veiksmīgi aktivizēta!', 'success');
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
        await authStore.fetchUser?.() || location.reload();
        notify('2FA deaktivizēta', 'success');
    } catch (err) {
        twoFAError.value = err?.message || 'Kļūda';
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
                <p class="text-zinc-500 text-sm mt-2">Pārvaldi savu kontu un iestatījumus</p>
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
                        <p class="text-[10px] sm:text-xs text-zinc-500 mt-1 font-bold uppercase tracking-wider">Loma</p>
                        <p class="text-xs sm:text-sm text-zinc-300 font-bold mt-1 capitalize">{{ authStore.user?.role }}</p>
                    </div>
                </div>
            </section>

            <!-- Account details form -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.account') }}</h3>

                <form @submit.prevent="saveProfile" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">Lietotājvārds</label>
                        <input v-model="profileForm.name" type="text" required
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': profileErrors.name }"
                            placeholder="lietotājvārds" />
                        <p v-if="profileErrors.name" class="text-xs text-red-400 mt-1">{{ profileErrors.name[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">E-pasts</label>
                        <input v-model="profileForm.email" type="email" required
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': profileErrors.email }"
                            placeholder="tu@example.com" />
                        <p v-if="profileErrors.email" class="text-xs text-red-400 mt-1">{{ profileErrors.email[0] }}</p>
                    </div>

                    <button type="submit" :disabled="isSavingProfile"
                        class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500">
                        {{ isSavingProfile ? 'Saglabā...' : 'Saglabāt profilu' }}
                    </button>
                </form>
            </section>

            <!-- Password change form -->
            <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">Nomainīt paroli</h3>

                <form @submit.prevent="changePassword" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">Pašreizējā parole</label>
                        <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': passwordErrors.current_password }" />
                        <p v-if="passwordErrors.current_password" class="text-xs text-red-400 mt-1">
                            {{ passwordErrors.current_password[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">Jaunā parole</label>
                        <input v-model="passwordForm.password" type="password" required autocomplete="new-password" minlength="8"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus:bg-black/60 transition-all"
                            :class="{ '!border-red-500/50': passwordErrors.password }" />
                        <p v-if="passwordErrors.password" class="text-xs text-red-400 mt-1">{{ passwordErrors.password[0] }}</p>
                        <p v-else class="text-[10px] text-zinc-600 mt-1">Vismaz 8 simboli</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">Apstiprināt jauno paroli</label>
                        <input v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password"
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

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-3 uppercase tracking-wider">Vēlamā krāsa</label>
                        <div class="flex gap-3">
                            <button type="button" v-for="c in ['white', 'black']" :key="c"
                                @click="settings.preferred_color = c"
                                :class="['flex-1 sm:flex-none px-5 py-2.5 rounded-xl font-bold text-sm border transition-all',
                                    settings.preferred_color === c
                                        ? 'bg-amber-500/10 border-amber-500/30 text-amber-400'
                                        : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ c === 'white' ? '♔ Baltais' : '♚ Melnais' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">Skaņas efekti</p>
                            <p class="text-xs text-zinc-600">Gājienu un paziņojumu skaņas</p>
                        </div>
                        <button type="button" @click="settings.sound_enabled = !settings.sound_enabled"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0',
                                settings.sound_enabled ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all',
                                settings.sound_enabled ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-300">Tumšais režīms</p>
                            <p class="text-xs text-zinc-600">Saudzē acis vakaros</p>
                        </div>
                        <button type="button" @click="settings.dark_mode = !settings.dark_mode"
                            :class="['w-12 h-7 rounded-full transition-all relative shrink-0',
                                settings.dark_mode ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all',
                                settings.dark_mode ? 'left-[22px]' : 'left-0.5']"></span>
                        </button>
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
                        <label for="2fa-disable-pw" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">Ievadi paroli, lai izslēgtu</label>
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
                        <label for="2fa-setup-pw" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">Apstiprini ar paroli</label>
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
                        <p class="text-xs text-zinc-400 text-center">Skenē QR kodu ar Google Authenticator vai līdzīgu lietotni:</p>
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
                        <p class="text-xs text-zinc-400">Saglabā šos rezerves kodus drošā vietā. Katrs kods darbojas tikai vienu reizi.</p>
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
                    Dzēst manu kontu
                </button>

                <form v-else @submit.prevent="deleteAccount" class="space-y-4">
                    <div>
                        <label for="delete-password" class="block text-xs font-bold text-red-400 mb-2 uppercase tracking-wider">Apstiprini ar paroli</label>
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
