<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import api from '../services/api';
import ChessBoard from '../components/ChessBoard.vue';

const { t, locale } = useI18n();
const { notify } = useNotification();
const auth = useAuthStore();
const { boardSize } = useResponsiveBoard({ maxSize: 440, padding: 48 });

const puzzle = ref(null);
const attempt = ref(null);
const leaderboard = ref([]);
const totalSolvers = ref(0);
const totalAttempts = ref(0);
const history = ref([]);
const streak = ref(0);

const isLoading = ref(true);
const isSubmitting = ref(false);
const feedback = ref(null);
const showExplanation = ref(false);
const showSuccess = ref(false);
const startTime = ref(null);
const elapsedSeconds = ref(0);
let timerHandle = null;
let feedbackTimer = null;

const isSolved = computed(() => attempt.value?.solved === true);
const boardOrientation = computed(() => {
    if (!puzzle.value?.fen) return 'white';
    return puzzle.value.fen.includes(' b ') ? 'black' : 'white';
});
const turnLabel = computed(() =>
    boardOrientation.value === 'white' ? t('common.white') : t('common.black'));
const difficultyStars = computed(() => puzzle.value?.difficulty || 1);

// Localized short date for the history list (avoid raw ISO "2026-06-12").
const formatHistoryDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    if (isNaN(d)) return iso;
    return d.toLocaleDateString(locale.value === 'lv' ? 'lv-LV' : 'en-GB', {
        day: 'numeric', month: 'short',
    });
};
const difficultyColor = computed(() => {
    const d = puzzle.value?.difficulty || 1;
    return d === 1 ? 'text-emerald-400 bg-emerald-500/5 border-emerald-500/10'
         : d === 2 ? 'text-amber-400 bg-amber-500/5 border-amber-500/10'
         : 'text-red-400 bg-red-500/5 border-red-500/10';
});
const theme = computed(() => {
    if (!puzzle.value) return '';
    return locale.value === 'lv' ? (puzzle.value.theme_lv || puzzle.value.theme) : puzzle.value.theme;
});
const explanation = computed(() => {
    if (!puzzle.value) return '';
    return locale.value === 'lv' ? (puzzle.value.explanation_lv || puzzle.value.explanation) : puzzle.value.explanation;
});
const solveRate = computed(() => {
    if (!totalAttempts.value) return 0;
    return Math.round((totalSolvers.value / totalAttempts.value) * 100);
});

function startTimer() {
    startTime.value = Date.now();
    elapsedSeconds.value = 0;
    timerHandle = setInterval(() => {
        elapsedSeconds.value = Math.floor((Date.now() - startTime.value) / 1000);
    }, 250);
}
function stopTimer() {
    if (timerHandle) { clearInterval(timerHandle); timerHandle = null; }
}
function formatTime(s) {
    if (s == null) return '—';
    return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`;
}

function handleMove(move) {
    if (isSolved.value || isSubmitting.value) return;
    submitMove(move.from + move.to + (move.promotion || ''));
}

async function submitMove(move) {
    if (isSubmitting.value || isSolved.value) return;
    isSubmitting.value = true;
    feedback.value = null;
    clearTimeout(feedbackTimer);

    try {
        const timeSpent = startTime.value ? Math.floor((Date.now() - startTime.value) / 1000) : 0;
        const { data } = await api.post('/daily-puzzle/submit', { move, time_spent: timeSpent });
        attempt.value = { solved: data.solved, attempts: data.attempts, solve_time_seconds: timeSpent };

        if (data.solved) {
            stopTimer();
            puzzle.value.correct_move = data.correct_move;
            puzzle.value.explanation = data.explanation;
            puzzle.value.explanation_lv = data.explanation_lv;
            feedback.value = { correct: true };
            showSuccess.value = true;
            setTimeout(() => { showSuccess.value = false; }, 2800);
            loadPuzzle();
            loadHistory();
            try { await api.post('/achievements/check'); } catch (err) { console.warn("API call silenced:", err) }
        } else {
            feedback.value = { correct: false };
            feedbackTimer = setTimeout(() => { feedback.value = null; }, 2200);
        }
    } catch (err) {
        notify(err?.message || t('daily.submit_failed'), 'error');
    } finally {
        isSubmitting.value = false;
    }
}

async function loadPuzzle() {
    try {
        const { data } = await api.get('/daily-puzzle');
        puzzle.value = data.puzzle;
        attempt.value = data.attempt;
        leaderboard.value = data.leaderboard || [];
        totalSolvers.value = data.total_solvers || 0;
        totalAttempts.value = data.total_attempts || 0;
        if (data.puzzle && !data.attempt?.solved) startTimer();
    } catch (err) {
        notify(err?.message || t('daily.load_failed'), 'error');
    }
}
async function loadHistory() {
    try {
        const { data } = await api.get('/daily-puzzle/history');
        history.value = data.history || [];
        streak.value = data.streak || 0;
    } catch { /* intentionally silenced */ }
}

onMounted(async () => {
    await loadPuzzle();
    await loadHistory();
    isLoading.value = false;
});
onUnmounted(() => {
    stopTimer();
    clearTimeout(feedbackTimer);
});
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-6 sm:mb-10">
                <div class="flex items-start sm:items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-4xl font-black tracking-tight">
                            <span class="text-amber-400">📅</span> {{ $t('daily.title') }}
                        </h1>
                        <p class="text-zinc-500 text-xs sm:text-sm mt-1 uppercase tracking-widest font-bold">
                            {{ new Date().toLocaleDateString(locale === 'lv' ? 'lv-LV' : 'en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                        </p>
                    </div>
                    <div v-if="streak > 0" class="px-4 py-2.5 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center gap-2.5 shrink-0">
                        <span class="text-xl">🔥</span>
                        <div>
                            <p class="text-base font-black text-amber-400 tabular-nums leading-none">{{ streak }}</p>
                            <p class="text-[9px] text-amber-400/50 font-bold uppercase tracking-wider mt-0.5">{{ $t('daily.streak_days') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading skeleton -->
            <div v-if="isLoading" class="flex flex-col lg:flex-row gap-6" aria-busy="true">
                <span class="sr-only">{{ $t('dashboard.loading') }}</span>
                <div class="flex-shrink-0 mx-auto lg:mx-0">
                    <div class="h-5 w-28 bg-zinc-800/40 rounded animate-pulse mb-3"></div>
                    <div class="h-4 w-40 bg-zinc-800/30 rounded animate-pulse mb-3"></div>
                    <div class="bg-zinc-800/40 rounded-xl animate-pulse" :style="{ width: boardSize + 'px', height: boardSize + 'px' }"></div>
                    <div class="flex justify-between mt-3">
                        <div class="h-4 w-16 bg-zinc-800/30 rounded animate-pulse"></div>
                        <div class="h-4 w-20 bg-zinc-800/30 rounded animate-pulse"></div>
                    </div>
                </div>
                <div class="flex-1 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div v-for="i in 3" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4">
                            <div class="h-6 w-10 bg-zinc-800/40 rounded animate-pulse mx-auto mb-2"></div>
                            <div class="h-2 w-12 bg-zinc-800/30 rounded animate-pulse mx-auto"></div>
                        </div>
                    </div>
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <div class="h-3 w-24 bg-zinc-800/40 rounded animate-pulse mb-4"></div>
                        <div class="space-y-3">
                            <div v-for="i in 5" :key="i" class="h-10 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No puzzle -->
            <div v-else-if="!puzzle" class="max-w-md mx-auto mt-16">
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-10 text-center">
                    <p class="text-5xl mb-5 opacity-30">📅</p>
                    <h2 class="text-lg font-black text-zinc-300 mb-2">{{ $t('daily.no_puzzle') }}</h2>
                    <p class="text-sm text-zinc-600 leading-relaxed">{{ $t('daily.check_tomorrow') }}</p>
                </div>
            </div>

            <!-- Main -->
            <div v-else class="flex flex-col lg:flex-row gap-6">
                <!-- Board column -->
                <div class="flex-shrink-0 mx-auto lg:mx-0" :style="{ width: boardSize + 'px', maxWidth: '100%' }">
                    <!-- Badges -->
                    <div class="flex items-center gap-2 mb-3">
                        <span :class="['flex items-center gap-0.5 px-2.5 py-1 rounded-lg border text-[10px] font-black uppercase tracking-wider', difficultyColor]">
                            <span v-for="s in difficultyStars" :key="s">★</span>
                        </span>
                        <span v-if="theme" class="text-xs text-zinc-400 font-bold truncate">{{ theme }}</span>
                    </div>

                    <!-- Turn indicator -->
                    <div v-if="!isSolved" class="flex items-center gap-2 mb-2 px-0.5">
                        <div class="w-2.5 h-2.5 rounded-full" :class="boardOrientation === 'white' ? 'bg-white animate-pulse' : 'bg-zinc-500 animate-pulse'"></div>
                        <span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">
                            {{ $t('daily.find_best_move', { color: turnLabel }) }}
                        </span>
                    </div>
                    <div v-else class="flex items-center gap-2 mb-2 px-0.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] font-bold text-emerald-400/70 uppercase tracking-wider">{{ $t('daily.completed') }}</span>
                    </div>

                    <!-- Board with success overlay -->
                    <div class="relative">
                        <ChessBoard :fen="puzzle.fen" :orientation="boardOrientation" :interactive="!isSolved && !isSubmitting" :size="boardSize" :resizable="false" @move="handleMove" />
                        <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 scale-105" leave-active-class="transition-all duration-300" leave-to-class="opacity-0">
                            <div v-if="showSuccess" class="absolute inset-0 flex items-center justify-center bg-emerald-900/30 backdrop-blur-[2px] rounded-xl pointer-events-none">
                                <div class="text-center animate-bounce">
                                    <p class="text-4xl sm:text-5xl mb-1">🎉</p>
                                    <p class="text-lg font-black text-emerald-400 drop-shadow-lg">{{ $t('daily.correct') }}</p>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Timer row -->
                    <div class="flex items-center justify-between mt-3 px-0.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-zinc-600 text-[11px]">⏱</span>
                            <span class="text-sm font-mono tabular-nums" :class="isSolved ? 'text-zinc-500' : 'text-zinc-300'">
                                {{ isSolved && attempt?.solve_time_seconds != null ? formatTime(attempt.solve_time_seconds) : formatTime(elapsedSeconds) }}
                            </span>
                        </div>
                        <div v-if="attempt?.attempts" class="text-[11px] text-zinc-600 tabular-nums">
                            {{ attempt.attempts }} {{ attempt.attempts === 1 ? $t('daily.attempt_singular') : $t('daily.attempts') }}
                        </div>
                    </div>

                    <!-- Incorrect feedback -->
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-2" leave-active-class="transition-all duration-200" leave-to-class="opacity-0 -translate-y-1">
                        <div v-if="feedback && !feedback.correct" class="mt-3 p-3 bg-red-500/5 border border-red-500/10 rounded-xl">
                            <p class="text-sm font-bold text-red-400 flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-red-500/10 flex items-center justify-center text-[10px]">✕</span>
                                {{ $t('daily.incorrect') }}
                            </p>
                            <p class="text-[11px] text-zinc-500 mt-1 ml-[26px]">{{ $t('daily.try_again') }}</p>
                        </div>
                    </Transition>

                    <!-- Explanation toggle -->
                    <div v-if="isSolved && explanation" class="mt-3">
                        <button @click="showExplanation = !showExplanation" class="w-full text-left text-[11px] font-bold text-amber-400/60 hover:text-amber-400 transition-colors flex items-center gap-1.5 py-1">
                            <span class="transition-transform duration-200" :class="showExplanation ? 'rotate-90' : ''">▸</span>
                            {{ $t('daily.show_explanation') }}
                        </button>
                        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0" leave-active-class="transition-all duration-150" leave-to-class="opacity-0">
                            <p v-if="showExplanation" class="text-sm text-zinc-400 mt-1.5 pl-3 border-l-2 border-amber-500/20 leading-relaxed">
                                {{ explanation }}
                            </p>
                        </Transition>
                    </div>
                </div>

                <!-- Right panels -->
                <div class="flex-1 min-w-0 space-y-4 sm:space-y-5">
                    <!-- Stat cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-3 sm:p-4 text-center hover:border-white/10 transition-all">
                            <p class="text-lg sm:text-xl font-black text-amber-400 tabular-nums">{{ totalSolvers }}</p>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-600 mt-0.5">{{ $t('daily.solvers') }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-3 sm:p-4 text-center hover:border-white/10 transition-all">
                            <p class="text-lg sm:text-xl font-black text-zinc-300 tabular-nums">{{ solveRate }}%</p>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-600 mt-0.5">{{ $t('daily.solve_rate') }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-3 sm:p-4 text-center hover:border-white/10 transition-all">
                            <p class="text-lg sm:text-xl font-black tabular-nums" :class="streak > 0 ? 'text-amber-400' : 'text-zinc-600'">{{ streak }}</p>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-zinc-600 mt-0.5">{{ $t('daily.streak_label') }}</p>
                        </div>
                    </div>

                    <!-- Leaderboard -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-white/5 flex items-center justify-between">
                            <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-zinc-500">🏆 {{ $t('daily.leaderboard') }}</h3>
                            <span class="text-[10px] text-zinc-600 tabular-nums">{{ $t('daily.top_10') }}</span>
                        </div>
                        <div v-if="leaderboard.length" class="divide-y divide-white/5">
                            <div v-for="(entry, i) in leaderboard" :key="i"
                                :class="['flex items-center gap-2 sm:gap-3 px-4 sm:px-5 py-2.5 sm:py-3 transition-colors',
                                    entry.name === auth.user?.name ? 'bg-amber-500/5' : 'hover:bg-white/[0.02]']">
                                <div class="w-7 text-center shrink-0">
                                    <span v-if="i === 0" class="text-sm sm:text-base">🥇</span>
                                    <span v-else-if="i === 1" class="text-sm sm:text-base">🥈</span>
                                    <span v-else-if="i === 2" class="text-sm sm:text-base">🥉</span>
                                    <span v-else class="text-xs font-black text-zinc-600 tabular-nums">{{ i + 1 }}</span>
                                </div>
                                <span class="text-sm flex-1 truncate" :class="entry.name === auth.user?.name ? 'text-amber-400 font-bold' : 'text-zinc-300'">{{ entry.name }}</span>
                                <span v-if="entry.attempts === 1" class="hidden sm:inline text-[9px] font-black uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded-full border border-emerald-500/15 shrink-0">⚡ 1st</span>
                                <span class="text-xs font-mono text-zinc-500 tabular-nums shrink-0 w-12 text-right">{{ formatTime(entry.solve_time_seconds) }}</span>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center">
                            <p class="text-2xl mb-2 opacity-30">🏆</p>
                            <p class="text-zinc-600 text-sm">{{ $t('daily.no_solvers_yet') }}</p>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-white/5">
                            <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-zinc-500">📊 {{ $t('daily.history') }}</h3>
                        </div>
                        <div v-if="history.length" class="divide-y divide-white/5">
                            <div v-for="(entry, i) in history.slice(0, 10)" :key="i" class="flex items-center gap-2 sm:gap-3 px-4 sm:px-5 py-2 sm:py-2.5">
                                <span class="text-[11px] text-zinc-600 font-mono tabular-nums w-[5rem] shrink-0">{{ formatHistoryDate(entry.date) }}</span>
                                <span class="text-xs text-zinc-400 flex-1 truncate">{{ locale === 'lv' ? (entry.theme_lv || entry.theme) : entry.theme }}</span>
                                <span v-if="entry.solved" class="text-emerald-400 text-xs font-bold shrink-0">✓</span>
                                <span v-else class="text-red-400/50 text-xs shrink-0">✕</span>
                                <span class="text-[11px] font-mono text-zinc-600 tabular-nums w-10 text-right shrink-0">{{ entry.time != null ? formatTime(entry.time) : '' }}</span>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center">
                            <p class="text-2xl mb-2 opacity-30">📊</p>
                            <p class="text-zinc-600 text-sm">{{ $t('daily.no_history') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
