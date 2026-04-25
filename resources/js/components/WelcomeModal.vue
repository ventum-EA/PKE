<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTutorial } from '../composables/useTutorial';
import { useAuthStore } from '../stores/auth';

const { t } = useI18n();
const auth = useAuthStore();
const { isFirstTime, start: startTutorial, hasCompleted } = useTutorial();

const show = ref(false);
const dialogRef = ref(null);

onMounted(() => {
    if (auth.isLoggedIn && isFirstTime.value) {
        setTimeout(() => { show.value = true; }, 600);
    }
});

watch(show, async (v) => {
    if (v) {
        await nextTick();
        dialogRef.value?.querySelector('a, button')?.focus();
    }
});

function handleStart() {
    show.value = false;
    startTutorial();
}

function handleSkip() {
    show.value = false;
    try {
        localStorage.setItem('chess_tutorial_state', JSON.stringify({ completed: true, skipped: true }));
    } catch {}
    hasCompleted.value = true;
}
</script>

<template>
    <Teleport to="body">
        <transition name="welcome-fade">
            <div v-if="show" class="fixed inset-0 z-[65] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" @keydown.escape="handleSkip">
                <div ref="dialogRef" role="dialog" aria-modal="true" aria-labelledby="welcome-heading" class="bg-zinc-900 border border-amber-500/20 rounded-3xl shadow-2xl shadow-amber-500/10 w-full max-w-md overflow-hidden">
                    <!-- Decorative top -->
                    <div class="h-32 bg-gradient-to-br from-amber-500/10 via-amber-600/5 to-transparent flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-5xl mb-2">♔</p>
                            <p class="text-amber-400 text-xs font-black uppercase tracking-[0.3em]">Chess Analysis</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <h2 id="welcome-heading" class="text-xl sm:text-2xl font-black text-white mb-3">
                            {{ t('welcome.title') }}
                        </h2>
                        <p class="text-sm text-zinc-400 leading-relaxed mb-6">
                            {{ t('welcome.description') }}
                        </p>

                        <!-- Feature highlights -->
                        <div class="space-y-3 mb-8">
                            <div class="flex items-start gap-3">
                                <span class="text-lg shrink-0 mt-0.5">♟</span>
                                <div>
                                    <p class="text-sm font-bold text-zinc-300">{{ t('welcome.feature_basics') }}</p>
                                    <p class="text-xs text-zinc-500">{{ t('welcome.feature_basics_desc') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-lg shrink-0 mt-0.5">🔍</span>
                                <div>
                                    <p class="text-sm font-bold text-zinc-300">{{ t('welcome.feature_analysis') }}</p>
                                    <p class="text-xs text-zinc-500">{{ t('welcome.feature_analysis_desc') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-lg shrink-0 mt-0.5">📖</span>
                                <div>
                                    <p class="text-sm font-bold text-zinc-300">{{ t('welcome.feature_learn') }}</p>
                                    <p class="text-xs text-zinc-500">{{ t('welcome.feature_learn_desc') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-lg shrink-0 mt-0.5">⚡</span>
                                <div>
                                    <p class="text-sm font-bold text-zinc-300">{{ t('welcome.feature_train') }}</p>
                                    <p class="text-xs text-zinc-500">{{ t('welcome.feature_train_desc') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <router-link to="/lessons" @click="handleSkip"
                                class="flex-1 py-3 px-6 text-sm font-black rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-black hover:from-emerald-400 hover:to-emerald-500 transition-all shadow-lg shadow-emerald-500/20 text-center">
                                {{ t('welcome.learn_chess') }}
                            </router-link>
                            <button @click="handleStart"
                                class="flex-1 py-3 px-6 text-sm font-bold rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-all text-center">
                                {{ t('welcome.start_tour') }}
                            </button>
                            <button @click="handleSkip"
                                class="flex-1 py-3 px-6 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all text-center">
                                {{ t('welcome.skip') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
.welcome-fade-enter-active { transition: opacity 400ms ease; }
.welcome-fade-leave-active { transition: opacity 200ms ease; }
.welcome-fade-enter-from, .welcome-fade-leave-to { opacity: 0; }
</style>
