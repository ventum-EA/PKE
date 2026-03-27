<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const authStore = useAuthStore();
const router = useRouter();

const isFormValid = computed(() => email.value.includes('@') && password.value.length >= 8);

const handleLogin = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        await authStore.login({ email: email.value, password: password.value });
        router.push('/');
    } catch (error) {
        errorMessage.value = error.message || 'Autentifikācija neizdevās. Pārbaudiet datus.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-amber-500/20 mb-6">
                    <svg class="w-12 h-12 text-black" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L9 7H6l-3 5h4l-2 4h3l-3 6h6l-1-3h4l-1 3h6l-3-6h3l-2-4h4L18 7h-3L12 2zm0 3.5L13.5 8h-3L12 5.5z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white">Pieslēgties</h1>
                <p class="text-zinc-500 mt-2 text-sm">Šaha analīzes un mācību platforma</p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-3xl p-8">
                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div v-if="errorMessage" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4">
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">E-pasts</label>
                        <input v-model="email" type="email" required placeholder="admin@example.com"
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/20 transition-all" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Parole</label>
                        <input v-model="password" type="password" required
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/20 transition-all" />
                    </div>

                    <button type="submit" :disabled="isLoading || !isFormValid"
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl hover:from-amber-400 hover:to-amber-500 disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm">
                        <span v-if="isLoading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Autentificē...
                        </span>
                        <span v-else>Pieslēgties</span>
                    </button>
                </form>

                <p class="text-center mt-6 text-zinc-600 text-sm">
                    Nav konta? <router-link to="/register" class="text-amber-400 font-bold hover:text-amber-300 transition-colors">Reģistrēties</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
