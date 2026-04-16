<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useStockfish } from '../services/stockfish';
import { createGame, makeMove as chessMove, detectOpening } from '../services/chess';
import { useGamesStore } from '../stores/games';
import { useNotification } from '../composables/useNotification';
import { onBeforeRouteLeave } from 'vue-router';
import ChessBoard from '../components/ChessBoard.vue';

const engine = useStockfish();
const gamesStore = useGamesStore();
const { notify } = useNotification();

const fen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
const chess = ref(null);
const playerColor = ref('white');
const skillLevel = ref(10);
const moveTime = ref(1500);
const engineThinking = ref(false);
const gameOver = ref(false);
const gameResult = ref('');
const moveHistory = ref([]);
const lastMove = ref(null);
const engineReady = ref(false);
const gameSaved = ref(false);
const gameInProgress = ref(false);

const STORAGE_KEY = 'chess_game_in_progress';

const skillLabels = {
    0: 'Iesācējs', 3: 'Vājš', 5: 'Vidējs', 8: 'Spēcīgs',
    10: 'Pieredzējis', 13: 'Eksperts', 16: 'Meistars', 20: 'Grandmeistars',
};

const currentSkillLabel = computed(() => {
    const keys = Object.keys(skillLabels).map(Number).sort((a, b) => a - b);
    let label = 'Iesācējs';
    for (const k of keys) {
        if (skillLevel.value >= k) label = skillLabels[k];
    }
    return label;
});

const isPlayerTurn = computed(() => {
    const turn = fen.value.split(' ')[1];
    return (turn === 'w' && playerColor.value === 'white') ||
           (turn === 'b' && playerColor.value === 'black');
});

const detectedOpening = computed(() => {
    if (moveHistory.value.length < 2) return null;
    return detectOpening(moveHistory.value);
});

// ---- Lifecycle ----
onMounted(async () => {
    try {
        await engine.init();
        engineReady.value = true;
    } catch {
        notify('Neizdevās ielādēt Stockfish dzinēju', 'error');
    }

    // Try restoring a saved game
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const state = JSON.parse(saved);
            playerColor.value = state.playerColor;
            skillLevel.value = state.skillLevel;
            moveTime.value = state.moveTime;
            moveHistory.value = state.moveHistory;
            fen.value = state.fen;
            lastMove.value = state.lastMove;
            chess.value = createGame(state.fen);
            gameInProgress.value = state.moveHistory.length > 0;
            gameSaved.value = false;

            // Check if game is actually over in this restored position
            if (chess.value.isGameOver()) {
                endGame({
                    isCheckmate: chess.value.isCheckmate(),
                    isStalemate: chess.value.isStalemate(),
                    isDraw: chess.value.isDraw(),
                    isGameOver: true,
                });
            } else if (!isPlayerTurn.value && engineReady.value) {
                // Engine's turn — make engine move
                makeEngineMove();
            }

            notify('Iepriekšējā spēle atjaunota', 'info');
            return;
        } catch {
            localStorage.removeItem(STORAGE_KEY);
        }
    }

    startNewGame();
});

onUnmounted(() => {
    engine.stop();
    // Auto-save on component unmount if game is in progress
    if (gameInProgress.value && !gameOver.value && !gameSaved.value) {
        saveToLocalStorage();
    }
});

// Auto-save to localStorage on every move
watch(moveHistory, () => {
    if (gameInProgress.value && !gameOver.value) {
        saveToLocalStorage();
    }
}, { deep: true });

// Warn before leaving if game in progress
onBeforeRouteLeave((to, from, next) => {
    if (gameInProgress.value && !gameOver.value && !gameSaved.value && moveHistory.value.length > 0) {
        const answer = window.confirm(
            'Jums ir nepabeigta partija. Tā tiks saglabāta un jūs varēsiet turpināt vēlāk.\nVai tiešām vēlaties aiziet?'
        );
        if (answer) {
            saveToLocalStorage();
            next();
        } else {
            next(false);
        }
    } else {
        next();
    }
});

// Also handle browser close / refresh
function handleBeforeUnload(e) {
    if (gameInProgress.value && !gameOver.value && moveHistory.value.length > 0) {
        saveToLocalStorage();
        e.preventDefault();
        e.returnValue = '';
    }
}
onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
onUnmounted(() => window.removeEventListener('beforeunload', handleBeforeUnload));

// ---- Game state persistence ----
function saveToLocalStorage() {
    const state = {
        playerColor: playerColor.value,
        skillLevel: skillLevel.value,
        moveTime: moveTime.value,
        moveHistory: moveHistory.value,
        fen: fen.value,
        lastMove: lastMove.value,
        savedAt: new Date().toISOString(),
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function clearLocalStorage() {
    localStorage.removeItem(STORAGE_KEY);
}

// ---- Game logic ----
function startNewGame() {
    // Warn if game in progress
    if (gameInProgress.value && !gameOver.value && moveHistory.value.length > 0) {
        if (!window.confirm('Nepabeigta partija tiks zaudēta. Turpināt?')) return;
    }

    chess.value = createGame();
    fen.value = chess.value.fen();
    moveHistory.value = [];
    lastMove.value = null;
    gameOver.value = false;
    gameResult.value = '';
    gameSaved.value = false;
    gameInProgress.value = false;
    clearLocalStorage();

    if (playerColor.value === 'black' && engineReady.value) {
        gameInProgress.value = true;
        makeEngineMove();
    }
}

async function handlePlayerMove(move) {
    if (!isPlayerTurn.value || gameOver.value || engineThinking.value) return;

    const result = chessMove(fen.value, move.from, move.to, move.promotion);
    if (!result) return;

    gameInProgress.value = true;
    fen.value = result.fen;
    chess.value = createGame(result.fen);
    lastMove.value = { from: move.from, to: move.to };
    moveHistory.value.push({
        san: result.san,
        fen: result.fen,
        color: playerColor.value,
        from: move.from,
        to: move.to,
    });

    if (result.isGameOver) {
        endGame(result);
        return;
    }

    await makeEngineMove();
}

async function makeEngineMove() {
    if (!engineReady.value || gameOver.value) return;
    engineThinking.value = true;

    try {
        const uciMove = await engine.getMove(fen.value, skillLevel.value, moveTime.value);
        if (!uciMove || uciMove === '(none)') {
            engineThinking.value = false;
            return;
        }

        const from = uciMove.substring(0, 2);
        const to = uciMove.substring(2, 4);
        const promotion = uciMove.length > 4 ? uciMove[4] : null;

        const result = chessMove(fen.value, from, to, promotion);
        if (!result) { engineThinking.value = false; return; }

        fen.value = result.fen;
        chess.value = createGame(result.fen);
        lastMove.value = { from, to };
        moveHistory.value.push({
            san: result.san,
            fen: result.fen,
            color: playerColor.value === 'white' ? 'black' : 'white',
            from, to,
        });

        if (result.isGameOver) endGame(result);
    } catch (err) {
        console.error('Engine error:', err);
    } finally {
        engineThinking.value = false;
    }
}

function endGame(result) {
    gameOver.value = true;
    clearLocalStorage();

    if (result.isCheckmate) {
        const winnerIsPlayer = !isPlayerTurn.value;
        gameResult.value = winnerIsPlayer ? 'Jūs uzvarējāt! ♔' : 'Stockfish uzvarēja. ♚';
    } else if (result.isStalemate) {
        gameResult.value = 'Pats — neizšķirts!';
    } else if (result.isDraw) {
        gameResult.value = 'Neizšķirts.';
    }
}

function resign() {
    if (!gameInProgress.value || gameOver.value) return;
    if (!window.confirm('Vai tiešām vēlaties padoties?')) return;

    gameOver.value = true;
    gameResult.value = 'Jūs padevāties. ♚';
    clearLocalStorage();
}

async function saveGame() {
    const pgn = moveHistory.value.map((m, i) => {
        if (i % 2 === 0) return `${Math.floor(i / 2) + 1}. ${m.san}`;
        return m.san;
    }).join(' ');

    let resultStr = '*';
    if (gameOver.value) {
        if (gameResult.value.includes('uzvarējāt'))
            resultStr = playerColor.value === 'white' ? '1-0' : '0-1';
        else if (gameResult.value.includes('uzvarēja') || gameResult.value.includes('padevāties'))
            resultStr = playerColor.value === 'white' ? '0-1' : '1-0';
        else resultStr = '1/2-1/2';
    }

    const opening = detectedOpening.value;

    try {
        await gamesStore.createGame({
            pgn,
            white_player: playerColor.value === 'white' ? 'Spēlētājs' : `Stockfish (${currentSkillLabel.value})`,
            black_player: playerColor.value === 'black' ? 'Spēlētājs' : `Stockfish (${currentSkillLabel.value})`,
            result: resultStr,
            user_color: playerColor.value,
            total_moves: moveHistory.value.length,
            opening_name: opening?.name || null,
            opening_eco: opening?.eco || null,
            played_at: new Date().toISOString().split('T')[0],
        });
        gameSaved.value = true;
        clearLocalStorage();
        notify('Partija saglabāta! Dodieties uz Partijas, lai analizētu.', 'success');
    } catch {
        notify('Neizdevās saglabāt partiju', 'error');
    }
}
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">♚</span> {{ $t('nav.play') }}</h1>
                <p class="text-zinc-500 text-sm mt-1">Reāllaika spēle pret šaha dzinēju (WASM)</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Board -->
                <div class="w-full lg:w-[min(60vw,85vh,700px)]">
                    <div class="flex items-center gap-2 mb-2 px-1">
                        <div class="w-3 h-3 rounded-full" :class="engineThinking ? 'bg-amber-400 animate-pulse' : 'bg-zinc-600'"></div>
                        <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">
                            Stockfish · {{ currentSkillLabel }}
                            <span v-if="engineThinking" class="text-amber-400 ml-1">domā...</span>
                        </span>
                    </div>

                    <ChessBoard
                        :fen="fen"
                        :orientation="playerColor"
                        :interactive="isPlayerTurn && !gameOver && !engineThinking"
                        :lastMove="lastMove"
                       
                        @move="handlePlayerMove"
                    />

                    <div class="flex items-center justify-between mt-2 px-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Jūs · {{ playerColor === 'white' ? 'Baltais' : 'Melnais' }}</span>
                        </div>
                        <span v-if="detectedOpening" class="text-[10px] text-zinc-600 font-mono">
                            {{ detectedOpening.eco }} {{ detectedOpening.name }}
                        </span>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="flex-1 min-w-0">
                    <!-- Game Over -->
                    <div v-if="gameOver" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-5 mb-6 text-center">
                        <p class="text-xl font-black text-amber-400 mb-2">{{ gameResult }}</p>
                        <p class="text-xs text-zinc-500 mb-4">{{ moveHistory.length }} gājieni · {{ detectedOpening?.name || '' }}</p>
                        <div class="flex items-center justify-center gap-3">
                            <button @click="startNewGame" class="px-5 py-2 bg-amber-500 text-black font-bold rounded-xl text-sm">Jauna partija</button>
                            <button v-if="!gameSaved" @click="saveGame" class="px-5 py-2 border border-amber-500/30 text-amber-400 font-bold rounded-xl text-sm">Saglabāt</button>
                            <span v-else class="text-xs text-emerald-400 font-bold">✓ Saglabāta</span>
                        </div>
                    </div>

                    <!-- In-game actions -->
                    <div v-if="gameInProgress && !gameOver" class="flex items-center gap-2 mb-4">
                        <button @click="saveGame"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-emerald-400 hover:border-emerald-500/20 transition-all text-center">
                            💾 Saglabāt pozīciju
                        </button>
                        <button @click="resign"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-red-400 hover:border-red-500/20 transition-all text-center">
                            🏳 Padoties
                        </button>
                    </div>

                    <!-- Settings -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 mb-6">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Iestatījumi</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 block">Jūsu krāsa</label>
                                <div class="flex gap-2">
                                    <button v-for="c in ['white', 'black']" :key="c"
                                        @click="playerColor = c; startNewGame()"
                                        :class="['px-4 py-2 rounded-lg text-sm font-bold border transition-all',
                                            playerColor === c ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'border-white/5 text-zinc-500']">
                                        {{ c === 'white' ? '♔ Baltais' : '♚ Melnais' }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 flex items-center justify-between">
                                    <span>Grūtības pakāpe</span>
                                    <span class="text-amber-400">{{ currentSkillLabel }} ({{ skillLevel }})</span>
                                </label>
                                <input type="range" v-model.number="skillLevel" min="0" max="20" class="w-full">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 flex items-center justify-between">
                                    <span>Domāšanas laiks</span>
                                    <span class="text-amber-400">{{ (moveTime / 1000).toFixed(1) }}s</span>
                                </label>
                                <input type="range" v-model.number="moveTime" min="500" max="5000" step="250" class="w-full">
                            </div>

                            <button @click="startNewGame"
                                class="w-full py-2.5 bg-zinc-800 text-zinc-300 font-bold rounded-xl hover:bg-zinc-700 transition-all text-sm">
                                ↻ Jauna partija
                            </button>
                        </div>
                    </div>

                    <!-- Move history -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">Gājieni ({{ moveHistory.length }})</h3>
                        <div class="max-h-60 overflow-y-auto">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(m, i) in moveHistory" :key="i">
                                    <span v-if="i % 2 === 0" class="text-xs text-zinc-600 font-mono mr-0.5">{{ Math.floor(i/2)+1 }}.</span>
                                    <span :class="['text-sm font-bold mr-1', m.color === playerColor ? 'text-amber-400' : 'text-zinc-400']">
                                        {{ m.san }}
                                    </span>
                                </template>
                            </div>
                            <p v-if="moveHistory.length === 0" class="text-zinc-600 text-xs">Veiciet pirmo gājienu...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
