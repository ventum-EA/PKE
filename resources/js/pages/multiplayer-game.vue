<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useConfirm } from '../composables/useConfirm';
import { useNotification } from '../composables/useNotification';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import { useSounds } from '../composables/useSounds';
import { useWebSocket } from '../composables/useWebSocket';
import { makeMove as chessMove, detectOpening } from '../services/chess';
import api from '../services/api';
import ChessBoard from '../components/ChessBoard.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { notify } = useNotification();
const auth = useAuthStore();
const { confirm } = useConfirm();
const { boardSize } = useResponsiveBoard({ maxSize: 480, padding: 48 });
const { onMove, onGameEnd, onDrawOffer, leaveGame } = useWebSocket();
const { playMoveSound, playGameStart, playGameEnd, playWin, playCheck, playLowTime, playNotify } = useSounds();

const gameId = computed(() => Number(route.params.id));
const game = ref(null);
const isLoading = ref(true);
const lastMove = ref(null);
const boardFlipped = ref(false);
const copiedPgn = ref(false);
let clockHandle = null;

// Clocks (local countdown between WebSocket updates)
const whiteTimeLocal = ref(0);
const blackTimeLocal = ref(0);

const myColor = computed(() => {
    if (!game.value) return null;
    return game.value.white?.id === auth.user?.id ? 'white' : game.value.black?.id === auth.user?.id ? 'black' : null;
});
const opponentColor = computed(() => myColor.value === 'white' ? 'black' : 'white');
const isMyTurn = computed(() => {
    if (!game.value || !myColor.value) return false;
    return game.value.fen.includes(' w ') ? myColor.value === 'white' : myColor.value === 'black';
});
const isActive = computed(() => game.value?.status === 'active');
const isCompleted = computed(() => game.value?.status === 'completed');
const opponent = computed(() => {
    if (!game.value || !myColor.value) return null;
    return myColor.value === 'white' ? game.value.black : game.value.white;
});
const me = computed(() => {
    if (!game.value || !myColor.value) return null;
    return myColor.value === 'white' ? game.value.white : game.value.black;
});
const drawOfferedToMe = computed(() => {
    if (!game.value?.draw_offered_by || !myColor.value) return false;
    return game.value.draw_offered_by !== myColor.value;
});
const myEloChange = computed(() => {
    if (!myColor.value || !game.value) return null;
    return myColor.value === 'white' ? game.value.white?.elo_change : game.value.black?.elo_change;
});
const resultText = computed(() => {
    if (!game.value?.result) return '';
    if (game.value.result === '1/2-1/2') return t('mp.draw');
    const whiteWon = game.value.result === '1-0';
    return (whiteWon && myColor.value === 'white') || (!whiteWon && myColor.value === 'black')
        ? t('mp.you_won') : t('mp.you_lost');
});
const resultReasonText = computed(() => {
    const r = game.value?.result_reason;
    if (!r) return '';
    const map = { checkmate: t('mp.by_checkmate'), resignation: t('mp.by_resignation'), timeout: t('mp.by_timeout'), abandon: t('mp.by_abandon'), draw_agreement: t('mp.by_agreement'), stalemate: t('mp.by_stalemate') };
    return map[r] || r;
});

// Opening detection (client-side, from moves array)
const detectedOpening = computed(() => {
    if (!game.value?.moves?.length) return null;
    return detectOpening(game.value.moves);
});

function formatClock(ms) {
    if (ms == null) return '—:——';
    const total = Math.max(0, Math.floor(ms / 1000));
    const m = Math.floor(total / 60);
    const s = total % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

async function loadGame() {
    try {
        const { data } = await api.get(`/multiplayer/${gameId.value}`);
        game.value = data.game;
        whiteTimeLocal.value = data.game.white_time ?? 0;
        blackTimeLocal.value = data.game.black_time ?? 0;

        // Set lastMove from the last move in history
        const moves = data.game.moves || [];
        if (moves.length) {
            const last = moves[moves.length - 1];
            lastMove.value = { from: last.uci.substring(0, 2), to: last.uci.substring(2, 4) };
        }
    } catch (err) {
        notify(t('mp.load_error'), 'error');
    }
}

let claimingTimeout = false;

function startClock() {
    clockHandle = setInterval(() => {
        if (!isActive.value || !game.value) return;
        const isWhiteTurn = game.value.fen.includes(' w ');
        if (isWhiteTurn) {
            whiteTimeLocal.value = Math.max(0, whiteTimeLocal.value - 250);
            if (whiteTimeLocal.value <= 0) {
                handleTimeout();
                return;
            }
            if (whiteTimeLocal.value <= 30000 && whiteTimeLocal.value % 1000 < 250) playLowTime();
        } else {
            blackTimeLocal.value = Math.max(0, blackTimeLocal.value - 250);
            if (blackTimeLocal.value <= 0) {
                handleTimeout();
                return;
            }
            if (blackTimeLocal.value <= 30000 && blackTimeLocal.value % 1000 < 250) playLowTime();
        }
    }, 250);
}

/** Auto-claim timeout when any player's clock hits zero */
async function handleTimeout() {
    if (claimingTimeout) return;
    claimingTimeout = true;
    stopClock();
    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/timeout`);
        game.value = data.game;
        const won = (myColor.value === 'white' && data.game.result === '1-0') ||
                    (myColor.value === 'black' && data.game.result === '0-1');
        won ? playWin() : playGameEnd();
        auth.fetchUser();
    } catch (err) {
        // Opponent may have already claimed it, or server disagrees — resync
        await loadGame();
        if (game.value?.status === 'completed') {
            stopClock();
        }
    } finally {
        claimingTimeout = false;
    }
}

function stopClock() {
    if (clockHandle) { clearInterval(clockHandle); clockHandle = null; }
}

/** Subscribe to real-time game events via WebSocket */
function subscribeToGame(gId) {
    // Opponent makes a move
    onMove(gId, (state) => {
        const prevMoves = game.value?.moves?.length || 0;
        game.value = state;
        whiteTimeLocal.value = state.white_time ?? 0;
        blackTimeLocal.value = state.black_time ?? 0;

        if (state.moves?.length > prevMoves) {
            const last = state.moves[state.moves.length - 1];
            lastMove.value = { from: last.uci.substring(0, 2), to: last.uci.substring(2, 4) };
            // Play sound for opponent's move
            playMoveSound({ san: last.san, captured: last.san.includes('x'), isCheck: last.san.includes('+'), isCheckmate: last.san.includes('#') });
        }
    });

    // Game ended (by opponent resign, timeout, etc.)
    onGameEnd(gId, (state) => {
        game.value = state;
        stopClock();
        const won = (myColor.value === 'white' && state.result === '1-0') || (myColor.value === 'black' && state.result === '0-1');
        won ? playWin() : playGameEnd();
        auth.fetchUser();
    });

    // Draw offer updates
    onDrawOffer(gId, (e) => {
        if (game.value) {
            game.value.draw_offered_by = e.action === 'offered' ? e.offeredBy : null;
            if (e.action === 'offered' && e.offeredBy !== myColor.value) playNotify();
        }
    });
}

async function handleMove(move) {
    if (!isMyTurn.value || !isActive.value) return;

    const result = chessMove(game.value.fen, move.from, move.to, move.promotion);
    if (!result) return;

    // Immediately update local state so board and clock reflect the move
    game.value.fen = result.fen;
    game.value.total_moves = (game.value.total_moves || 0) + 1;
    lastMove.value = { from: move.from, to: move.to };
    playMoveSound(result);

    const opening = detectedOpening.value;

    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/move`, {
            san: result.san,
            uci: move.from + move.to + (move.promotion || ''),
            fen: result.fen,
            is_game_over: result.isGameOver || false,
            is_checkmate: result.isCheckmate || false,
            is_stalemate: result.isStalemate || false,
            is_draw: result.isDraw || false,
            opening_name: opening?.name || null,
            opening_eco: opening?.eco || null,
            time_remaining: myColor.value === 'white' ? whiteTimeLocal.value : blackTimeLocal.value,
        });
        game.value = data.game;
        if (data.game.status === 'completed') {
            stopClock();
            playWin(); // We made the winning move
            auth.fetchUser();
        }
    } catch (err) {
        notify(err?.response?.data?.message || t('mp.move_error'), 'error');
        await loadGame(); // Resync on error
    }
}

async function handleResign() {
    const answer = await confirm(t('mp.confirm_resign_title'), t('mp.confirm_resign_msg'), 'danger');
    if (!answer) return;
    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/resign`);
        game.value = data.game;
        stopClock();
        playGameEnd();
        auth.fetchUser();
    } catch { /* intentionally silenced */ }
}

async function handleDrawOffer() {
    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/draw`, { action: 'offer' });
        game.value = data.game;
        notify(t('mp.draw_offered'), 'success');
    } catch { /* intentionally silenced */ }
}

async function handleDrawAccept() {
    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/draw`, { action: 'accept' });
        game.value = data.game;
        stopClock();
        playGameEnd();
        auth.fetchUser();
    } catch { /* intentionally silenced */ }
}

async function handleDrawDecline() {
    try {
        const { data } = await api.post(`/multiplayer/${gameId.value}/draw`, { action: 'decline' });
        game.value = data.game;
    } catch { /* intentionally silenced */ }
}

async function rematch() {
    try {
        const opponentId = game.value ? (myColor.value === 'white' ? game.value.black?.id : game.value.white?.id) : null;
        const { data } = await api.post('/multiplayer/create', {
            color: myColor.value === 'white' ? 'black' : 'white', // swap colors
            time_control: game.value?.time_control || 600,
            rated: game.value?.rated ?? true,
        });
        const url = `${window.location.origin}/multiplayer/join/${data.invite_token}`;
        await navigator.clipboard.writeText(url);
        notify(t('mp.rematch_created'), 'success');
    } catch { /* intentionally silenced */ }
}

async function copyPgn() {
    if (!game.value?.moves?.length) return;
    const pgn = game.value.moves.map(m =>
        m.color === 'white' ? `${m.move_number}. ${m.san}` : m.san
    ).join(' ');
    try {
        await navigator.clipboard.writeText(pgn);
        copiedPgn.value = true;
        setTimeout(() => copiedPgn.value = false, 2000);
    } catch { /* intentionally silenced */ }
}

let pollHandle = null;

/** Poll game state as fallback when WebSocket isn't connected */
function startPolling(gId) {
    if (pollHandle) return;
    pollHandle = setInterval(async () => {
        if (!isActive.value) { stopPolling(); return; }
        try {
            const { data } = await api.get(`/multiplayer/${gId}`);
            const state = data.game || data;
            if (!state) return;

            const prevMoves = game.value?.moves?.length || 0;
            const newMoves = state.moves?.length || 0;

            if (newMoves > prevMoves) {
                game.value = state;
                whiteTimeLocal.value = state.white_time ?? 0;
                blackTimeLocal.value = state.black_time ?? 0;
                const last = state.moves[state.moves.length - 1];
                if (last) {
                    lastMove.value = { from: last.uci.substring(0, 2), to: last.uci.substring(2, 4) };
                    playMoveSound({ san: last.san, captured: last.san?.includes('x'), isCheck: last.san?.includes('+') });
                }
            }

            // Check if game ended
            if (state.status && state.status !== 'active' && state.status !== 'waiting') {
                game.value = state;
                stopClock();
                stopPolling();
                const won = (myColor.value === 'white' && state.result === '1-0') || (myColor.value === 'black' && state.result === '0-1');
                won ? playWin() : playGameEnd();
                auth.fetchUser();
            }

            // Draw offer update
            if (state.draw_offered_by !== game.value?.draw_offered_by) {
                game.value.draw_offered_by = state.draw_offered_by;
                if (state.draw_offered_by && state.draw_offered_by !== myColor.value) playNotify();
            }
        } catch { /* poll failed, retry next interval */ }
    }, 2500);
}

function stopPolling() {
    if (pollHandle) { clearInterval(pollHandle); pollHandle = null; }
}

onMounted(async () => {
    await loadGame();
    isLoading.value = false;
    if (isActive.value) {
        subscribeToGame(gameId.value);
        startPolling(gameId.value);
        startClock();
        playGameStart();
    }
});

onUnmounted(() => {
    stopClock();
    stopPolling();
    leaveGame(gameId.value);
});
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <div class="max-w-6xl mx-auto">
            <!-- Loading -->
            <div v-if="isLoading" class="flex justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="game" class="flex flex-col lg:flex-row gap-6">
                <!-- Board column -->
                <div class="flex-shrink-0 mx-auto lg:mx-0">
                    <!-- Opponent bar (top) -->
                    <div class="flex items-center justify-between mb-2 px-0.5">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" :class="isActive && !isMyTurn ? 'bg-amber-400 animate-pulse' : 'bg-zinc-600'"></div>
                            <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                                {{ opponent?.name || $t('mp.waiting') }}
                                <span class="text-zinc-600 normal-case">({{ opponent?.elo_rating || '?' }})</span>
                            </span>
                        </div>
                        <span class="text-sm font-mono tabular-nums font-bold px-3 py-1 rounded-lg"
                            :class="isActive && !isMyTurn ? 'bg-zinc-800 text-white' : 'text-zinc-500'">
                            {{ formatClock(opponentColor === 'white' ? whiteTimeLocal : blackTimeLocal) }}
                        </span>
                    </div>

                    <ChessBoard
                        :fen="game.fen"
                        :orientation="boardFlipped ? (myColor === 'white' ? 'black' : 'white') : (myColor || 'white')"
                        :interactive="isMyTurn && isActive"
                        :lastMove="lastMove"
                        :size="boardSize"
                        :resizable="false"
                        @move="handleMove"
                    />

                    <!-- Player bar (bottom) -->
                    <div class="flex items-center justify-between mt-2 px-0.5">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" :class="isActive && isMyTurn ? 'bg-emerald-400 animate-pulse' : 'bg-emerald-400'"></div>
                            <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                                {{ me?.name || $t('mp.you') }}
                                <span class="text-zinc-600 normal-case">({{ me?.elo_rating || '?' }})</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="boardFlipped = !boardFlipped" class="text-zinc-600 hover:text-zinc-300 text-xs px-3 py-2 rounded-lg border border-white/5 hover:border-white/10 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60" :title="$t('mp.flip_board')" :aria-label="$t('mp.flip_board')">↕</button>
                            <span class="text-sm font-mono tabular-nums font-bold px-3 py-1 rounded-lg"
                                :class="isActive && isMyTurn ? 'bg-zinc-800 text-white' : 'text-zinc-500'">
                                {{ formatClock(myColor === 'white' ? whiteTimeLocal : blackTimeLocal) }}
                            </span>
                        </div>
                    </div>

                    <!-- Opening -->
                    <p v-if="detectedOpening || game.opening_name" class="text-[10px] text-zinc-600 font-mono mt-2 px-0.5 truncate">
                        {{ game.opening_eco || detectedOpening?.eco }} {{ game.opening_name || detectedOpening?.name }}
                    </p>
                </div>

                <!-- Right panel -->
                <div class="flex-1 min-w-0 space-y-4">
                    <!-- Game over -->
                    <div v-if="isCompleted" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 text-center">
                        <p class="text-xl font-black text-amber-400 mb-1">{{ resultText }}</p>
                        <p class="text-xs text-zinc-500 mb-3">{{ resultReasonText }}</p>

                        <!-- ELO change -->
                        <div v-if="myEloChange != null" class="inline-flex items-center gap-3 py-2 px-4 rounded-xl bg-black/20 border border-white/5 mb-4">
                            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">ELO</span>
                            <span class="text-lg font-black" :class="myEloChange > 0 ? 'text-emerald-400' : myEloChange < 0 ? 'text-red-400' : 'text-zinc-400'">
                                {{ myEloChange > 0 ? '+' : '' }}{{ myEloChange }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <router-link to="/multiplayer" class="px-5 py-2 bg-amber-500 text-black font-bold rounded-xl text-sm hover:bg-amber-400 active:scale-95 transition-all">
                                {{ $t('mp.back_to_lobby') }}
                            </router-link>
                            <button @click="rematch" class="px-5 py-2 bg-zinc-800 text-zinc-300 font-bold rounded-xl text-sm hover:bg-zinc-700 transition-all">
                                ↻ {{ $t('mp.rematch') }}
                            </button>
                            <button @click="copyPgn" class="px-4 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 transition-all">
                                {{ copiedPgn ? '✓' : '📋' }} PGN
                            </button>
                        </div>
                    </div>

                    <!-- Draw offer banner -->
                    <div v-if="drawOfferedToMe && isActive" class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4">
                        <p class="text-sm font-bold text-blue-400 mb-3">{{ $t('mp.draw_offered_to_you') }}</p>
                        <div class="flex gap-2">
                            <button @click="handleDrawAccept" class="flex-1 py-2 text-xs font-bold rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/15 transition-all">
                                ✓ {{ $t('mp.accept_draw') }}
                            </button>
                            <button @click="handleDrawDecline" class="flex-1 py-2 text-xs font-bold rounded-xl text-zinc-400 border border-white/10 hover:text-red-400 hover:border-red-500/20 transition-all">
                                ✕ {{ $t('mp.decline_draw') }}
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="isActive" class="flex items-center gap-2">
                        <button @click="handleDrawOffer" :disabled="game.draw_offered_by === myColor"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-blue-400 hover:border-blue-500/20 disabled:opacity-30 transition-all text-center">
                            ½ {{ $t('mp.offer_draw') }}
                        </button>
                        <button @click="handleResign"
                            class="flex-1 py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-red-400 hover:border-red-500/20 transition-all text-center">
                            🏳 {{ $t('mp.resign') }}
                        </button>
                    </div>

                    <!-- Move history -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-3">
                            {{ $t('mp.moves') }} ({{ game.total_moves }})
                        </h3>
                        <div class="max-h-72 overflow-y-auto">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(m, i) in game.moves" :key="i">
                                    <span v-if="m.color === 'white'" class="text-xs text-zinc-600 font-mono mr-0.5">{{ m.move_number }}.</span>
                                    <span :class="['text-sm font-bold mr-1', m.color === myColor ? 'text-amber-400' : 'text-zinc-400']">
                                        {{ m.san }}
                                    </span>
                                </template>
                            </div>
                            <p v-if="!game.moves?.length" class="text-zinc-600 text-xs">{{ $t('mp.no_moves_yet') }}</p>
                        </div>
                    </div>

                    <!-- Turn indicator -->
                    <div v-if="isActive" class="text-center">
                        <p class="text-xs font-bold" :class="isMyTurn ? 'text-emerald-400' : 'text-zinc-500'">
                            {{ isMyTurn ? $t('mp.your_turn') : $t('mp.opponent_turn') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
