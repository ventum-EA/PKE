<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import ProfileIdentityCard from '../components/ProfileIdentityCard.vue';
import ProfileAccountForm from '../components/ProfileAccountForm.vue';
import ProfilePreferences from '../components/ProfilePreferences.vue';
import ProfileTwoFactor from '../components/ProfileTwoFactor.vue';
import ProfileDangerZone from '../components/ProfileDangerZone.vue';

const authStore = useAuthStore();
const { t } = useI18n();
const eloHistory = ref([]);
const eloHistoryError = ref(false);

onMounted(async () => {
    try {
        const { data } = await api.get('/elo/history', { limit: 20 });
        eloHistory.value = data?.history || [];
    } catch (err) {
        console.warn('[profile] failed to load ELO history:', err);
        eloHistoryError.value = true;
    }
});

/* Settings model — all user-configurable preferences */
const settings = reactive({
    preferred_color: authStore.user?.preferred_color || 'white',
    dark_mode: authStore.user?.dark_mode ?? true,
    default_difficulty: authStore.user?.default_difficulty ?? 5,
    sound_enabled: authStore.user?.sound_enabled ?? true,
    board_theme: authStore.user?.board_theme || 'classic',
    piece_style: authStore.user?.piece_style || 'standard',
    font_size: authStore.user?.font_size || 'medium',
    high_contrast: authStore.user?.high_contrast ?? false,
});
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8 sm:mb-10">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                    {{ $t('profile.title') }}
                </h1>
            </div>

            <ProfileIdentityCard
                :user="authStore.user"
                :elo-history="eloHistory"
                :elo-history-error="eloHistoryError" />

            <ProfileAccountForm />

            <ProfilePreferences v-model="settings" />

            <ProfileTwoFactor />

            <ProfileDangerZone />
        </div>
    </div>
</template>
