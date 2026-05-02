<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { onBeforeRouteLeave } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useGamesStore } from '../stores/games';
import { useConfirm } from '../composables/useConfirm';
import { useNotification } from '../composables/useNotification';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import { useSounds } from '../composables/useSounds';
import { createGame, makeMove as chessMove, detectOpening } from '../services/chess';
import { useStockfish } from '../services/stockfish';
import ChessBoard from '../components/ChessBoard.vue';
import SandboxBoard from '../components/SandboxBoard.vue';

const { t } = useI18n();
const { playMoveSound, playGameStart, playGameEnd, playWin } = useSounds();
const engine = useStockfish();
const gamesStore = useGamesStore();
const auth = useAuthStore();
const { notify } = useNotification();
const { confirm } = useConfirm();
const { boardSize } = useResponsiveBoard({ maxSize: 480, padding: 48 });

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
const gameSaving = ref(false);
const gameInProgress = ref(false);
const eloChange = ref(null);
const sandboxes = ref([]);
const gameResultCode = ref(null); // 'player_win' | 'player_loss' | 'draw' | 'resign' | null

function spawnSandbox() {
    sandboxes.value.push({
        id: `sb-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        fen: fen.value,
    });
}
function removeSandbox(sbId) {
    sandboxes.value = sandboxes.value.filter(s => s.id !== sbId);
}

const STORAGE_KEY = 'chess_game_in_progress';

const skillKeys = {
    0: 'skill_beginner', 3: 'skill_weak', 5: 'skill_medium', 8: 'skill_strong',
    10: 'skill_experienced', 13: 'skill_expert', 16: 'skill_master', 20: 'skill_grandmaster',
};

const currentSkillLabel = computed(() => {
    const keys = Object.keys(skillKeys).map(Number).sort((a, b) => a - b);
    let key = 'skill_beginner';
    for (const k of keys) {
        if (skillLevel.value >= k) key = skillKeys[k];
    }
    return t('play.' + key);
});

const currentSkillRaw = computed(() => {
    const keys = Object.keys(skillKeys).map(Number).sort((a, b) => a - b);
    let key = 'skill_beginner';
    for (const k of keys) {
        if (skillLevel.value >= k) key = skillKeys[k];
    }
    const rawLabels = {
        skill_beginner: 'Beginner', skill_weak: 'Weak', skill_medium: 'Medium', skill_strong: 'Strong',
        skill_experienced: 'Experienced', skill_expert: 'Expert', skill_master: 'Master', skill_grandmaster: 'Grandmaster',
    };
    return rawLabels[key] || 'Experienced';
});

const SKILL_TO_ELO = { 0:400, 1:500, 2:600, 3:700, 4:850, 5:1000, 6:1150, 7:1300, 8:1400, 9:1500, 10:1600, 11:1700, 12:1800, 13:1900, 14:2000, 15:2100, 16:2200, 17:2350, 18:2500, 19:2650, 20:2800 };
const opponentElo = computed(() => SKILL_TO_ELO[Math.max(0, Math.min(20, skillLevel.value))] || 1600);

const isPlayerTurn = computed(() => {
    const turn = fen.value.split(' ')[1];
    return (turn === 'w' && playerColor.value === 'white') ||
           (turn === 'b' && playerColor.value === 'black');
});

const detectedOpening = computed(() => {
    if (moveHistory.value.length < 2) return null;
    return detectOpening(moveHistory.value);
});

onMounted(async () => {
    try {
        await engine.init();
        engineReady.value = true;
    } catch {
        notify(t('play.engine_load_failed'), 'error');
    }

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

            if (chess.value.isGameOver()) {
                endGame({
                    isCheckmate: chess.value.isCheckmate(),
                    isStalemate: chess.value.isStalemate(),
                    isDraw: chess.value.isDraw(),
                    isGameOver: true,
                });
            } else if (!isPlayerTurn.value && engineReady.value) {
                makeEngineMove();
            }

            notify(t('play.game_restored'), 'info');
            return;
        } catch {
            localStorage.removeItem(STORAGE_KEY);
        }
    }

    startNewGame();
});

onUnmounted(() => {
    engine.stop();
    if (gameInProgress.value && !gameOver.value && !gameSaved.value) {
        saveToLocalStorage();
    }
});

watch(moveHistory, () => {
    if (gameInProgress.value && !gameOver.value) {
        saveToLocalStorage();
    }
}, { deep: true });

onBeforeRouteLeave(async (to, from, next) => {
    if (gameInProgress.value && !gameOver.value && !gameSaved.value && moveHistory.value.length > 0) {
        const answer = await confirm(
            t('play.confirm_leave_title'),
            t('play.confirm_leave_msg'),
            'warning'
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

function handleBeforeUnload(e) {
    if (gameInProgress.value && !gameOver.value && moveHistory.value.length > 0) {
        saveToLocalStorage();
        e.preventDefault();
        e.returnValue = '';
    }
}
onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
onUnmounted(() => window.removeEventListener('beforeunload', handleBeforeUnload));

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

async function startNewGame() {
    if (gameInProgress.value && !gameOver.value && moveHistory.value.length > 0) {
        const answer = await confirm(
            t('play.confirm_newgame_title'),
            t('play.confirm_newgame_msg'),
            'warning'
        );
        if (!answer) return;
    }

    chess.value = createGame();
    fen.value = chess.value.fen();
    moveHistory.value = [];
    lastMove.value = null;
    gameOver.value = false;
    gameResult.value = '';
    gameSaved.value = false;
    eloChange.value = null;
    gameInProgress.value = false;
    gameResultCode.value = null;
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

    playMoveSound(result);
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

        playMoveSound(result);
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
        gameResult.value = winnerIsPlayer ? t('play.won') : t('play.lost');
        gameResultCode.value = winnerIsPlayer ? 'player_win' : 'player_loss';
        winnerIsPlayer ? playWin() : playGameEnd();
    } else if (result.isStalemate) {
        gameResult.value = t('play.stalemate');
        gameResultCode.value = 'draw';
    } else if (result.isDraw) {
        gameResult.value = t('play.draw_result');
        gameResultCode.value = 'draw';
    }
}

async function resign() {
    if (!gameInProgress.value || gameOver.value) return;
    const answer = await confirm(
        t('play.confirm_resign_title'),
        t('play.confirm_resign_msg'),
        'danger'
    );
    if (!answer) return;

    gameOver.value = true;
    gameResult.value = t('play.resigned');
    gameResultCode.value = 'player_loss';
    clearLocalStorage();
}

async function saveGame() {
    if (gameSaving.value) return;
    gameSaving.value = true;
    const pgn = moveHistory.value.map((m, i) => {
        if (i % 2 === 0) return `${Math.floor(i / 2) + 1}. ${m.san}`;
        return m.san;
    }).join(' ');

    let resultStr = '*';
    if (gameOver.value) {
        if (gameResultCode.value === 'player_win')
            resultStr = playerColor.value === 'white' ? '1-0' : '0-1';
        else if (gameResultCode.value === 'player_loss')
            resultStr = playerColor.value === 'white' ? '0-1' : '1-0';
        else resultStr = '1/2-1/2';
    }

    const opening = detectedOpening.value;

    try {
        const response = await gamesStore.createGame({
            pgn,
            white_player: playerColor.value === 'white' ? t('play.player') : `Stockfish (${currentSkillRaw.value})`,
            black_player: playerColor.value === 'black' ? t('play.player') : `Stockfish (${currentSkillRaw.value})`,
            result: resultStr,
            user_color: playerColor.value,
            total_moves: moveHistory.value.length,
            opening_name: opening?.name || null,
            opening_eco: opening?.eco || null,
            played_at: new Date().toISOString().split('T')[0],
            skill_level: skillLevel.value,
        });
        gameSaved.value = true;
        clearLocalStorage();

        const elo = response?.elo;
        if (elo && elo.change !== undefined) {
            eloChange.value = elo;
            auth.updateElo(elo.new_elo);
            const sign = elo.change >= 0 ? '+' : '';
            notify(`${t('play.game_saved')} · ELO ${sign}${elo.change} (${elo.new_elo})`, elo.change >= 0 ? 'success' : 'info');
        } else {
            notify(t('play.game_saved'), 'success');
        }
    } catch {
        notify(t('play.save_failed'), 'error');
    } finally {
        gameSaving.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white" data-tutorial="play">
        <div class="max-w-6xl mx-auto">
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-4xl font-black tracking-tight"><span class="text-amber-400">♚</span> {{ $t('nav.play') }}</h1>
                <p class="text-zinc-500 text-xs sm:text-sm mt-1">{{ $t('play.subtitle') }}</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <!-- Board -->
                <div class="flex-shrink-0 mx-auto lg:mx-0">
                    <div class="flex items-center gap-2 mb-2 px-1">
                        <div class="w-3 h-3 rounded-full" :class="engineThinking ? 'bg-amber-400 animate-pulse' : 'bg-zinc-600'"></div>
                        <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">
                            Stockfish · {{ currentSkillLabel }}
                            <span class="text-zinc-600 normal-case">(ELO {{ opponentElo }})</span>
                            <span v-if="engineThinking" class="text-amber-400 ml-1">{{ $t('play.engine_thinking') }}</span>
                        </span>
                    </div>

                    <ChessBoard
                        :fen="fen"
                        :orientation="playerColor"
                        :interactive="isPlayerTurn && !gameOver && !engineThinking"
                        :lastMove="lastMove"
                        :size="boardSize"
                        :resizable="false"
                        @move="handlePlayerMove"
                    />

                    <div class="flex items-center justify-between mt-2 px-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">{{ $t('play.you') }} · {{ playerColor === 'white' ? $t('common.white') : $t('common.black') }}</span>
                        </div>
                        <span v-if="detectedOpening" class="text-[10px] text-zinc-600 font-mono">
                            {{ detectedOpening.eco }} {{ detectedOpening.name }}
                        </span>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="flex-1 min-w-0 w-full">
                    <!-- Game Over -->
                    <div v-if="gameOver" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-5 mb-6 text-center">
                        <p class="text-xl font-black text-amber-400 mb-2">{{ gameResult }}</p>
                        <p class="text-xs text-zinc-500 mb-3">{{ moveHistory.length }} {{ $t('common.moves') }} · {{ detectedOpening?.name || '' }}</p>

                        <div v-if="eloChange" class="mb-4 py-3 px-4 rounded-xl bg-black/20 border border-white/5 inline-block">
                            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">{{ $t('elo.rating_change') }}</p>
                            <div class="flex items-center justify-center gap-3">
                                <span class="text-sm text-zinc-500">{{ eloChange.old_elo }}</span>
                                <span class="text-lg font-black" :class="eloChange.change > 0 ? 'text-emerald-400' : eloChange.change < 0 ? 'text-red-400' : 'text-zinc-400'">
                                    → {{ eloChange.new_elo }}
                                </span>
                                <span class="text-sm font-black px-2 py-0.5 rounded-full"
                                    :class="eloChange.change > 0 ? 'bg-emerald-500/10 text-emerald-400' : eloChange.change < 0 ? 'bg-red-500/10 text-red-400' : 'bg-zinc-800 text-zinc-400'">
                                    {{ eloChange.change > 0 ? '+' : '' }}{{ eloChange.change }}
                                </span>
                            </div>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ $t('elo.opponent_rating') }}: {{ eloChange.opponent_elo }}</p>
                        </div>

                        <div class="flex items-center justify-center gap-3">
                            <button @click="startNewGame" class="px-5 py-2.5 bg-amber-500 text-black font-bold rounded-xl text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 transition-all hover:bg-amber-400">{{ $t('play.new_game_btn') }}</button>
                            <button v-if="!gameSaved" @click="saveGame" :disabled="gameSaving" class="px-5 py-2.5 border border-amber-500/30 text-amber-400 font-bold rounded-xl text-sm disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 transition-all hover:bg-amber-500/10">{{ $t('play.save_btn') }}</button>
                            <span v-else class="text-xs text-emerald-400 font-bold">{{ $t('play.saved_label') }}</span>
                        </div>
                    </div>

                    <!-- In-game actions -->
                    <div v-if="gameInProgress && !gameOver" class="flex items-center gap-2 mb-4">
                        <button @click="saveGame"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-emerald-400 hover:border-emerald-500/20 transition-all text-center">
                            {{ $t('play.save_position') }}
                        </button>
                        <button @click="spawnSandbox"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-violet-500/20 text-violet-400 hover:bg-violet-500/10 transition-all text-center">
                            🧪 {{ $t('sandbox.spawn') }}
                        </button>
                        <button @click="resign"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-red-400 hover:border-red-500/20 transition-all text-center">
                            {{ $t('play.resign') }}
                        </button>
                    </div>

                    <!-- Settings -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 mb-6">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('play.settings') }}</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 block">{{ $t('play.your_color') }}</label>
                                <div class="flex gap-2">
                                    <button v-for="c in ['white', 'black']" :key="c"
                                        @click="playerColor = c; startNewGame()"
                                        :class="['px-4 py-2 rounded-lg text-sm font-bold border transition-all',
                                            playerColor === c ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'border-white/5 text-zinc-500']">
                                        {{ c === 'white' ? $t('play.white_piece') : $t('play.black_piece') }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 flex items-center justify-between">
                                    <span>{{ $t('play.difficulty') }}</span>
                                    <span :class="[
                                        skillLevel <= 3 ? 'text-emerald-400' :
                                        skillLevel <= 8 ? 'text-amber-400' :
                                        skillLevel <= 14 ? 'text-orange-400' : 'text-red-400']">
                                        {{ currentSkillLabel }} · ELO ~{{ opponentElo }}
                                    </span>
                                </label>
                                <input type="range" v-model.number="skillLevel" min="0" max="20" class="w-full accent-amber-500">
                                <div class="flex justify-between text-[9px] text-zinc-600 mt-1">
                                    <span>{{ $t('play.skill_beginner') }}</span>
                                    <span>{{ $t('play.skill_grandmaster') }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-zinc-400 mb-2 flex items-center justify-between">
                                    <span>{{ $t('play.thinking_time') }}</span>
                                    <span class="text-amber-400">{{ (moveTime / 1000).toFixed(1) }}s</span>
                                </label>
                                <input type="range" v-model.number="moveTime" min="500" max="5000" step="250" class="w-full">
                            </div>

                            <button @click="startNewGame"
                                class="w-full py-2.5 bg-zinc-800 text-zinc-300 font-bold rounded-xl hover:bg-zinc-700 transition-all text-sm">
                                {{ $t('play.new_game_icon') }}
                            </button>
                        </div>
                    </div>

                    <!-- Move history -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">{{ $t('play.moves_count', { count: moveHistory.length }) }}</h3>
                        <div class="max-h-60 overflow-y-auto">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(m, i) in moveHistory" :key="i">
                                    <span v-if="i % 2 === 0" class="text-xs text-zinc-600 font-mono mr-0.5">{{ Math.floor(i/2)+1 }}.</span>
                                    <span :class="['text-sm font-bold mr-1', m.color === playerColor ? 'text-amber-400' : 'text-zinc-400']">
                                        {{ m.san }}
                                    </span>
                                </template>
                            </div>
                            <p v-if="moveHistory.length === 0" class="text-zinc-600 text-xs">{{ $t('play.make_first_move') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sandbox boards -->
            <div v-if="sandboxes.length" class="mt-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4 flex items-center gap-2">
                    🧪 {{ $t('sandbox.title') }}
                    <span class="text-[10px] text-zinc-600 normal-case font-normal tracking-normal">{{ $t('sandbox.description') }}</span>
                </h3>
                <div class="space-y-4">
                    <SandboxBoard
                        v-for="sb in sandboxes" :key="sb.id"
                        :initialFen="sb.fen"
                        :depth="0"
                        @close="removeSandbox(sb.id)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
