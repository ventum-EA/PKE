<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import { createGame } from '../services/chess';
import ChessBoard from '../components/ChessBoard.vue';

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
    return list.filter(o => o.name_lv.toLowerCase().includes(q) || o.eco.toLowerCase().includes(q));
});

const currentMoveExplanation = computed(() => {
    if (!selectedOpening.value || currentMoveIndex.value < 0) return null;
    return selectedOpening.value.move_explanations_lv?.[currentMoveIndex.value] || null;
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
    if (ni >= theoryMoves.value.length) { practiceFeedback.value = { type: 'success', text: 'Atklātne pabeigta!' }; trackPractice(); return; }
    const isOpp = (practiceColor.value === 'white' && ni % 2 === 1) || (practiceColor.value === 'black' && ni % 2 === 0);
    if (isOpp) {
        const e = theoryMoves.value[ni];
        try { practiceChess.value.move(e.san); } catch { return; }
        boardFen.value = practiceChess.value.fen(); lastMove.value = { from: e.from, to: e.to }; currentMoveIndex.value = ni;
        if (ni + 1 >= theoryMoves.value.length) { practiceFeedback.value = { type: 'success', text: 'Atklātne pabeigta!' }; trackPractice(); }
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
        practiceFeedback.value = { type: 'success', text: `✓ Pareizi: ${e.san}` };
        setTimeout(() => { practiceFeedback.value = null; autoPlay(); }, 600);
    } else {
        practiceFeedback.value = { type: 'error', text: `✕ Nepareizi. Pareizais: ${e.san}` };
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
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">📖</span> Atklātņu pārlūks</h1>
                <p class="text-zinc-500 text-sm mt-1">Pārlūkojiet ECO atklātnes, skatiet teorijas līnijas un praktizējiet tās</p>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center py-20"><div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div></div>

            <div v-else class="flex flex-col lg:flex-row gap-8">
                <div class="w-full lg:w-80 flex-shrink-0">
                    <div class="flex gap-1 mb-4">
                        <button v-for="(cat, letter) in categories" :key="letter" @click="selectedCategory = letter"
                            :class="['px-3 py-2 rounded-xl text-sm font-bold transition-all', selectedCategory === letter ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'text-zinc-500 hover:text-zinc-300 border border-white/5']">{{ letter }}</button>
                    </div>
                    <p class="text-xs text-zinc-600 mb-3">{{ categories[selectedCategory]?.desc }}</p>
                    <input v-model="searchQuery" type="text" placeholder="Meklēt atklātni..." class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 mb-4" />
                    <div class="max-h-[500px] overflow-y-auto space-y-1 pr-1">
                        <div v-for="o in filteredOpenings" :key="o.id" @click="selectOpening(o)"
                            :class="['p-3 rounded-xl cursor-pointer border transition-all', selectedOpening?.id === o.id ? 'bg-amber-500/10 border-amber-500/20' : 'bg-zinc-900/30 border-white/5 hover:border-white/10']">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono text-amber-400/70 w-8">{{ o.eco }}</span>
                                <span class="text-sm text-zinc-300 font-semibold">{{ o.name_lv }}</span>
                            </div>
                            <span v-if="o.user_practiced" class="text-[9px] text-emerald-500 ml-10">✓ praktizēts</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1">
                    <div v-if="selectedOpening">
                        <div class="mb-4">
                            <h2 class="text-xl font-black"><span class="text-amber-400 font-mono mr-2">{{ selectedOpening.eco }}</span>{{ selectedOpening.name_lv }}</h2>
                            <p class="text-xs text-zinc-500 mt-1 font-mono">{{ selectedOpening.moves }}</p>
                        </div>
                        <div v-if="selectedOpening.summary_lv && !practiceMode" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 mb-6">
                            <p class="text-sm text-zinc-300 leading-relaxed mb-4">{{ selectedOpening.summary_lv }}</p>
                            <div v-if="selectedOpening.ideas_lv?.length">
                                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Galvenās idejas</p>
                                <p v-for="(idea, i) in selectedOpening.ideas_lv" :key="i" class="text-xs text-zinc-400 pl-3 border-l-2 border-amber-500/20 mb-1.5">{{ idea }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="flex-shrink-0">
                                <ChessBoard :fen="boardFen" :orientation="practiceMode ? practiceColor : 'white'" :interactive="practiceMode && isUserTurn" :lastMove="lastMove" :size="380" @move="handlePracticeMove" />
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
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">Teorijas gājieni</h3>
                                    <div class="flex flex-wrap gap-1">
                                        <template v-for="(m, i) in theoryMoves" :key="i">
                                            <span v-if="i % 2 === 0" class="text-xs text-zinc-600 font-mono mr-0.5">{{ Math.floor(i/2)+1 }}.</span>
                                            <span @click="!practiceMode && goToMove(i)" :class="['text-sm font-bold mr-1', currentMoveIndex === i ? 'text-amber-400' : !practiceMode ? 'text-zinc-400 cursor-pointer hover:text-white' : 'text-zinc-400']">{{ m.san }}</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <button v-if="!practiceMode" @click="playThrough" class="w-full py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 hover:border-amber-500/20 transition-all">▶ Atskaņot automātiski</button>
                                    <div v-if="!practiceMode" class="grid grid-cols-2 gap-2">
                                        <button @click="startPractice('white')" class="py-2.5 text-sm font-bold rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 hover:bg-amber-500/20">♔ Praktizēt kā baltais</button>
                                        <button @click="startPractice('black')" class="py-2.5 text-sm font-bold rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 hover:bg-amber-500/20">♚ Praktizēt kā melnais</button>
                                    </div>
                                    <button v-if="practiceMode" @click="practiceMode = false; goToMove(-1); practiceFeedback = null" class="w-full py-2.5 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white">← Atpakaļ uz teoriju</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-20 bg-zinc-900/30 rounded-3xl border border-dashed border-zinc-800">
                        <p class="text-4xl mb-4">📖</p>
                        <h3 class="text-lg font-bold text-white mb-2">Izvēlieties atklātni</h3>
                        <p class="text-zinc-500 text-sm">Kreisajā panelī atlasiet ECO kategoriju un atklātni.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
