<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t } = useI18n();

const email = ref('');
const isLoading = ref(false);
const success = ref(false);
const errorMessage = ref('');

const isFormValid = computed(() => email.value.trim().length > 0);

const submit = async () => {
    if (!isFormValid.value || isLoading.value) return;
    isLoading.value = true;
    errorMessage.value = '';
    try {
        await api.csrf();
        await api.post('/forgot-password', { email: email.value.trim() });
        success.value = true;
    } catch (err) {
        // We deliberately show a generic success message regardless of whether
        // the email exists, to avoid leaking which addresses are registered.
        // Only show a real error for network/server failures.
        if (err?.status >= 500 || !err?.response) {
            errorMessage.value = t('auth.network_error');
        } else {
            success.value = true;
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black tracking-tight text-white">{{ $t('auth.forgot_password_title') }}</h1>
                <p class="text-zinc-500 mt-2 text-sm">{{ $t('auth.forgot_password_subtitle') }}</p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-3xl p-8">
                <!-- Success state -->
                <div v-if="success" class="text-center py-4">
                    <div class="w-16 h-16 mx-auto bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-white font-bold mb-2">{{ $t('auth.reset_email_sent') }}</p>
                    <p class="text-zinc-500 text-sm mb-6">{{ $t('auth.reset_email_sent_detail') }}</p>
                    <router-link to="/login" class="text-amber-400 hover:text-amber-300 font-bold text-sm">
                        ← {{ $t('auth.back_to_login') }}
                    </router-link>
                </div>

                <!-- Form -->
                <form v-else @submit.prevent="submit" class="space-y-6" novalidate>
                    <div v-if="errorMessage" role="alert" aria-live="assertive"
                        class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4">
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <div>
                        <label for="fp-email" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            {{ $t('auth.email') }}
                        </label>
                        <input
                            id="fp-email"
                            v-model="email"
                            type="email"
                            autofocus
                            required
                            autocomplete="email"
                            :placeholder="$t('auth.email_placeholder')"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all"
                        />
                    </div>

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
                            {{ $t('auth.sending') }}
                        </span>
                        <span v-else>{{ $t('auth.send_reset_link') }}</span>
                    </button>

                    <p class="text-center text-zinc-600 text-sm pt-2">
                        <router-link to="/login" class="text-amber-400 hover:text-amber-300 font-bold">
                            ← {{ $t('auth.back_to_login') }}
                        </router-link>
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
