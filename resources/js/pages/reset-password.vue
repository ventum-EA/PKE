<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const email = ref('');
const token = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const success = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});

onMounted(() => {
    email.value = typeof route.query.email === 'string' ? route.query.email : '';
    token.value = typeof route.query.token === 'string' ? route.query.token : '';
});

const passwordsMatch = computed(
    () => passwordConfirmation.value.length === 0 || password.value === passwordConfirmation.value
);

const isFormValid = computed(() =>
    email.value.length > 0 &&
    token.value.length > 0 &&
    password.value.length >= 8 &&
    password.value === passwordConfirmation.value
);

const submit = async () => {
    if (!isFormValid.value || isLoading.value) return;
    isLoading.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};
    try {
        await api.csrf();
        await api.post('/reset-password', {
            email: email.value,
            token: token.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        success.value = true;
        // Redirect to login after a short pause so user sees the success state
        setTimeout(() => router.push('/login'), 2500);
    } catch (err) {
        fieldErrors.value = err?.errors || {};
        errorMessage.value = err?.message || t('auth.reset_failed');
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black tracking-tight text-white">{{ $t('auth.reset_password_title') }}</h1>
                <p class="text-zinc-500 mt-2 text-sm">{{ $t('auth.reset_password_subtitle') }}</p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-3xl p-8">
                <div v-if="success" class="text-center py-4">
                    <div class="w-16 h-16 mx-auto bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-2">{{ $t('auth.password_reset_success') }}</p>
                    <p class="text-zinc-500 text-sm">{{ $t('auth.redirecting_to_login') }}</p>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-5" novalidate>
                    <div v-if="errorMessage" role="alert" aria-live="assertive"
                        class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4">
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <div>
                        <label for="rp-email" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            {{ $t('auth.email') }}
                        </label>
                        <input
                            id="rp-email"
                            v-model="email"
                            type="email"
                            required
                            autocomplete="email"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                        />
                        <p v-if="fieldErrors.email" class="text-xs text-red-400 mt-1">{{ fieldErrors.email[0] }}</p>
                    </div>

                    <div>
                        <label for="rp-password" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            {{ $t('auth.new_password') }}
                        </label>
                        <input
                            id="rp-password"
                            v-model="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            minlength="8"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                        />
                        <p class="text-xs text-zinc-600 mt-1">{{ $t('auth.password_min_chars') }}</p>
                        <p v-if="fieldErrors.password" class="text-xs text-red-400 mt-1">{{ fieldErrors.password[0] }}</p>
                    </div>

                    <div>
                        <label for="rp-password-confirm" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            {{ $t('auth.confirm_password') }}
                        </label>
                        <input
                            id="rp-password-confirm"
                            v-model="passwordConfirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="w-full bg-black/50 border rounded-xl px-4 py-3 text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                            :class="passwordsMatch ? 'border-white/10 focus:border-amber-500/50' : 'border-red-500/40'"
                        />
                        <p v-if="!passwordsMatch" class="text-xs text-red-400 mt-1">{{ $t('auth.passwords_dont_match') }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="isLoading || !isFormValid"
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl hover:from-amber-400 hover:to-amber-500 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
                    >
                        <span v-if="isLoading">{{ $t('auth.resetting') }}…</span>
                        <span v-else>{{ $t('auth.reset_password') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
