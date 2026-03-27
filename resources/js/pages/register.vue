<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const authStore = useAuthStore();
const router = useRouter();

const isFormValid = computed(() =>
    name.value.length >= 2 && email.value.includes('@') &&
    password.value.length >= 8 && password.value === password_confirmation.value
);

const handleRegister = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        await authStore.register({
            name: name.value, email: email.value,
            password: password.value, password_confirmation: password_confirmation.value,
        });
        router.push('/');
    } catch (error) {
        errorMessage.value = error.message || 'Reģistrācija neizdevās.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black tracking-tight text-white">Reģistrācija</h1>
                <p class="text-zinc-500 mt-2 text-sm">Izveidojiet jaunu kontu</p>
            </div>

            <div class="bg-zinc-900/50 backdrop-blur border border-white/5 rounded-3xl p-8">
                <form @submit.prevent="handleRegister" class="space-y-5">
                    <div v-if="errorMessage" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4">
                        <p class="text-sm text-red-400">{{ errorMessage }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Lietotājvārds</label>
                        <input v-model="name" type="text" required class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">E-pasts</label>
                        <input v-model="email" type="email" required class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Parole</label>
                        <input v-model="password" type="password" required class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Paroles apstiprinājums</label>
                        <input v-model="password_confirmation" type="password" required class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 transition-all" />
                    </div>

                    <button type="submit" :disabled="isLoading || !isFormValid"
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 transition-all shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm">
                        {{ isLoading ? 'Reģistrē...' : 'Reģistrēties' }}
                    </button>
                </form>

                <p class="text-center mt-6 text-zinc-600 text-sm">
                    Jau ir konts? <router-link to="/login" class="text-amber-400 font-bold hover:text-amber-300">Pieslēgties</router-link>
                </p>
            </div>
        </div>
    </div>
</template>
