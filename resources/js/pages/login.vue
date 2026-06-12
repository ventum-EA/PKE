<script setup>
import { ref, computed, nextTick } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';

const { t } = useI18n();

const email = ref('');
const password = ref('');
const otp = ref('');
const requires2fa = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const errorRef = ref(null);
const otpInputRef = ref(null);

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

// Only block submission for empty fields — never apply registration rules
// (e.g. ≥ 8 chars) to login, because older accounts may use shorter passwords.
// Server is the source of truth for credentials.
const isFormValid = computed(() => {
    if (requires2fa.value) {
        return otp.value.length === 6 && /^\d{6}$/.test(otp.value);
    }
    return email.value.trim().length > 0 && password.value.length > 0;
});

const focusError = async () => {
    await nextTick();
    errorRef.value?.focus?.();
};

// Never redirect back into /logout or another auth page after login —
// that would immediately end the fresh session (or loop).
const safeRedirect = () => {
    const r = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    const unsafe = ['/logout', '/login', '/register', '/forgot-password', '/reset-password'];
    if (!r.startsWith('/') || r.startsWith('//') || unsafe.some((p) => r.startsWith(p))) return '/';
    return r;
};

const handleLogin = async () => {
    if (isLoading.value) return;
    if (!isFormValid.value) {
        // Inline, localized feedback instead of relying on native browser
        // tooltips (which follow the browser locale, not the app language).
        errorMessage.value = t('auth.fill_all_fields');
        focusError();
        return;
    }
    isLoading.value = true;
    errorMessage.value = '';
    try {
        // If we're at the 2FA step, just verify the OTP
        if (requires2fa.value) {
            await authStore.verify2FA(otp.value);
            router.push(safeRedirect());
            return;
        }

        // Otherwise, submit credentials
        const result = await authStore.login({
            email: email.value.trim(),
            password: password.value,
        });

        if (result?.requires_2fa) {
            requires2fa.value = true;
            await nextTick();
            otpInputRef.value?.focus?.();
            return;
        }

        router.push(safeRedirect());
    } catch (error) {
        errorMessage.value = error?.message || t('auth.login_failed');
        if (requires2fa.value) {
            otp.value = '';
            await nextTick();
            otpInputRef.value?.focus?.();
        } else {
            focusError();
        }
    } finally {
        isLoading.value = false;
    }
};

const backToCredentials = () => {
    requires2fa.value = false;
    otp.value = '';
    errorMessage.value = '';
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-sm">
            <div class="text-center mb-6">
                <div class="w-14 h-14 mx-auto bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center shadow-2xl shadow-amber-500/20 mb-4" aria-hidden="true">
                    <svg class="w-8 h-8 text-black" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L9 7H6l-3 5h4l-2 4h3l-3 6h6l-1-3h4l-1 3h6l-3-6h3l-2-4h4L18 7h-3L12 2zm0 3.5L13.5 8h-3L12 5.5z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-white">{{ $t('auth.login') }}</h1>
                <p class="text-zinc-500 mt-1 text-sm">
                    {{ requires2fa ? $t('auth.two_factor_subtitle') : $t('auth.subtitle') }}
                </p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-2xl p-6">
                <form @submit.prevent="handleLogin" class="space-y-5" novalidate>

                    <div
                        v-if="errorMessage"
                        ref="errorRef"
                        tabindex="-1"
                        role="alert"
                        aria-live="assertive"
                        class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-red-500/30"
                    >
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <template v-if="!requires2fa">
                        <div>
                            <label for="login-email" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                                {{ $t('auth.email') }}
                            </label>
                            <input
                                id="login-email"
                                v-model="email"
                                type="email"
                                autofocus
                                required
                                autocomplete="email"
                                :placeholder="$t('auth.email_placeholder')"
                                class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="login-password" class="block text-xs font-bold uppercase tracking-wider text-zinc-500">
                                    {{ $t('auth.password') }}
                                </label>
                                <router-link
                                    to="/forgot-password"
                                    class="text-xs text-zinc-500 hover:text-amber-400 transition-colors"
                                >
                                    {{ $t('auth.forgot_password') }}
                                </router-link>
                            </div>
                            <input
                                id="login-password"
                                v-model="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                            />
                        </div>
                    </template>

                    <template v-else>
                        <div>
                            <label for="login-otp" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                                {{ $t('auth.two_factor_code') }}
                            </label>
                            <input
                                id="login-otp"
                                ref="otpInputRef"
                                v-model="otp"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                required
                                maxlength="6"
                                pattern="\d{6}"
                                :placeholder="'123456'"
                                class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white text-center text-2xl tracking-[0.5em] placeholder-zinc-700 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                            />
                            <p class="text-xs text-zinc-500 mt-2">{{ $t('auth.two_factor_hint') }}</p>
                        </div>

                        <button
                            type="button"
                            @click="backToCredentials"
                            class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors"
                        >
                            ← {{ $t('auth.back') }}
                        </button>
                    </template>

                    <button
                        type="submit"
                        :disabled="isLoading || !isFormValid"
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl hover:from-amber-400 hover:to-amber-500 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
                    >
                        <span v-if="isLoading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ $t('auth.authenticating') }}
                        </span>
                        <span v-else>
                            {{ requires2fa ? $t('auth.verify') : $t('auth.login') }}
                        </span>
                    </button>
                </form>

                <div v-if="!requires2fa" class="flex items-center justify-between mt-6">
                    <p class="text-zinc-600 text-sm">
                        {{ $t('auth.no_account') }}
                        <router-link to="/register" class="text-amber-400 font-bold hover:text-amber-300 transition-colors">
                            {{ $t('auth.register') }}
                        </router-link>
                    </p>
                    <LanguageSwitcher />
                </div>
            </div>
        </div>
    </div>
</template>
