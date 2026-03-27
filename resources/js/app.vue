<script setup>
import Header from './components/Header.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import Notification from './components/Notification.vue';
import { useConfirm } from './composables/useConfirm';
import { useNotification } from './composables/useNotification';

const { show: confirmShow, title, message: confirmMsg, type: confirmType, onConfirm, onCancel } = useConfirm();
const { show: notifShow, message: notifMsg, type: notifType } = useNotification();
</script>

<template>
  <div class="min-h-screen flex flex-col bg-[#0c0c0e]">
    <Header />
    <main class="grow">
      <router-view />
    </main>
    <footer class="border-t border-white/5 py-6 text-center">
      <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600 font-medium">Šaha Analīzes Platforma · Ēriks Anisimovičs · PKE 2026</p>
    </footer>
    <ConfirmModal :show="confirmShow" :title="title" :message="confirmMsg" :type="confirmType" @confirm="onConfirm" @cancel="onCancel" />
    <Notification :show="notifShow" :message="notifMsg" :type="notifType" />
  </div>
</template>
