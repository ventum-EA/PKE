<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';
import { useTutorial } from '../composables/useTutorial';
import LanguageSwitcher from './LanguageSwitcher.vue';
import { useI18n } from 'vue-i18n';

const auth = useAuthStore();
const route = useRoute();
const mobileOpen = ref(false);
const { theme, toggleTheme } = useTheme();
const { start: startTutorial } = useTutorial();
const { t } = useI18n();

const navLinks = computed(() => [
    { to: '/', label: t('nav.dashboard'), icon: '◈' },
    { to: '/daily', label: t('nav.daily'), icon: '📅' },
    { separator: true },
    { to: '/lessons', label: t('nav.lessons'), icon: '🎓' },
    { to: '/openings', label: t('nav.openings'), icon: '📖' },
    { to: '/training', label: t('nav.training'), icon: '⚡' },
    { separator: true },
    { to: '/games', label: t('nav.games'), icon: '📂' },
    { to: '/achievements', label: t('nav.achievements'), icon: '🏆' },
    { to: '/puzzles', label: t('nav.puzzles'), icon: '◆' },
]);

const initial = computed(() => auth.user?.name?.charAt(0).toUpperCase() || '?');

watch(() => route.fullPath, () => { mobileOpen.value = false; });
</script>

<template>
    <header class="sticky top-0 z-50 backdrop-blur-xl border-b transition-colors duration-300"
        :style="{ backgroundColor: 'color-mix(in srgb, var(--color-bg-app) 90%, transparent)', borderColor: 'var(--color-border)' }">
        <nav class="mx-auto px-3 sm:px-4 lg:px-6 py-2 flex items-center gap-1"
            :aria-label="t('nav.primary_nav')">
            <!-- Logo -->
            <router-link to="/" class="flex items-center gap-2 group shrink-0 mr-1">
                <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-amber-600 rounded-lg flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:shadow-amber-500/40 transition-shadow">
                    <svg class="w-5 h-5 text-black" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L9 7H6l-3 5h4l-2 4h3l-3 6h6l-1-3h4l-1 3h6l-3-6h3l-2-4h4L18 7h-3L12 2zm0 3.5L13.5 8h-3L12 5.5z"/>
                    </svg>
                </div>
                <div class="hidden sm:block">
                    <!-- theme-text instead of text-white: white-on-white was unreadable in the light theme -->
                    <span class="text-sm font-black tracking-tight theme-text">ŠAHA</span>
                    <span class="text-sm font-light tracking-tight text-amber-500 ml-0.5">ANALĪZE</span>
                </div>
            </router-link>

            <!-- Desktop nav (xl+) -->
            <div class="hidden xl:flex items-center gap-0.5 flex-1 justify-center min-w-0" v-if="auth.isLoggedIn">
                <template v-for="(link, i) in navLinks" :key="i">
                    <div v-if="link.separator" class="w-px h-4 bg-white/10 mx-0.5 shrink-0"></div>
                    <router-link v-else :to="link.to"
                        class="px-2 py-1 rounded-lg text-[11px] font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-all whitespace-nowrap shrink-0"
                        active-class="!text-amber-400 !bg-amber-400/10">
                        <span class="mr-0.5">{{ link.icon }}</span>{{ link.label }}
                    </router-link>
                </template>

                <router-link v-if="auth.isAdmin" to="/admin"
                    class="px-2 py-1 rounded-lg text-[11px] font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-all shrink-0"
                    active-class="!text-amber-400 !bg-amber-400/10">
                    <span class="mr-0.5">⚙</span>Admin
                </router-link>
            </div>

            <!-- Desktop right side (xl+) -->
            <div class="hidden xl:flex items-center gap-1 shrink-0" v-if="auth.isLoggedIn">
                <LanguageSwitcher />
                <button type="button" @click="startTutorial"
                    :aria-label="t('tutorial.restart')"
                    :title="t('tutorial.restart')"
                    class="p-1.5 rounded-lg text-zinc-500 hover:text-amber-400 hover:bg-white/5 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                    <span aria-hidden="true" class="text-sm">❓</span>
                </button>
                <button type="button" @click="toggleTheme"
                    :aria-label="theme === 'dark' ? t('nav.light_theme') : t('nav.dark_theme')"
                    :title="theme === 'dark' ? t('nav.light_theme') : t('nav.dark_theme')"
                    class="p-1.5 rounded-lg text-zinc-500 hover:text-amber-400 hover:bg-white/5 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                    <span aria-hidden="true" class="text-sm">{{ theme === 'dark' ? '☀' : '🌙' }}</span>
                </button>
                <router-link to="/profile" class="flex items-center gap-2 group cursor-pointer px-2 py-1 rounded-lg hover:bg-white/5 transition-all">
                    <div class="w-7 h-7 bg-gradient-to-br from-amber-400 to-amber-600 rounded-md flex items-center justify-center text-[10px] font-black text-black shrink-0">
                        {{ initial }}
                    </div>
                    <div class="text-right min-w-0">
                        <p class="text-[11px] font-bold text-zinc-300 group-hover:text-amber-400 transition-colors truncate max-w-[6rem]">{{ auth.user?.name }}</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-wider">ELO {{ auth.user?.elo_rating }}</p>
                    </div>
                </router-link>
                <router-link to="/logout"
                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider text-zinc-500 border border-zinc-800 hover:border-red-900 hover:text-red-400 transition-all">
                    {{ t('nav.logout') }}
                </router-link>
            </div>

            <!-- Mobile/tablet toggle (< xl) -->
            <button @click="mobileOpen = !mobileOpen"
                class="xl:hidden ml-auto p-2 text-zinc-300 hover:text-amber-400 rounded-xl hover:bg-white/5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
                :aria-expanded="mobileOpen"
                aria-controls="mobile-nav-menu"
                :aria-label="t('nav.menu')"
                v-if="auth.isLoggedIn">
                <svg v-if="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </nav>

        <!-- Mobile dropdown -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2">
            <div v-if="mobileOpen && auth.isLoggedIn"
                id="mobile-nav-menu"
                class="xl:hidden border-t border-white/5 px-4 sm:px-6 py-4 bg-[#0c0c0e]/95 backdrop-blur-xl">
                <!-- User summary -->
                <router-link to="/profile" @click="mobileOpen = false"
                    class="flex items-center gap-3 mb-4 p-3 bg-zinc-900/50 rounded-2xl border border-white/5">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center text-sm font-black text-black shrink-0">
                        {{ initial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-white truncate">{{ auth.user?.name }}</p>
                        <p class="text-[10px] text-zinc-500 uppercase tracking-wider">ELO {{ auth.user?.elo_rating }}</p>
                    </div>
                    <span class="text-amber-400 text-lg">›</span>
                </router-link>

                <!-- Nav links -->
                <div class="flex flex-col gap-0.5">
                    <template v-for="(link, i) in navLinks" :key="i">
                        <div v-if="link.separator" class="h-px bg-white/5 my-2"></div>
                        <router-link v-else :to="link.to"
                            class="py-3 px-3 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-colors flex items-center gap-3"
                            active-class="!text-amber-400 !bg-amber-400/10"
                            @click="mobileOpen = false">
                            <span class="text-lg w-6 text-center shrink-0">{{ link.icon }}</span>
                            {{ link.label }}
                        </router-link>
                    </template>

                    <router-link v-if="auth.isAdmin" to="/admin"
                        class="py-3 px-3 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-white/5 transition-colors flex items-center gap-3"
                        active-class="!text-amber-400 !bg-amber-400/10">
                        <span class="text-base w-5 text-center">⚙</span>Admin
                    </router-link>

                    <div class="h-px bg-white/5 my-2"></div>

                    <button type="button" @click="startTutorial(); mobileOpen = false"
                        class="w-full py-3 px-3 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-amber-500/5 transition-colors flex items-center gap-3 text-left">
                        <span class="text-base w-5 text-center">❓</span>
                        {{ t('tutorial.help') }}
                    </button>

                    <button type="button" @click="toggleTheme"
                        class="w-full py-3 px-3 rounded-xl text-sm font-semibold text-zinc-400 hover:text-amber-400 hover:bg-amber-500/5 transition-colors flex items-center gap-3 text-left">
                        <span class="text-base w-5 text-center">{{ theme === 'dark' ? '☀' : '🌙' }}</span>
                        {{ theme === 'dark' ? t('nav.light_theme') : t('nav.dark_theme') }}
                    </button>

                    <router-link to="/logout"
                        class="py-3 px-3 rounded-xl text-sm font-semibold text-zinc-500 hover:text-red-400 hover:bg-red-500/5 transition-colors flex items-center gap-3">
                        <span class="text-base w-5 text-center">↩</span>{{ t('nav.logout') }}
                    </router-link>
                </div>
            </div>
        </transition>
    </header>
</template>
