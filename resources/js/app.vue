<script setup>
import Header from './components/Header.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import Notification from './components/Notification.vue';
import ScrollToTop from './components/ScrollToTop.vue';
import ShortcutsHelp from './components/ShortcutsHelp.vue';
import { useConfirm } from './composables/useConfirm';
import { useNotification } from './composables/useNotification';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import { useTheme } from './composables/useTheme';

const { show: confirmShow, title, message: confirmMsg, type: confirmType, onConfirm, onCancel } = useConfirm();
const { show: notifShow, message: notifMsg, type: notifType } = useNotification();
const { helpOpen, closeHelp } = useKeyboardShortcuts();
useTheme(); // Initialize theme from localStorage / prefers-color-scheme
</script>

<template>
  <div class="min-h-screen flex flex-col theme-bg-app theme-text transition-colors duration-300">
    <!-- Skip link for keyboard users (WCAG 2.4.1) -->
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
  </div>
</template>
