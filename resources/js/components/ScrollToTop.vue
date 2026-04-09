<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const visible = ref(false);
const SCROLL_THRESHOLD = 400;

const handleScroll = () => {
    visible.value = window.scrollY > SCROLL_THRESHOLD;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
    <Transition
        enter-from-class="opacity-0 translate-y-2 scale-90"
        enter-active-class="transition-all duration-200 ease-out"
        leave-to-class="opacity-0 translate-y-2 scale-90"
        leave-active-class="transition-all duration-200 ease-in">
        <button v-if="visible"
            type="button"
            @click="scrollToTop"
            aria-label="Ritināt uz lapas augšu"
            class="fixed bottom-24 right-6 z-40 w-11 h-11 bg-zinc-900/90 border border-amber-500/30 text-amber-400 rounded-full backdrop-blur-md shadow-lg shadow-black/40 hover:bg-amber-500/15 hover:scale-110 active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
            <span aria-hidden="true" class="text-lg leading-none">↑</span>
        </button>
    </Transition>
</template>
