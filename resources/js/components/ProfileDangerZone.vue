<script setup>
import { reactive, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';

const { t } = useI18n();
const authStore = useAuthStore();
const { notify } = useNotification();

const deleteForm = reactive({ password: '', confirm: '' });
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

function cancelDelete() {
    showDeleteConfirm.value = false;
    deleteForm.password = '';
    deleteForm.confirm = '';
}
</script>

<template>
    <section class="bg-red-950/20 border border-red-500/20 rounded-3xl p-6 sm:p-8 mt-6 sm:mt-8"
        aria-labelledby="danger-zone-heading">
        <div class="flex items-start gap-3 mb-6">
            <span class="text-2xl" aria-hidden="true">⚠</span>
            <div>
                <h3 id="danger-zone-heading" class="text-xs font-black uppercase tracking-widest text-red-400">{{ $t('profile.danger_zone') }}</h3>
                <p class="text-sm text-zinc-400 mt-1">{{ $t('profile.danger_zone_desc') }}</p>
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
                    {{ $t('profile.delete_type_keyword') }} <span class="font-mono bg-red-500/10 px-2 py-0.5 rounded">{{ $t('profile.delete_keyword') }}</span>
                </label>
                <input id="delete-confirm" v-model="deleteForm.confirm" type="text" required autocomplete="off"
                    class="w-full bg-black/40 border border-red-500/30 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-red-500/60 focus-visible:ring-2 focus-visible:ring-red-500/50 transition-all"
                    :placeholder="t('profile.delete_confirm_placeholder')" />
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <button type="submit" :disabled="!canDelete || isDeleting"
                    class="flex-1 py-3 bg-red-500/20 text-red-400 font-black rounded-xl border border-red-500/40 hover:bg-red-500/30 disabled:opacity-30 disabled:cursor-not-allowed transition-all uppercase tracking-wider text-xs">
                    {{ isDeleting ? $t('common.loading') : $t('profile.delete_permanently') }}
                </button>
                <button type="button" @click="cancelDelete"
                    class="px-6 py-3 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-white transition-all uppercase tracking-wider text-xs">
                    {{ $t('common.cancel') }}
                </button>
            </div>
        </form>
    </section>
</template>
