<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';

const { t } = useI18n();
const authStore = useAuthStore();
const { notify } = useNotification();

/* ── Profile form ── */
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

/* ── Password form ── */
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
</script>

<template>
    <!-- Account details form -->
    <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.account') }}</h3>
        <form @submit.prevent="saveProfile" class="space-y-4">
            <div>
                <label for="profile-name" class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.username_label') }}</label>
                <input id="profile-name" v-model="profileForm.name" type="text" required
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/40 focus-visible:ring-2 focus-visible:ring-amber-500/30 transition-all"
                    :class="{ '!border-red-500': profileErrors.name }"
                    :aria-invalid="!!profileErrors.name"
                    :placeholder="t('profile.username_placeholder')" />
                <p v-if="profileErrors.name" role="alert" class="text-xs text-red-400 mt-1">{{ profileErrors.name[0] }}</p>
            </div>
            <div>
                <label for="profile-email" class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('profile.email_label') }}</label>
                <input id="profile-email" v-model="profileForm.email" type="email" required
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/40 focus-visible:ring-2 focus-visible:ring-amber-500/30 transition-all"
                    :class="{ '!border-red-500': profileErrors.email }"
                    :aria-invalid="!!profileErrors.email"
                    :placeholder="t('profile.email_placeholder')" />
                <p v-if="profileErrors.email" role="alert" class="text-xs text-red-400 mt-1">{{ profileErrors.email[0] }}</p>
            </div>
            <button type="submit" :disabled="isSavingProfile"
                class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500">
                {{ isSavingProfile ? $t('common.saving') : $t('profile.save_profile') }}
            </button>
        </form>
    </section>

    <!-- Password change form -->
    <section class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8">
        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('profile.change_password') }}</h3>
        <form @submit.prevent="changePassword" class="space-y-4">
            <div>
                <label for="pw-current" class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('auth.current_password') }}</label>
                <input id="pw-current" v-model="passwordForm.current_password" type="password" required autocomplete="current-password"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/40 focus-visible:ring-2 focus-visible:ring-amber-500/30 transition-all"
                    :class="{ '!border-red-500': passwordErrors.current_password }" />
                <p v-if="passwordErrors.current_password" role="alert" class="text-xs text-red-400 mt-1">{{ passwordErrors.current_password[0] }}</p>
            </div>
            <div>
                <label for="pw-new" class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('auth.new_password') }}</label>
                <input id="pw-new" v-model="passwordForm.password" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/40 focus-visible:ring-2 focus-visible:ring-amber-500/30 transition-all"
                    :class="{ '!border-red-500': passwordErrors.password }" />
                <p v-if="passwordErrors.password" role="alert" class="text-xs text-red-400 mt-1">{{ passwordErrors.password[0] }}</p>
            </div>
            <div>
                <label for="pw-confirm" class="block text-xs font-bold text-zinc-400 mb-2 uppercase tracking-wider">{{ $t('auth.confirm_password') }}</label>
                <input id="pw-confirm" v-model="passwordForm.password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/40 focus-visible:ring-2 focus-visible:ring-amber-500/30 transition-all" />
            </div>
            <button type="submit" :disabled="isChangingPassword"
                class="w-full py-3 bg-zinc-800 text-white font-bold rounded-xl border border-white/10 hover:border-amber-500/30 disabled:opacity-40 transition-all uppercase tracking-wider text-xs">
                {{ isChangingPassword ? $t('common.saving') : $t('profile.change_password') }}
            </button>
        </form>
    </section>
</template>
