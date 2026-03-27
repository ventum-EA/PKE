<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useGamesStore } from '../stores/games';
import { useNotification } from '../composables/useNotification';
import { useStockfish } from '../services/stockfish';
import { parsePgn, classifyEvalDiff, categorizeError, generateExplanation } from '../services/chess';
import api from '../services/api';
import ChessBoard from './ChessBoard.vue';

const props = defineProps({ gameId: Number });
const emit = defineEmits(['close']);
const gamesStore = useGamesStore();
const { notify } = useNotification();
const engine = useStockfish();

const game = ref(null);
const parsedMoves = ref([]);
const analyzedMoves = ref([]);
const isAnalyzing = ref(false);
const analysisProgress = ref(0);
const currentMoveIndex = ref(-1); // -1 = starting position
const boardFen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
const analysisDepth = ref(15);
const engineReady = ref(false);

const classColors = {
    best: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
    excellent: 'text-teal-400 bg-teal-500/10 border-teal-500/20',
    good: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
    inaccuracy: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20',
    mistake: 'text-orange-400 bg-orange-500/10 border-orange-500/20',
    blunder: 'text-red-400 bg-red-500/10 border-red-500/20',
};
const classLabels = {
    best: 'Labākais', excellent: 'Lielisks', good: 'Labs',
    inaccuracy: 'Neprecizitāte', mistake: 'Kļūda', blunder: 'Rupja kļūda',
};
const categoryLabels = {
    tactical: '⚔ Taktiska', positional: '◈ Pozicionāla',
    opening: '♟ Atklātne', endgame: '♔ Galotne',
};

const currentMove = computed(() => {
    if (currentMoveIndex.value < 0 || currentMoveIndex.value >= analyzedMoves.value.length) return null;
    return analyzedMoves.value[currentMoveIndex.value];
});

const lastMove = computed(() => {
    if (currentMoveIndex.value < 0) return null;
    const m = parsedMoves.value[currentMoveIndex.value];
    return m ? { from: m.from, to: m.to } : null;
});

const errorSquares = computed(() => {
    const m = currentMove.value;
    if (!m || !m.classification || ['best', 'excellent', 'good'].includes(m.classification)) return [];
    const pm = parsedMoves.value[currentMoveIndex.value];
    return pm ? [pm.from, pm.to] : [];
});

const errors = computed(() => analyzedMoves.value.filter(m =>
    ['inaccuracy', 'mistake', 'blunder'].includes(m.classification)));

const evalBar = computed(() => {
    const m = currentMove.value;
    if (!m) return 50;
    const ev = m.evalAfter ?? 0;
    // Convert eval to percentage (sigmoid-like)
    return Math.min(95, Math.max(5, 50 + (ev * 10)));
});

onMounted(async () => {
    game.value = await gamesStore.fetchGame(props.gameId);
    if (game.value?.pgn) {
        const parsed = parsePgn(game.value.pgn);
        parsedMoves.value = parsed.moves;
    }
    // Check if already analyzed on server
    if (game.value?.is_analyzed) {
        try {
            const serverMoves = await gamesStore.fetchMoves(props.gameId);
            if (serverMoves.length > 0) {
                analyzedMoves.value = serverMoves.map((m, i) => ({
                    ...m,
                    evalBefore: m.eval_before,
                    evalAfter: m.eval_after,
                    evalDiff: m.eval_diff,
                }));
            }
        } catch {}
    }

    try {
        await engine.init();
        engineReady.value = true;
    } catch {
        console.warn('Stockfish WASM could not load');
    }
});

onUnmounted(() => {
    engine.stop();
});

async function runAnalysis() {
    if (!parsedMoves.value.length) return;
    isAnalyzing.value = true;
    analysisProgress.value = 0;
    analyzedMoves.value = [];

    const fens = parsedMoves.value.map(m => m.fen_before);
    // Add final position
    fens.push(parsedMoves.value[parsedMoves.value.length - 1].fen_after);

    const evals = [];
    const depth = analysisDepth.value;

    // Analyze each position
    for (let i = 0; i < fens.length; i++) {
        try {
            const result = await engine.analyze(fens[i], depth);
            evals.push(result.eval);
        } catch {
            evals.push(0);
        }
        analysisProgress.value = Math.round(((i + 1) / fens.length) * 100);
    }

    // Build analyzed moves
    for (let i = 0; i < parsedMoves.value.length; i++) {
        const m = parsedMoves.value[i];
        const evalBefore = evals[i] ?? 0;
        const evalAfter = evals[i + 1] ?? 0;

        const classification = classifyEvalDiff(evalBefore, evalAfter, m.color);
        const category = ['inaccuracy', 'mistake', 'blunder'].includes(classification)
            ? categorizeError(i, parsedMoves.value.length, m)
            : null;

        // Get best move for this position
        let bestMove = m.san;
        try {
            const best = await engine.analyze(m.fen_before, Math.min(depth, 12));
            if (best.pv && best.pv.length > 0) bestMove = best.pv[0];
        } catch {}

        const explanation = generateExplanation(classification, category, m.san, bestMove);

        analyzedMoves.value.push({
            ...m,
            evalBefore: Math.round(evalBefore * 100) / 100,
            evalAfter: Math.round(evalAfter * 100) / 100,
            evalDiff: Math.round(Math.abs(evalAfter - evalBefore) * 100) / 100,
            classification,
            error_category: category,
            explanation,
            bestMove,
            move_san: m.san,
            move_number: m.moveNumber,
            color: m.color,
        });
    }

    isAnalyzing.value = false;
    // Save WASM analysis results to server for persistence
    try {
        const movesToSave = analyzedMoves.value.map(m => ({
            move_number: m.move_number,
            color: m.color,
            move_san: m.move_san,
            fen_before: m.fen_before,
            fen_after: m.fen_after,
            eval_before: m.evalBefore,
            eval_after: m.evalAfter,
            eval_diff: m.evalDiff,
            best_move: m.bestMove,
            classification: m.classification,
            error_category: m.error_category,
            explanation: m.explanation,
        }));
        await gamesStore.saveMoves(props.gameId, movesToSave);
    } catch (e) {
        console.warn('Could not save analysis to server:', e);
    }

    notify('Analīze pabeigta!', 'success');
}

function goToMove(index) {
    currentMoveIndex.value = index;
    if (index < 0) {
        boardFen.value = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    } else if (parsedMoves.value[index]) {
        boardFen.value = parsedMoves.value[index].fen_after;
    }
}

function goToStart() { goToMove(-1); }
function goBack() { goToMove(Math.max(-1, currentMoveIndex.value - 1)); }
function goForward() { goToMove(Math.min(parsedMoves.value.length - 1, currentMoveIndex.value + 1)); }
function goToEnd() { goToMove(parsedMoves.value.length - 1); }

// Keyboard navigation
function handleKeydown(e) {
    if (e.key === 'ArrowLeft') goBack();
    if (e.key === 'ArrowRight') goForward();
    if (e.key === 'Home') goToStart();
    if (e.key === 'End') goToEnd();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

async function handleShare() {
    try {
        const url = await gamesStore.shareGame(props.gameId);
        navigator.clipboard?.writeText(url);
        notify('Saite nokopēta!', 'success');
    } catch { notify('Neizdevās izveidot saiti', 'error'); }
}

async function requestServerAnalysis() {
    try {
        await api.post(`/game/${props.gameId}/analyze`, { depth: 20, server: true });
        notify('Dziļā analīze ieplānota. Rezultāti parādīsies pēc apstrādes.', 'info');
    } catch {
        notify('Neizdevās ieplānot servera analīzi', 'error');
    }
}

async function generateTraining() {
    try {
        const { data } = await api.post(`/training/generate/${props.gameId}`);
        const count = data.puzzles?.length || 0;
        if (count > 0) {
            notify(`${count} treniņu uzdevumi ģenerēti! Dodieties uz Treniņi sadaļu.`, 'success');
        } else {
            notify('Nav kļūdu, no kurām ģenerēt uzdevumus.', 'info');
        }
    } catch {
        notify('Neizdevās ģenerēt treniņus', 'error');
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm overflow-y-auto p-4" @click.self="emit('close')">
        <div class="max-w-6xl mx-auto my-4 bg-zinc-900 border border-white/10 rounded-3xl shadow-2xl">
            <!-- Header -->
            <div class="p-5 border-b border-white/5 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-lg font-black text-white">
                        {{ game?.white_player || '?' }} vs {{ game?.black_player || '?' }}
                    </h2>
                    <p class="text-xs text-zinc-500 mt-1">
                        {{ game?.opening_name }} · {{ game?.result }} · {{ parsedMoves.length }} gājieni
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="handleShare" class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 transition-all">🔗</button>
                    <button @click="emit('close')" class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all">✕</button>
                </div>
            </div>

            <div class="p-5 flex flex-col lg:flex-row gap-6">
                <!-- Left: Board + Controls -->
                <div class="flex-shrink-0">
                    <ChessBoard
                        :fen="boardFen"
                        :orientation="game?.user_color || 'white'"
                        :interactive="false"
                        :lastMove="lastMove"
                        :highlightSquares="errorSquares"
                        :highlightColor="currentMove?.classification === 'blunder' ? 'rgba(235,68,68,0.45)' : currentMove?.classification === 'mistake' ? 'rgba(245,158,11,0.4)' : 'rgba(250,204,21,0.3)'"
                        :size="380"
                    />

                    <!-- Eval bar -->
                    <div class="mt-3 h-4 bg-zinc-800 rounded-full overflow-hidden relative">
                        <div class="h-full bg-white transition-all duration-300 rounded-full"
                            :style="{ width: evalBar + '%' }"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold"
                            :class="evalBar > 50 ? 'text-zinc-800' : 'text-zinc-300'">
                            {{ currentMove ? (currentMove.evalAfter > 0 ? '+' : '') + currentMove.evalAfter : '0.00' }}
                        </span>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <button @click="goToStart" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏮</button>
                        <button @click="goBack" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">◀</button>
                        <span class="text-xs text-zinc-500 min-w-[60px] text-center">
                            {{ currentMoveIndex + 1 }} / {{ parsedMoves.length }}
                        </span>
                        <button @click="goForward" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">▶</button>
                        <button @click="goToEnd" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏭</button>
                    </div>

                    <!-- Current move explanation -->
                    <div v-if="currentMove?.explanation" class="mt-3 p-3 bg-black/30 rounded-xl border border-white/5">
                        <div class="flex items-center gap-2 mb-1">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase border', classColors[currentMove.classification]]">
                                {{ classLabels[currentMove.classification] }}
                            </span>
                            <span v-if="currentMove.error_category" class="text-[10px] text-zinc-600">
                                {{ categoryLabels[currentMove.error_category] }}
                            </span>
                        </div>
                        <p class="text-sm text-zinc-400 mt-1">{{ currentMove.explanation }}</p>
                    </div>
                </div>

                <!-- Right: Analysis panel -->
                <div class="flex-1 min-w-0">
                    <!-- Not analyzed yet -->
                    <div v-if="analyzedMoves.length === 0 && !isAnalyzing" class="text-center py-8">
                        <p class="text-3xl mb-3">🔍</p>
                        <h3 class="text-base font-bold text-white mb-2">Sākt Stockfish analīzi</h3>
                        <p class="text-zinc-500 text-sm mb-4">Dzinējs analizēs katru gājienu un atradīs kļūdas</p>
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <label class="text-xs text-zinc-500">Dziļums:</label>
                            <input type="range" v-model.number="analysisDepth" min="8" max="22" class="w-32">
                            <span class="text-sm text-amber-400 font-bold w-6">{{ analysisDepth }}</span>
                        </div>
                        <button @click="runAnalysis" :disabled="!engineReady"
                            class="px-8 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm disabled:opacity-40">
                            {{ engineReady ? '⚡ Analizēt ar Stockfish' : 'Ielādē dzinēju...' }}
                        </button>
                    </div>

                    <!-- Analyzing progress -->
                    <div v-else-if="isAnalyzing" class="text-center py-8">
                        <div class="w-14 h-14 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-zinc-400 font-bold mb-2">Stockfish analizē...</p>
                        <div class="w-64 mx-auto bg-zinc-800 rounded-full h-2 mb-1">
                            <div class="bg-amber-400 h-2 rounded-full transition-all" :style="{ width: analysisProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-zinc-600">{{ analysisProgress }}% · Dziļums {{ analysisDepth }}</p>
                    </div>

                    <!-- Analysis results -->
                    <div v-else>
                        <!-- Summary -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-red-500/5 border border-red-500/10 rounded-xl p-3 text-center">
                                <p class="text-xl font-black text-red-400">{{ errors.filter(e => e.classification === 'blunder').length }}</p>
                                <p class="text-[10px] font-bold uppercase text-zinc-500 mt-1">Rupjas kļūdas</p>
                            </div>
                            <div class="bg-orange-500/5 border border-orange-500/10 rounded-xl p-3 text-center">
                                <p class="text-xl font-black text-orange-400">{{ errors.filter(e => e.classification === 'mistake').length }}</p>
                                <p class="text-[10px] font-bold uppercase text-zinc-500 mt-1">Kļūdas</p>
                            </div>
                            <div class="bg-yellow-500/5 border border-yellow-500/10 rounded-xl p-3 text-center">
                                <p class="text-xl font-black text-yellow-400">{{ errors.filter(e => e.classification === 'inaccuracy').length }}</p>
                                <p class="text-[10px] font-bold uppercase text-zinc-500 mt-1">Neprecizitātes</p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2 mb-4">
                            <button @click="requestServerAnalysis"
                                class="flex-1 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-blue-400 hover:border-blue-500/20 transition-all text-center">
                                🖥 Servera analīze (dziļāka)
                            </button>
                            <button @click="generateTraining"
                                class="flex-1 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-emerald-400 hover:border-emerald-500/20 transition-all text-center">
                                🎯 Ģenerēt treniņus
                            </button>
                        </div>

                        <!-- Move list (scrollable) -->
                        <div class="max-h-[420px] overflow-y-auto pr-1 space-y-0.5">
                            <div v-for="(move, i) in analyzedMoves" :key="i"
                                @click="goToMove(i)"
                                :class="['flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-all text-xs',
                                    currentMoveIndex === i ? 'bg-amber-500/10 border border-amber-500/20' :
                                    ['inaccuracy','mistake','blunder'].includes(move.classification)
                                        ? 'border border-transparent hover:border-white/5 ' + classColors[move.classification].split(' ')[0]
                                        : 'text-zinc-500 hover:bg-white/[0.02]']">
                                <span class="font-mono text-zinc-600 w-8 text-right">{{ move.move_number }}.{{ move.color === 'black' ? '..' : '' }}</span>
                                <span class="font-bold text-white w-14">{{ move.move_san }}</span>
                                <span v-if="move.classification && !['best','excellent','good'].includes(move.classification)"
                                    :class="['px-1.5 py-0.5 rounded text-[9px] font-black uppercase border', classColors[move.classification]]">
                                    {{ classLabels[move.classification]?.substring(0, 5) }}
                                </span>
                                <span v-else class="text-zinc-700 text-[10px]">{{ move.classification }}</span>
                                <span class="ml-auto font-mono text-zinc-600">
                                    {{ move.evalAfter > 0 ? '+' : '' }}{{ move.evalAfter }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
