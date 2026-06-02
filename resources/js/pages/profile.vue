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

/* Settings model — shared with ProfilePreferences via v-model */
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
