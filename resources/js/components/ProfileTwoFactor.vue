<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';
import api from '../services/api';

const { t } = useI18n();
const authStore = useAuthStore();
const { notify } = useNotification();

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
        twoFAError.value = err?.message || t('profile.2fa_error_code');
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
    <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mt-6 sm:mt-8"
        aria-labelledby="2fa-heading">
        <div class="flex items-start gap-3 mb-5">
            <span class="text-2xl" aria-hidden="true">🔐</span>
            <div>
                <h3 id="2fa-heading" class="text-xs font-black uppercase tracking-widest text-amber-400">
                    {{ $t('profile.2fa_title') }}
                </h3>
                <p class="text-sm text-zinc-400 mt-1">
                    {{ is2FAEnabled ? $t('profile.2fa_active_desc') : $t('profile.2fa_inactive_desc') }}
                </p>
            </div>
        </div>

        <p v-if="twoFAError" role="alert" class="text-xs text-red-400 mb-4 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-2">
            {{ twoFAError }}
        </p>

        <!-- Status: enabled — offer disable -->
        <div v-if="is2FAEnabled && twoFAStep !== 'enabled'" class="space-y-4">
            <div class="flex items-center gap-2 text-sm">
                <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                <span class="text-emerald-400 font-bold">{{ $t('profile.2fa_status_on') }}</span>
            </div>
            <div>
                <label for="2fa-disable-pw" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">{{ $t('profile.2fa_disable_label') }}</label>
                <input id="2fa-disable-pw" v-model="twoFAPassword" type="password" autocomplete="current-password"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
            </div>
            <button type="button" @click="disable2FA" :disabled="!twoFAPassword || twoFALoading"
                class="px-5 py-2.5 bg-red-500/10 text-red-400 font-bold rounded-xl border border-red-500/20 hover:bg-red-500/20 disabled:opacity-30 text-xs uppercase tracking-wider transition-all">
                {{ twoFALoading ? $t('common.loading') : $t('profile.2fa_disable_btn') }}
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
                class="px-5 py-2.5 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 disabled:opacity-30 text-xs uppercase tracking-wider transition-all">
                {{ twoFALoading ? $t('common.loading') : $t('profile.2fa_enable_btn') }}
            </button>
        </div>

        <!-- Step 2: scan QR + enter code -->
        <div v-else-if="twoFAStep === 'confirm'" class="space-y-5 animate-fade-in-up">
            <div class="bg-black/40 border border-white/10 rounded-xl p-5 flex flex-col items-center gap-4">
                <p class="text-xs text-zinc-400 text-center">{{ $t('profile.2fa_scan_qr') }}</p>
                <!-- v-html is safe: twoFAQrSvg is server-generated SVG from bacon/bacon-qr-code, never user input -->
                <div v-html="twoFAQrSvg" class="bg-white rounded-lg p-2 w-[216px] h-[216px]"></div>
                <p class="text-[10px] text-zinc-600 font-mono break-all text-center max-w-xs">
                    {{ $t('profile.2fa_manual_code') }}: <span class="text-zinc-400 select-all">{{ twoFASecret }}</span>
                </p>
            </div>
            <div>
                <label for="2fa-code" class="block text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">
                    {{ $t('profile.2fa_enter_code') }}
                </label>
                <input id="2fa-code" v-model="twoFACode" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                    autocomplete="one-time-code" :placeholder="t('profile.totp_placeholder')"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-lg text-center text-white font-mono tracking-[0.5em] focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
            </div>
            <div class="flex gap-2">
                <button type="button" @click="confirm2FA" :disabled="twoFACode.length !== 6 || twoFALoading"
                    class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-30 text-xs uppercase tracking-wider transition-all">
                    {{ twoFALoading ? $t('common.loading') : $t('profile.2fa_confirm_btn') }}
                </button>
                <button type="button" @click="twoFAStep = 'idle'; twoFACode = ''"
                    class="px-5 py-3 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-white text-xs uppercase tracking-wider transition-all">
                    {{ $t('common.cancel') }}
                </button>
            </div>
        </div>

        <!-- Step 3: show recovery codes -->
        <div v-else-if="twoFAStep === 'enabled'" class="space-y-4 animate-pop-success">
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4">
                <p class="text-sm font-black text-emerald-400 mb-1">{{ $t('profile.2fa_success') }}</p>
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
                {{ $t('profile.2fa_codes_saved') }}
            </button>
        </div>
    </section>
</template>
