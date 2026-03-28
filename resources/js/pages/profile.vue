<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import { useNotification } from '../composables/useNotification';

const authStore = useAuthStore();
const { notify } = useNotification();
const isSaving = ref(false);

const settings = ref({
    preferred_color: authStore.user?.preferred_color || 'white',
    dark_mode: true,
    sound_enabled: true,
});

const saveSettings = async () => {
    isSaving.value = true;
    try {
        await api.put('/user/settings', settings.value);
        notify('Iestatījumi saglabāti!', 'success');
    } catch {
        notify('Kļūda saglabājot iestatījumus', 'error');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-3xl mx-auto">
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">◉</span> Profils</h1>
            </div>

            <!-- Profile Card -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-8 mb-8">
                <div class="flex items-center gap-6 mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center text-3xl font-black text-black shadow-xl shadow-amber-500/20">
                        {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-white">{{ authStore.user?.name }}</h2>
                        <p class="text-zinc-500 text-sm">{{ authStore.user?.email }}</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs font-bold text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
                                ELO {{ authStore.user?.elo_rating }}
                            </span>
                            <span class="text-xs font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ authStore.user?.role }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-black/30 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-white">♔</p>
                        <p class="text-xs text-zinc-500 mt-1 font-bold uppercase tracking-wider">Reģ. datums</p>
                        <p class="text-sm text-zinc-300 font-bold mt-1">{{ authStore.user?.created_at?.split(' ')[0] }}</p>
                    </div>
                    <div class="bg-black/30 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-white">{{ authStore.user?.role === 'admin' ? '⚙' : '♟' }}</p>
                        <p class="text-xs text-zinc-500 mt-1 font-bold uppercase tracking-wider">Loma</p>
                        <p class="text-sm text-zinc-300 font-bold mt-1 capitalize">{{ authStore.user?.role }}</p>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">Iestatījumi</h3>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-zinc-400 mb-2">Vēlamā krāsa</label>
                        <div class="flex gap-3">
                            <button v-for="c in ['white', 'black']" :key="c" @click="settings.preferred_color = c"
                                :class="['px-5 py-2.5 rounded-xl font-bold text-sm border transition-all',
                                    settings.preferred_color === c
                                        ? 'bg-amber-500/10 border-amber-500/30 text-amber-400'
                                        : 'bg-zinc-900 border-white/5 text-zinc-500 hover:text-zinc-300']">
                                {{ c === 'white' ? '♔ Baltais' : '♚ Melnais' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-zinc-400">Skaņas efekti</p>
                            <p class="text-xs text-zinc-600">Gājienu un paziņojumu skaņas</p>
                        </div>
                        <button @click="settings.sound_enabled = !settings.sound_enabled"
                            :class="['w-12 h-7 rounded-full transition-all relative',
                                settings.sound_enabled ? 'bg-amber-500' : 'bg-zinc-700']">
                            <span :class="['absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all',
                                settings.sound_enabled ? 'left-5.5' : 'left-0.5']"></span>
                        </button>
                    </div>

                    <button @click="saveSettings" :disabled="isSaving"
                        class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm">
                        {{ isSaving ? 'Saglabā...' : 'Saglabāt iestatījumus' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
