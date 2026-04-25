<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useTutorial } from '../composables/useTutorial';

const { t } = useI18n();
const router = useRouter();
const {
    isActive, currentStep, step, totalSteps, progress,
    next, prev, skip, finish,
} = useTutorial();

const tooltipStyle = ref({});
const spotlightStyle = ref({});
const showSpotlight = ref(false);

const STEP_ICONS = {
    welcome: '👋', dashboard: '◈', play: '♚', games: '♟',
    openings: '📖', lessons: '🎓', training: '⚡', profile: '⚙', complete: '🎉',
};

async function positionTooltip() {
    showSpotlight.value = false;

    if (!step.value) return;

    if (step.value.route && router.currentRoute.value.path !== step.value.route) {
        await router.push(step.value.route);
        await nextTick();
        await new Promise(r => setTimeout(r, 350));
    }

    await nextTick();

    if (!step.value.target || step.value.position === 'center') {
        tooltipStyle.value = {};
        spotlightStyle.value = {};
        showSpotlight.value = false;
        return;
    }

    const el = document.querySelector(step.value.target);
    if (!el) {
        tooltipStyle.value = {};
        showSpotlight.value = false;
        return;
    }

    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    await new Promise(r => setTimeout(r, 300));

    const rect = el.getBoundingClientRect();
    const pad = 12;

    spotlightStyle.value = {
        top: (rect.top - pad) + 'px',
        left: (rect.left - pad) + 'px',
        width: (rect.width + pad * 2) + 'px',
        height: (rect.height + pad * 2) + 'px',
    };
    showSpotlight.value = true;

    const tooltipWidth = Math.min(380, window.innerWidth - 32);
    let top, left;

    if (step.value.position === 'bottom' || rect.top > window.innerHeight / 2) {
        top = Math.max(16, rect.top - pad - 16);
        tooltipStyle.value = {
            bottom: (window.innerHeight - top) + 'px',
            left: Math.max(16, Math.min(rect.left, window.innerWidth - tooltipWidth - 16)) + 'px',
            maxWidth: tooltipWidth + 'px',
        };
    } else {
        top = rect.bottom + pad + 16;
        tooltipStyle.value = {
            top: top + 'px',
            left: Math.max(16, Math.min(rect.left, window.innerWidth - tooltipWidth - 16)) + 'px',
            maxWidth: tooltipWidth + 'px',
        };
    }
}

watch([currentStep, isActive], () => {
    if (isActive.value) {
        positionTooltip();
    }
});

function onResize() { if (isActive.value) positionTooltip(); }
onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));

function handleNext() {
    if (currentStep.value >= totalSteps - 1) {
        finish();
    } else {
        next();
    }
}
</script>

<template>
    <Teleport to="body">
        <transition name="tutorial-fade">
            <div v-if="isActive && step" class="fixed inset-0 z-[70]" aria-live="polite">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="skip"></div>

                <!-- Spotlight cutout -->
                <div v-if="showSpotlight"
                    class="absolute rounded-2xl ring-2 ring-amber-400/50 ring-offset-2 ring-offset-transparent transition-all duration-300 pointer-events-none"
                    :style="spotlightStyle">
                    <div class="absolute inset-0 rounded-2xl animate-pulse bg-amber-400/5"></div>
                </div>

                <!-- Tooltip card -->
                <div class="absolute z-[71] w-full sm:w-auto"
                    :class="step.position === 'center' ? 'inset-0 flex items-center justify-center p-4' : ''"
                    :style="step.position !== 'center' ? tooltipStyle : {}">
                    <div class="bg-zinc-900 border border-amber-500/20 rounded-2xl shadow-2xl shadow-amber-500/10 w-full sm:max-w-sm overflow-hidden"
                        role="dialog" aria-modal="true" :aria-label="t('tutorial.title')">

                        <!-- Progress bar -->
                        <div class="h-1 bg-zinc-800">
                            <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 transition-all duration-500"
                                :style="{ width: progress + '%' }"></div>
                        </div>

                        <div class="p-5 sm:p-6">
                            <!-- Icon + Step counter -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-2xl sm:text-3xl">{{ STEP_ICONS[step.key] || '📌' }}</span>
                                <span class="text-[10px] font-bold text-zinc-600 uppercase tracking-widest">
                                    {{ currentStep + 1 }} / {{ totalSteps }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-base sm:text-lg font-black text-white mb-2">
                                {{ t('tutorial.steps.' + step.key + '.title') }}
                            </h3>

                            <!-- Description -->
                            <p class="text-sm text-zinc-400 leading-relaxed mb-5">
                                {{ t('tutorial.steps.' + step.key + '.desc') }}
                            </p>

                            <!-- Tip (if exists) -->
                            <p v-if="t('tutorial.steps.' + step.key + '.tip') !== 'tutorial.steps.' + step.key + '.tip'"
                                class="text-xs text-amber-400/70 bg-amber-500/5 rounded-lg px-3 py-2 mb-4 border border-amber-500/10">
                                💡 {{ t('tutorial.steps.' + step.key + '.tip') }}
                            </p>

                            <!-- Navigation -->
                            <div class="flex items-center gap-2">
                                <button v-if="currentStep > 0" @click="prev"
                                    class="px-4 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all">
                                    ← {{ t('tutorial.prev') }}
                                </button>
                                <button @click="skip"
                                    class="px-4 py-2.5 text-xs font-bold rounded-xl text-zinc-600 hover:text-zinc-400 transition-all ml-auto">
                                    {{ t('tutorial.skip') }}
                                </button>
                                <button @click="handleNext"
                                    class="px-6 py-2.5 text-xs font-black rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-black hover:from-amber-400 hover:to-amber-500 transition-all shadow-lg shadow-amber-500/20">
                                    {{ currentStep >= totalSteps - 1 ? t('tutorial.finish') : t('tutorial.next') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
.tutorial-fade-enter-active,
.tutorial-fade-leave-active {
    transition: opacity 250ms ease;
}
.tutorial-fade-enter-from,
.tutorial-fade-leave-to {
    opacity: 0;
}
</style>
