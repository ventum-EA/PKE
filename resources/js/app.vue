<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from './stores/auth';
import Header from './components/Header.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import Notification from './components/Notification.vue';
import ScrollToTop from './components/ScrollToTop.vue';
import ShortcutsHelp from './components/ShortcutsHelp.vue';
import WelcomeModal from './components/WelcomeModal.vue';
import TutorialOverlay from './components/TutorialOverlay.vue';
import { useConfirm } from './composables/useConfirm';
import { useNotification } from './composables/useNotification';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import { useTheme } from './composables/useTheme';

const { show: confirmShow, title, message: confirmMsg, type: confirmType, onConfirm, onCancel } = useConfirm();
const { show: notifShow, message: notifMsg, type: notifType } = useNotification();
const { helpOpen, closeHelp } = useKeyboardShortcuts();
useTheme();

// Only show onboarding/tutorial overlays when the user is logged in and not on
// auth pages — guests don't need the dashboard tour.
const auth = useAuthStore();
const route = useRoute();
const AUTH_ROUTES = ['/login', '/register', '/forgot-password', '/reset-password'];
const showOverlays = computed(
    () => auth.isLoggedIn && !AUTH_ROUTES.some(p => route.path.startsWith(p))
);
</script>

<template>
  <div class="min-h-screen flex flex-col theme-bg-app theme-text transition-colors duration-300">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[80] focus:px-4 focus:py-2 focus:bg-amber-500 focus:text-black focus:font-bold focus:rounded-lg focus:shadow-xl">
      {{ $t('app.skip_to_main') }}
    </a>
    <Header />
    <main id="main-content" class="grow" tabindex="-1">
      <router-view v-slot="{ Component, route }">
        <transition name="page" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </transition>
      </router-view>
    </main>
    <footer class="border-t border-white/5 py-6 text-center">
      <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600 font-medium">{{ $t('app.footer') }}</p>
    </footer>
    <ConfirmModal :show="confirmShow" :title="title" :message="confirmMsg" :type="confirmType" @confirm="onConfirm" @cancel="onCancel" />
    <Notification :show="notifShow" :message="notifMsg" :type="notifType" />
    <ShortcutsHelp :show="helpOpen" @close="closeHelp" />
    <ScrollToTop />

    <!-- Onboarding (first visit) + interactive tutorial — only for logged-in users -->
    <template v-if="showOverlays">
      <WelcomeModal />
      <TutorialOverlay />
    </template>
  </div>
</template>
