<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';
import { createGame } from '../services/chess';
import ChessBoard from '../components/ChessBoard.vue';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import { useLocalized } from '../composables/useLocalized';

const { t } = useI18n();
// `localized` returns the right language variant for any `_lv`-suffixed field.
// Works for English speakers too — falls back gracefully when a translation
// is missing, instead of always showing Latvian.
const localized = useLocalized();

const allOpenings = ref([]);
const categories = ref({});
const isLoading = ref(true);
const searchQuery = ref('');
const selectedCategory = ref('B');
const selectedOpening = ref(null);
const boardFen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
const currentMoveIndex = ref(-1);
const theoryMoves = ref([]);
const lastMove = ref(null);
const practiceMode = ref(false);
const practiceColor = ref('white');
const practiceChess = ref(null);
const practiceFeedback = ref(null);
const { boardSize } = useResponsiveBoard({ maxSize: 420, padding: 48 });

onMounted(async () => {
    try {
        const { data } = await api.get('/openings');
        allOpenings.value = data.openings;
        categories.value = data.categories;
    } catch (e) { console.error('Failed to load openings:', e); }
    isLoading.value = false;
});

const filteredOpenings = computed(() => {
    const list = allOpenings.value.filter(o => o.category === selectedCategory.value);
    if (!searchQuery.value) return list;
    const q = searchQuery.value.toLowerCase();
    return list.filter(o =>
        localized(o, 'name').toLowerCase().includes(q) ||
        o.eco.toLowerCase().includes(q)
    );
});

const currentMoveExplanation = computed(() => {
    if (!selectedOpening.value || currentMoveIndex.value < 0) return null;
    return localized(selectedOpening.value, 'move_explanations')?.[currentMoveIndex.value] || null;
});

function selectOpening(opening) {
    selectedOpening.value = opening;
    practiceMode.value = false;
    practiceFeedback.value = null;
    const moves = opening.moves.split(' ');
    const chess = createGame();
    const parsed = [];
    for (const san of moves) {
        try {
            const result = chess.move(san, { sloppy: true });
            if (result) parsed.push({ san: result.san, from: result.from, to: result.to, fen_after: chess.fen() });
        } catch { break; }
    }
    theoryMoves.value = parsed;
    currentMoveIndex.value = -1;
    boardFen.value = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    lastMove.value = null;
}

function goToMove(i) {
    currentMoveIndex.value = i;
    if (i < 0) { boardFen.value = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'; lastMove.value = null; }
    else if (theoryMoves.value[i]) { boardFen.value = theoryMoves.value[i].fen_after; lastMove.value = { from: theoryMoves.value[i].from, to: theoryMoves.value[i].to }; }
}

function playThrough() {
    goToMove(-1); let i = 0;
    const iv = setInterval(() => { if (i >= theoryMoves.value.length) { clearInterval(iv); return; } goToMove(i); i++; }, 800);
}

function startPractice(color) {
    practiceColor.value = color; practiceMode.value = true; practiceFeedback.value = null;
    goToMove(-1); practiceChess.value = createGame();
    if (color === 'black' && theoryMoves.value.length > 0) setTimeout(() => autoPlay(), 500);
}

function autoPlay() {
    const ni = currentMoveIndex.value + 1;
    if (ni >= theoryMoves.value.length) { practiceFeedback.value = { type: 'success', text: t('openings.opening_complete') }; trackPractice(); return; }
    const isOpp = (practiceColor.value === 'white' && ni % 2 === 1) || (practiceColor.value === 'black' && ni % 2 === 0);
    if (isOpp) {
        const e = theoryMoves.value[ni];
        try { practiceChess.value.move(e.san); } catch { return; }
        boardFen.value = practiceChess.value.fen(); lastMove.value = { from: e.from, to: e.to }; currentMoveIndex.value = ni;
        if (ni + 1 >= theoryMoves.value.length) { practiceFeedback.value = { type: 'success', text: t('openings.opening_complete') }; trackPractice(); }
    }
}

function handlePracticeMove(move) {
    if (!practiceMode.value) return;
    const ni = currentMoveIndex.value + 1;
    if (ni >= theoryMoves.value.length) return;
    const e = theoryMoves.value[ni];
    if (move.from + move.to === e.from + e.to) {
        try { practiceChess.value.move(e.san); } catch { return; }
        boardFen.value = practiceChess.value.fen(); lastMove.value = { from: move.from, to: move.to }; currentMoveIndex.value = ni;
        practiceFeedback.value = { type: 'success', text: t('openings.correct', { move: e.san }) };
        setTimeout(() => { practiceFeedback.value = null; autoPlay(); }, 600);
    } else {
        practiceFeedback.value = { type: 'error', text: t('openings.incorrect', { move: e.san }) };
    }
}

async function trackPractice() {
    if (!selectedOpening.value?.id) return;
    try { await api.post(`/openings/${selectedOpening.value.id}/progress`, { color: practiceColor.value, completed: true }); } catch {}
}

const isUserTurn = computed(() => {
    if (!practiceMode.value) return false;
    const ni = currentMoveIndex.value + 1;
    if (ni >= theoryMoves.value.length) return false;
    return (practiceColor.value === 'white' && ni % 2 === 0) || (practiceColor.value === 'black' && ni % 2 === 1);
});
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white" data-tutorial="openings">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">📖</span> {{ $t('nav.openings') }}</h1>
                <p class="text-zinc-500 text-sm mt-1">{{ $t('openings.subtitle') }}</p>
            </div>

            <div v-if="isLoading" class="space-y-4" aria-busy="true">
                <div class="flex gap-2 mb-4">
                    <div v-for="i in 5" :key="i" class="h-9 w-14 bg-zinc-800/40 rounded-xl animate-pulse"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="i in 6" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 space-y-2">
                        <div class="h-4 w-16 bg-zinc-800/40 rounded animate-pulse"></div>
                        <div class="h-5 w-40 bg-zinc-800/30 rounded animate-pulse"></div>
                        <div class="h-3 w-full bg-zinc-800/20 rounded animate-pulse"></div>
                    </div>
                </div>
            </div>

            <div v-else class="flex flex-col lg:flex-row gap-8">
                <div class="w-full lg:w-72 xl:w-80 flex-shrink-0">
                    <div class="flex gap-1 mb-4">
                        <button v-for="(cat, letter) in categories" :key="letter" @click="selectedCategory = letter"
                            :class="['px-3 py-2 rounded-xl text-sm font-bold transition-all', selectedCategory === letter ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'text-zinc-500 hover:text-zinc-300 border border-white/5']">{{ letter }}</button>
                    </div>
                    <p class="text-xs text-zinc-600 mb-3">{{ categories[selectedCategory]?.desc }}</p>
                    <input v-model="searchQuery" type="text" :placeholder="$t('openings.search')" :aria-label="$t('openings.search')" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 mb-4" />
                    <div class="max-h-[500px] overflow-y-auto space-y-1 pr-1">
                        <div v-for="o in filteredOpenings" :key="o.id" @click="selectOpening(o)"
                            :class="['p-3 rounded-xl cursor-pointer border transition-all', selectedOpening?.id === o.id ? 'bg-amber-500/10 border-amber-500/20' : 'bg-zinc-900/30 border-white/5 hover:border-white/10']">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono text-amber-400/70 w-8">{{ o.eco }}</span>
                                <span class="text-sm text-zinc-300 font-semibold">{{ localized(o, 'name') }}</span>
                            </div>
                            <span v-if="o.user_practiced" class="text-[9px] text-emerald-500 ml-10">{{ $t('openings.practiced') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1">
                    <div v-if="selectedOpening">
                        <div class="mb-4">
                            <h2 class="text-xl font-black"><span class="text-amber-400 font-mono mr-2">{{ selectedOpening.eco }}</span>{{ localized(selectedOpening, 'name') }}</h2>
                            <p class="text-xs text-zinc-500 mt-1 font-mono">{{ selectedOpening.moves }}</p>
                        </div>
                        <div v-if="localized(selectedOpening, 'summary') && !practiceMode" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 mb-6">
                            <p class="text-sm text-zinc-300 leading-relaxed mb-4">{{ localized(selectedOpening, 'summary') }}</p>
                            <div v-if="localized(selectedOpening, 'ideas')?.length">
                                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">{{ $t('openings.main_ideas') }}</p>
                                <p v-for="(idea, i) in localized(selectedOpening, 'ideas')" :key="i" class="text-xs text-zinc-400 pl-3 border-l-2 border-amber-500/20 mb-1.5">{{ idea }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="flex-shrink-0">
                                <ChessBoard
                                    :fen="boardFen"
                                    :orientation="practiceMode ? practiceColor : 'white'"
                                    :interactive="practiceMode && isUserTurn"
                                    :lastMove="lastMove"
                                    :size="boardSize"
                                    
                                    @move="handlePracticeMove"
                                />
                                <div v-if="practiceFeedback" :class="['mt-3 p-3 rounded-xl text-sm font-bold text-center', practiceFeedback.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20']">{{ practiceFeedback.text }}</div>
                                <div v-if="!practiceMode" class="flex items-center justify-center gap-2 mt-3">
                                    <button @click="goToMove(-1)" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏮</button>
                                    <button @click="goToMove(Math.max(-1, currentMoveIndex - 1))" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">◀</button>
                                    <span class="text-xs text-zinc-500 min-w-[50px] text-center">{{ currentMoveIndex + 1 }} / {{ theoryMoves.length }}</span>
                                    <button @click="goToMove(Math.min(theoryMoves.length - 1, currentMoveIndex + 1))" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">▶</button>
                                    <button @click="goToMove(theoryMoves.length - 1)" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏭</button>
                                </div>
                                <div v-if="currentMoveExplanation && !practiceMode" class="mt-3 p-3 bg-amber-500/5 border border-amber-500/10 rounded-xl">
                                    <p class="text-xs text-amber-400 font-bold mb-1">{{ currentMoveExplanation.move }}</p>
                                    <p class="text-sm text-zinc-300">{{ currentMoveExplanation.text }}</p>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 mb-4">
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">{{ $t('openings.theory_moves') }}</h3>
                                    <div class="flex flex-wrap gap-1">
                                        <template v-for="(m, i) in theoryMoves" :key="i">
                                            <span v-if="i % 2 === 0" class="text-xs text-zinc-600 font-mono mr-0.5">{{ Math.floor(i/2)+1 }}.</span>
                                            <span @click="!practiceMode && goToMove(i)" :class="['text-sm font-bold mr-1', currentMoveIndex === i ? 'text-amber-400' : !practiceMode ? 'text-zinc-400 cursor-pointer hover:text-white' : 'text-zinc-400']">{{ m.san }}</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <button v-if="!practiceMode" @click="playThrough" class="w-full py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 hover:border-amber-500/20 transition-all">{{ $t('openings.play_through') }}</button>
                                    <div v-if="!practiceMode" class="grid grid-cols-2 gap-2">
                                        <button @click="startPractice('white')" class="py-2.5 text-sm font-bold rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 hover:bg-amber-500/20">{{ $t('openings.practice_white') }}</button>
                                        <button @click="startPractice('black')" class="py-2.5 text-sm font-bold rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 hover:bg-amber-500/20">{{ $t('openings.practice_black') }}</button>
                                    </div>
                                    <button v-if="practiceMode" @click="practiceMode = false; goToMove(-1); practiceFeedback = null" class="w-full py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white">{{ $t('openings.back_to_theory') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-20 bg-zinc-900/30 rounded-3xl border border-dashed border-zinc-800">
                        <p class="text-4xl mb-4">📖</p>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $t('openings.select_opening') }}</h3>
                        <p class="text-zinc-500 text-sm">{{ $t('openings.select_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
