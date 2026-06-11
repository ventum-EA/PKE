<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';
import { useNotification } from '../composables/useNotification';
import ChessBoard from '../components/ChessBoard.vue';
import { Chess } from 'chess.js';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';

const { notify } = useNotification();
const { t } = useI18n();
const { boardSize } = useResponsiveBoard({ maxSize: 420, padding: 48 });
const allLessons = ref([]);
const categoriesMeta = ref({});
const isLoading = ref(true);
const selectedCategoryKey = ref(null);
const selectedLesson = ref(null);
const currentView = ref('categories');

const currentPuzzleIdx = ref(0);
const puzzleResult = ref(null);
const attempts = ref(0);
const showSolution = ref(false);
const showHint = ref(false);
const lessonCompleted = ref(false);
const solvedCount = ref(0);
const boardFen = ref(null);

// Guided path: track completed lessons in localStorage
const completedLessons = ref(new Set(JSON.parse(localStorage.getItem('pke-completed-lessons') || '[]')));
function markCompleted(slug) {
    completedLessons.value.add(slug);
    localStorage.setItem('pke-completed-lessons', JSON.stringify([...completedLessons.value]));
}
function isCompleted(slug) { return completedLessons.value.has(slug); }

// Normalize theory text: the seeder stores \n as literal two-char sequences
// (single-quoted PHP strings), so convert them to real newlines before splitting.
function normalizeTheory(text) { return (text || '').replace(/\\n/g, '\n'); }

// Recommended category order for beginners
const CATEGORY_ORDER = ['basics', 'tactics', 'strategy', 'openings', 'endgame', 'checkmate_patterns'];

// Next recommended lesson across all categories
const nextLesson = computed(() => {
    if (!allLessons.value?.length) return null;
    for (const cat of CATEGORY_ORDER) {
        const catLessons = allLessons.value.filter(l => l.category === cat).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        const next = catLessons.find(l => !isCompleted(l.slug));
        if (next) return { ...next, categoryName: categoriesMeta.value[cat]?.title || cat };
    }
    return null;
});

// Progress per category
const categoryProgress = computed(() => {
    const progress = {};
    if (!allLessons.value?.length) return progress;
    for (const cat of CATEGORY_ORDER) {
        const catLessons = allLessons.value.filter(l => l.category === cat);
        const done = catLessons.filter(l => isCompleted(l.slug)).length;
        progress[cat] = { done, total: catLessons.length, pct: catLessons.length ? Math.round(done / catLessons.length * 100) : 0 };
    }
    return progress;
});

onMounted(async () => {
    try {
        const { data } = await api.get('/lessons');
        allLessons.value = data.lessons;
        categoriesMeta.value = data.categories;
    } catch (e) { console.error('Failed to load lessons:', e); }
    isLoading.value = false;
});

const categories = computed(() => {
    return Object.entries(categoriesMeta.value).map(([key, meta]) => ({ key, ...meta }));
});
const categoryLessons = computed(() => allLessons.value.filter(l => l.category === selectedCategoryKey.value));
const currentPuzzle = computed(() => selectedLesson.value?.puzzles?.[currentPuzzleIdx.value] || null);
const totalPuzzles = computed(() => selectedLesson.value?.puzzles?.length || 0);

const diffLabel = (d) => ({ 1: t('lessons.diff_1'), 2: t('lessons.diff_2'), 3: t('lessons.diff_3') }[d] || '?');
const diffColor = (d) => ({ 1: 'text-emerald-400', 2: 'text-amber-400', 3: 'text-red-400' }[d] || 'text-zinc-400');

function openCategory(key) { selectedCategoryKey.value = key; currentView.value = 'lessons'; }

async function openLesson(id) {
    try {
        const { data } = await api.get(`/lessons/${id}`);
        selectedLesson.value = data.lesson;
        currentView.value = 'lesson';
        resetPuzzle();
    } catch { notify(t('lessons.load_error'), 'error'); }
}

async function startPuzzles() {
    // Fully initialize puzzle state BEFORE switching view to avoid
    // reactivity cascade that can freeze the ChessBoard mount.
    currentPuzzleIdx.value = 0;
    solvedCount.value = 0;
    lessonCompleted.value = false;
    puzzleResult.value = null;
    attempts.value = 0;
    showSolution.value = false;
    showHint.value = false;
    boardFen.value = selectedLesson.value?.puzzles?.[0]?.fen
        || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    await nextTick();
    currentView.value = 'puzzle';
}
function resetPuzzle() {
    puzzleResult.value = null; attempts.value = 0; showSolution.value = false; showHint.value = false;
    const puzzle = selectedLesson.value?.puzzles?.[currentPuzzleIdx.value];
    boardFen.value = puzzle?.fen || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
}

function handleMove(move) {
    if (!currentPuzzle.value || puzzleResult.value === 'correct') return;
    const userUci = move.from + move.to;
    const correct = currentPuzzle.value.correct_move;
    attempts.value++;
    if (userUci === correct || userUci === correct.substring(0, 4)) {
        // Apply the move to the board so the piece visually stays in its new position
        try {
            const c = new Chess(currentPuzzle.value.fen);
            const result = c.move({ from: move.from, to: move.to, promotion: move.promotion || 'q' });
            if (result) boardFen.value = c.fen();
        } catch { /* board stays as-is */ }
        puzzleResult.value = 'correct'; solvedCount.value++;
        notify(t('lessons.correct'), 'success');
    } else {
        puzzleResult.value = 'wrong';
        setTimeout(() => { if (puzzleResult.value === 'wrong') puzzleResult.value = null; }, 1200);
    }
}

async function nextPuzzle() {
    if (currentPuzzleIdx.value < totalPuzzles.value - 1) { currentPuzzleIdx.value++; resetPuzzle(); }
    else {
        lessonCompleted.value = true;
        if (selectedLesson.value?.slug) markCompleted(selectedLesson.value.slug);
        try {
            await api.post(`/lessons/${selectedLesson.value.id}/progress`, {
                puzzles_solved: solvedCount.value, puzzles_total: totalPuzzles.value,
            });
        } catch { /* intentionally silenced */ }
    }
}

function goBack() {
    if (currentView.value === 'puzzle') currentView.value = 'lesson';
    else if (currentView.value === 'lesson') currentView.value = 'lessons';
    else if (currentView.value === 'lessons') { currentView.value = 'categories'; selectedCategoryKey.value = null; }
}
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white" data-tutorial="lessons">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <button v-if="currentView !== 'categories'" @click="goBack" :aria-label="$t('common.back')" class="p-2 rounded-xl bg-zinc-800 text-zinc-400 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">←</button>
                <div>
                    <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">🎓</span>
                        {{ currentView === 'categories' ? t('lessons.title') : currentView === 'lessons' ? categoriesMeta[selectedCategoryKey]?.title : selectedLesson?.title_lv || t('lessons.lesson_label') }}
                    </h1>
                </div>
            </div>

            <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" aria-busy="true">
                <div v-for="i in 8" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 space-y-3">
                    <div class="w-10 h-10 bg-zinc-800/40 rounded-xl animate-pulse"></div>
                    <div class="h-4 w-28 bg-zinc-800/40 rounded animate-pulse"></div>
                    <div class="h-3 w-full bg-zinc-800/20 rounded animate-pulse"></div>
                    <div class="h-2 bg-zinc-800/30 rounded-full animate-pulse"></div>
                </div>
            </div>

            <!-- CATEGORIES -->
            <div v-else-if="currentView === 'categories'">
                <!-- Guided path: next lesson recommendation -->
                <div v-if="nextLesson" class="bg-amber-500/5 border border-amber-500/15 rounded-2xl p-5 mb-5 cursor-pointer hover:bg-amber-500/10 transition-all"
                    @click="openLesson(nextLesson.id)">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🎯</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-amber-400/60">{{ $t('lessons.recommended') || 'Ieteicamā nākamā nodarbība' }}</p>
                            <p class="text-sm font-bold text-amber-400 truncate">{{ nextLesson.title_lv }}</p>
                            <p class="text-xs text-zinc-500 truncate">{{ nextLesson.categoryName }} · {{ nextLesson.description_lv }}</p>
                        </div>
                        <span class="text-amber-400/40 text-xl shrink-0">→</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="cat in categories" :key="cat.key" @click="openCategory(cat.key)"
                    class="bg-zinc-900/50 border border-white/5 rounded-2xl p-6 cursor-pointer hover:border-amber-500/20 transition-all group">
                    <div class="flex items-start justify-between mb-3">
                        <span class="text-3xl">{{ cat.icon }}</span>
                        <div class="text-right">
                            <span class="text-xs font-bold text-zinc-500">{{ cat.lesson_count }} {{ $t('lessons.lessons_count') }}</span><br>
                            <span class="text-xs text-zinc-600">{{ cat.puzzle_count }} uzdevumi</span>
                        </div>
                    </div>
                    <h3 class="text-lg font-black text-white group-hover:text-amber-400 transition-colors">{{ cat.title }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">{{ cat.desc }}</p>
                    <!-- Progress bar -->
                    <div v-if="categoryProgress[cat.key]" class="mt-3">
                        <div class="flex items-center justify-between text-[10px] text-zinc-600 mb-1">
                            <span>{{ categoryProgress[cat.key].done }}/{{ categoryProgress[cat.key].total }}</span>
                            <span>{{ categoryProgress[cat.key].pct }}%</span>
                        </div>
                        <div class="h-1 bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full transition-all duration-500" :style="{ width: categoryProgress[cat.key].pct + '%' }"></div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- LESSONS LIST -->
            <div v-else-if="currentView === 'lessons'" class="space-y-4">
                <div v-for="lesson in categoryLessons" :key="lesson.id" @click="openLesson(lesson.id)"
                    :class="['bg-zinc-900/50 border rounded-2xl p-5 cursor-pointer hover:border-amber-500/20 transition-all group',
                        isCompleted(lesson.slug) ? 'border-emerald-500/15' : 'border-white/5']">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <span v-if="isCompleted(lesson.slug)" class="text-emerald-400 text-lg shrink-0">✓</span>
                            <span v-else class="text-zinc-600 text-lg shrink-0">○</span>
                            <div class="min-w-0">
                            <h3 class="text-base font-bold text-white group-hover:text-amber-400">{{ lesson.title_lv }}</h3>
                            <p class="text-sm text-zinc-500 mt-1">{{ lesson.description_lv }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <span :class="diffColor(lesson.difficulty)" class="text-xs font-bold uppercase">{{ diffLabel(lesson.difficulty) }}</span>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ lesson.puzzles_count }} uzdevumi</p>
                            <span v-if="lesson.user_completed" class="text-[9px] text-emerald-500">✓ {{ lesson.user_best_score }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LESSON THEORY -->
            <div v-else-if="currentView === 'lesson' && selectedLesson">
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-8 mb-6">
                    <span :class="diffColor(selectedLesson.difficulty)" class="text-xs font-bold uppercase px-3 py-1 rounded-full border border-current/20 bg-current/10 mb-4 inline-block">{{ diffLabel(selectedLesson.difficulty) }}</span>
                    <p class="text-sm text-zinc-400 mb-6">{{ selectedLesson.description_lv }}</p>
                    <div v-for="(para, i) in normalizeTheory(selectedLesson.theory_lv).split('\n\n')" :key="i" class="mb-4">
                        <template v-for="(line, j) in para.split('\n')" :key="j">
                            <p v-if="line.startsWith('-') || line.startsWith('•')" class="text-sm text-zinc-400 pl-4 mb-1"><span class="text-amber-400 mr-2">•</span>{{ line.replace(/^[-•]\s*/, '') }}</p>
                            <p v-else-if="line.match(/^\d+\./)" class="text-sm text-zinc-400 pl-4 mb-1"><span class="text-amber-400 mr-1">{{ line.match(/^\d+\./)[0] }}</span>{{ line.replace(/^\d+\./, '').trim() }}</p>
                            <p v-else class="text-sm text-zinc-300 leading-relaxed mb-1">{{ line }}</p>
                        </template>
                    </div>
                </div>
                <button @click="startPuzzles" class="w-full py-4 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-2xl uppercase tracking-wider text-sm hover:from-amber-400 hover:to-amber-500 active:scale-[0.98] transition-all shadow-lg shadow-amber-500/20">
                    🎯 {{ $t('lessons.start_puzzles') }} ({{ selectedLesson.puzzles?.length || 0 }})
                </button>
            </div>

            <!-- PUZZLE -->
            <div v-else-if="currentView === 'puzzle' && currentPuzzle && !lessonCompleted">
                <div class="flex items-center justify-center gap-2 mb-6">
                    <div v-for="(_, i) in totalPuzzles" :key="i" :class="['w-3 h-3 rounded-full', i < currentPuzzleIdx ? 'bg-emerald-400' : i === currentPuzzleIdx ? 'bg-amber-400 scale-125' : 'bg-zinc-700']"></div>
                </div>
                <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-center lg:items-start">
                    <div class="flex-shrink-0">
                        <ChessBoard :fen="boardFen" orientation="white" :interactive="puzzleResult !== 'correct' && !showSolution" :size="boardSize" @move="handleMove" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-black">{{ $t('lessons.puzzle_counter', { current: currentPuzzleIdx + 1, total: totalPuzzles }) }}</h3>
                                <span class="text-xs text-zinc-600">{{ $t('lessons.attempts_label', { count: attempts }) }}</span>
                            </div>
                            <p class="text-sm text-zinc-400 mb-5">{{ $t('lessons.find_best_move') }}</p>

                            <div v-if="currentPuzzle.hints_lv?.length && attempts >= 1 && !showSolution" class="mb-4">
                                <button v-if="!showHint" @click="showHint = true" class="text-xs text-amber-400/70 hover:text-amber-400 font-bold">💡 Rādīt padomu</button>
                                <div v-else class="bg-amber-500/5 border border-amber-500/10 rounded-xl p-3">
                                    <p class="text-sm text-amber-400/80">{{ currentPuzzle.hints_lv[Math.min(attempts - 1, currentPuzzle.hints_lv.length - 1)] }}</p>
                                </div>
                            </div>

                            <div v-if="puzzleResult === 'correct'" class="rounded-xl p-4 mb-4 bg-emerald-500/10 border border-emerald-500/20">
                                <p class="text-base font-black text-emerald-400 mb-2">✓ {{ $t('training.correct') }}</p>
                                <p class="text-sm text-zinc-400">{{ currentPuzzle.explanation_lv }}</p>
                            </div>
                            <div v-if="puzzleResult === 'wrong'" class="rounded-xl p-4 mb-4 bg-red-500/10 border border-red-500/20">
                                <p class="text-base font-black text-red-400">✕ {{ $t('training.incorrect') }}</p>
                            </div>
                            <div v-if="showSolution" class="rounded-xl p-4 mb-4 bg-amber-500/10 border border-amber-500/20">
                                <p class="text-sm font-bold text-amber-400 mb-1">Pareizais: <span class="font-mono text-lg">{{ currentPuzzle.correct_move }}</span></p>
                                <p class="text-sm text-zinc-400 mt-2">{{ currentPuzzle.explanation_lv }}</p>
                            </div>

                            <div class="space-y-2 mt-4">
                                <button v-if="puzzleResult === 'correct' || showSolution" @click="nextPuzzle"
                                    class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl uppercase text-sm">
                                    {{ currentPuzzleIdx < totalPuzzles - 1 ? 'Nākamais →' : 'Pabeigt nodarbību' }}
                                </button>
                                <button v-if="puzzleResult !== 'correct' && !showSolution && attempts >= 3" @click="showSolution = true"
                                    class="w-full py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400">👁 Parādīt atbildi</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMPLETED -->
            <div v-else-if="lessonCompleted" class="text-center py-16">
                <p class="text-6xl mb-6">🏆</p>
                <h2 class="text-2xl font-black mb-2">{{ $t('lessons.lesson_complete') }}</h2>
                <p class="text-zinc-500 mb-2">{{ selectedLesson?.title_lv }}</p>
                <p class="text-lg font-bold text-amber-400 mb-8">{{ solvedCount }} / {{ totalPuzzles }} atrisināti pareizi</p>
                <div class="flex items-center justify-center gap-4">
                    <button @click="startPuzzles" class="px-6 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl hover:bg-zinc-700">↻ Atkārtot</button>
                    <button @click="currentView = 'lessons'; lessonCompleted = false" class="px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-bold rounded-xl hover:from-amber-400 hover:to-amber-500 active:scale-95 transition-all">{{ $t('lessons.next_lesson') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>
