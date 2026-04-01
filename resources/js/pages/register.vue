<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});
const authStore = useAuthStore();
const router = useRouter();

const isFormValid = computed(() =>
    name.value.length >= 2 &&
    email.value.includes('@') &&
    password.value.length >= 8 &&
    password.value === passwordConfirmation.value
);

const passwordMismatch = computed(
    () => passwordConfirmation.value.length > 0 && password.value !== passwordConfirmation.value
);

const handleRegister = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};
    try {
        await authStore.register({
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        router.push('/');
    } catch (error) {
        fieldErrors.value = error?.errors || {};
        errorMessage.value = error?.message || t('auth.register_failed');
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black tracking-tight text-white">{{ $t('auth.register') }}</h1>
                <p class="text-zinc-500 mt-2 text-sm">{{ $t('auth.create_account') }}</p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-3xl p-8">
                <form @submit.prevent="handleRegister" class="space-y-5" novalidate>
                    <div v-if="errorMessage" role="alert" aria-live="assertive"
                        class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4">
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <div>
                        <label for="register-name" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">{{ $t('auth.username') }}</label>
                        <input id="register-name" v-model="name" type="text" required autocomplete="username"
                            :aria-invalid="!!fieldErrors.name"
                            :aria-describedby="fieldErrors.name ? 'register-name-error' : undefined"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                        <p v-if="fieldErrors.name" id="register-name-error" role="alert" class="text-xs text-red-400 mt-1">
                            {{ fieldErrors.name[0] }}
                        </p>
                    </div>

                    <div>
                        <label for="register-email" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">{{ $t('auth.email') }}</label>
                        <input id="register-email" v-model="email" type="email" required autocomplete="email"
                            :aria-invalid="!!fieldErrors.email"
                            :aria-describedby="fieldErrors.email ? 'register-email-error' : undefined"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                        <p v-if="fieldErrors.email" id="register-email-error" role="alert" class="text-xs text-red-400 mt-1">
                            {{ fieldErrors.email[0] }}
                        </p>
                    </div>

                    <div>
                        <label for="register-password" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">{{ $t('auth.password') }}</label>
                        <input id="register-password" v-model="password" type="password" required minlength="8" autocomplete="new-password"
                            :aria-invalid="!!fieldErrors.password"
                            aria-describedby="register-password-hint"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                        <p id="register-password-hint" class="text-[10px] text-zinc-600 mt-1">{{ $t('auth.password_min') }}</p>
                        <p v-if="fieldErrors.password" role="alert" class="text-xs text-red-400 mt-1">
                            {{ fieldErrors.password[0] }}
                        </p>
                    </div>

                    <div>
                        <label for="register-confirm" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">{{ $t('auth.password_confirm') }}</label>
                        <input id="register-confirm" v-model="passwordConfirmation" type="password" required autocomplete="new-password"
                            :aria-invalid="passwordMismatch"
                            :aria-describedby="passwordMismatch ? 'register-confirm-error' : undefined"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40 transition-all" />
                        <p v-if="passwordMismatch" id="register-confirm-error" role="alert" class="text-xs text-red-400 mt-1">
                            Paroles nesakrīt
                        </p>
                    </div>

                    <button type="submit" :disabled="isLoading || !isFormValid"
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                        {{ isLoading ? $t('auth.registering') : $t('auth.register') }}
                    </button>
                </form>

                <p class="text-center mt-6 text-zinc-600 text-sm">
                    Jau ir konts? <router-link to="/login" class="text-amber-400 font-bold hover:text-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 rounded">{{ $t('auth.login') }}</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
