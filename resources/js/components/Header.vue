<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
const auth = useAuthStore();
const mobileOpen = ref(false);

const navLinks = [
    { to: '/', label: 'Panelis', icon: '◈' },
    { to: '/games', label: 'Partijas', icon: '♟' },
    { to: '/play', label: 'Spēlēt', icon: '♚' },
    { to: '/openings', label: 'Atklātnes', icon: '📖' },
    { to: '/lessons', label: 'Mācības', icon: '🎓' },
    { to: '/training', label: 'Treniņi', icon: '⚡' },
];
</script>

<template>
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-[#0c0c0e]/90 border-b border-white/5">
        <nav class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <router-link to="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:shadow-amber-500/40 transition-shadow">
                    <svg class="w-6 h-6 text-black" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L9 7H6l-3 5h4l-2 4h3l-3 6h6l-1-3h4l-1 3h6l-3-6h3l-2-4h4L18 7h-3L12 2zm0 3.5L13.5 8h-3L12 5.5z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-white">ŠAHA</span>
                    <span class="text-lg font-light tracking-tight text-amber-400 ml-1">ANALĪZE</span>
                </div>
            </router-link>

            <div class="hidden md:flex items-center gap-1" v-if="auth.isLoggedIn">
                <router-link v-for="link in navLinks" :key="link.to" :to="link.to"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-all"
                    active-class="!text-amber-400 !bg-amber-400/10">
                    <span class="mr-1.5">{{ link.icon }}</span>{{ link.label }}
                </router-link>

                <router-link v-if="auth.isAdmin" to="/admin"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-all"
                    active-class="!text-amber-400 !bg-amber-400/10">
                    <span class="mr-1.5">⚙</span>Admin
                </router-link>
            </div>

            <div class="hidden md:flex items-center gap-4" v-if="auth.isLoggedIn">
                <router-link to="/profile" class="text-right group cursor-pointer">
                    <p class="text-xs font-bold text-zinc-400 group-hover:text-amber-400 transition-colors">{{ auth.user?.name }}</p>
                    <p class="text-[10px] text-zinc-600 uppercase tracking-wider">ELO {{ auth.user?.elo_rating }}</p>
                </router-link>
                <router-link to="/logout"
                    class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider text-zinc-500 border border-zinc-800 hover:border-red-900 hover:text-red-400 transition-all">
                    Iziet
                </router-link>
            </div>

            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-zinc-400" v-if="auth.isLoggedIn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </nav>

        <div v-if="mobileOpen && auth.isLoggedIn" class="md:hidden border-t border-white/5 px-6 py-4 flex flex-col gap-2">
            <router-link v-for="link in [...navLinks, {to:'/profile',label:'Profils'}, ...(auth.isAdmin ? [{to:'/admin',label:'Admin'}] : []), {to:'/logout',label:'Iziet'}]"
                :key="link.to" :to="link.to" @click="mobileOpen = false"
                class="py-2 text-sm font-semibold text-zinc-400 hover:text-amber-400 transition-colors">
                {{ link.label }}
            </router-link>
        </div>
    </header>
</template>
