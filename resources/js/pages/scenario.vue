<script setup>
import { ref, computed, reactive } from 'vue';
import { Chess } from 'chess.js';
import ChessBoard from '../components/ChessBoard.vue';
import { useNotification } from '../composables/useNotification';

const { notify } = useNotification();

// --- Board state --------------------------------------------------------
// 8 rows × 8 cols, index [0][0] = a8, [7][7] = h1 (board-rendering order)
const makeEmptyBoard = () => Array.from({ length: 8 }, () => Array(8).fill(null));
const makeStartBoard = () => {
    const b = makeEmptyBoard();
    const back = ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'];
    for (let c = 0; c < 8; c++) {
        b[0][c] = back[c];       // black back rank
        b[1][c] = 'p';
        b[6][c] = 'P';
        b[7][c] = back[c].toUpperCase();
    }
    return b;
};

const board = ref(makeStartBoard());
const selectedPiece = ref('P'); // active palette brush
const turn = ref('w');
const castling = reactive({ K: true, Q: true, k: true, q: true });
const enPassant = ref('-');

// Play-mode state --------------------------------------------------------
const playMode = ref(false);
const playFen = ref('');
const playGame = ref(null);
const lastMove = ref(null);
const moveHistory = ref([]);

// --- FEN build / parse --------------------------------------------------
function buildFen() {
    const rows = board.value.map(row => {
        let s = '';
        let empty = 0;
        for (const cell of row) {
            if (cell === null) {
                empty++;
            } else {
                if (empty > 0) { s += empty; empty = 0; }
                s += cell;
            }
        }
        if (empty > 0) s += empty;
        return s;
    });
    const placement = rows.join('/');

    let castleStr = '';
    if (castling.K) castleStr += 'K';
    if (castling.Q) castleStr += 'Q';
    if (castling.k) castleStr += 'k';
    if (castling.q) castleStr += 'q';
    if (!castleStr) castleStr = '-';

    return `${placement} ${turn.value} ${castleStr} ${enPassant.value} 0 1`;
}

function loadFen(fen) {
    try {
        const parts = fen.trim().split(/\s+/);
        if (parts.length < 2) throw new Error('Invalid FEN');

        // Validate with chess.js
        new Chess(fen);

        const grid = makeEmptyBoard();
        const rows = parts[0].split('/');
        if (rows.length !== 8) throw new Error('Invalid rows');
        for (let r = 0; r < 8; r++) {
            let c = 0;
            for (const ch of rows[r]) {
                if (ch >= '1' && ch <= '8') {
                    c += parseInt(ch);
                } else {
                    if (c > 7) throw new Error('Invalid row width');
                    grid[r][c++] = ch;
                }
            }
            if (c !== 8) throw new Error('Invalid row width');
        }

        board.value = grid;
        turn.value = parts[1] === 'b' ? 'b' : 'w';
        const castleStr = parts[2] || '-';
        castling.K = castleStr.includes('K');
        castling.Q = castleStr.includes('Q');
        castling.k = castleStr.includes('k');
        castling.q = castleStr.includes('q');
        enPassant.value = parts[3] || '-';

        return true;
    } catch (err) {
        notify('Nederīgs FEN: ' + err.message, 'error');
        return false;
    }
}

// --- Palette & click handling ------------------------------------------
const palette = [
    { label: 'Baltās', pieces: ['K', 'Q', 'R', 'B', 'N', 'P'] },
    { label: 'Melnās', pieces: ['k', 'q', 'r', 'b', 'n', 'p'] },
];

const PIECE_SYMBOLS = {
    K: '♔', Q: '♕', R: '♖', B: '♗', N: '♘', P: '♙',
    k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟',
};

function placeOnSquare(r, c) {
    // Click with empty/eraser brush removes the piece
    if (selectedPiece.value === '') {
        board.value[r][c] = null;
    } else {
        board.value[r][c] = selectedPiece.value;
    }
    // Force Vue reactivity on nested array mutation
    board.value = board.value.map(row => row.slice());
}

function clearBoard() {
    board.value = makeEmptyBoard();
    castling.K = castling.Q = castling.k = castling.q = false;
    enPassant.value = '-';
}

function resetToStart() {
    board.value = makeStartBoard();
    turn.value = 'w';
    castling.K = castling.Q = castling.k = castling.q = true;
    enPassant.value = '-';
}

// --- Validation & play --------------------------------------------------
const currentFen = computed(() => buildFen());

const validation = computed(() => {
    try {
        const c = new Chess(currentFen.value);
        return { ok: true, inCheck: c.inCheck?.() || false, turn: c.turn() };
    } catch (err) {
        return { ok: false, error: err.message };
    }
});

function startPlaying() {
    if (!validation.value.ok) {
        notify('Pozīcija nav derīga šaha spēlei', 'error');
        return;
    }
    try {
        playGame.value = new Chess(currentFen.value);
        playFen.value = playGame.value.fen();
        moveHistory.value = [];
        lastMove.value = null;
        playMode.value = true;
    } catch (err) {
        notify('Neizdevās sākt spēli: ' + err.message, 'error');
    }
}

function stopPlaying() {
    playMode.value = false;
    playGame.value = null;
}

function handleMove({ from, to, promotion }) {
    if (!playGame.value) return;
    try {
        const result = playGame.value.move({ from, to, promotion: promotion || 'q' });
        if (result) {
            playFen.value = playGame.value.fen();
            lastMove.value = { from, to };
            moveHistory.value.push(result.san);
        }
    } catch {
        /* illegal move — ignore */
    }
}

function undoMove() {
    if (!playGame.value) return;
    const undone = playGame.value.undo();
    if (undone) {
        playFen.value = playGame.value.fen();
        moveHistory.value.pop();
        const h = playGame.value.history({ verbose: true });
        lastMove.value = h.length ? { from: h[h.length - 1].from, to: h[h.length - 1].to } : null;
    }
}

// --- FEN clipboard ------------------------------------------------------
const fenInput = ref('');
const fenCopied = ref(false);

async function copyFen() {
    try {
        await navigator.clipboard.writeText(currentFen.value);
        fenCopied.value = true;
        setTimeout(() => { fenCopied.value = false; }, 1600);
        notify('FEN nokopēts', 'success');
    } catch {
        notify('Neizdevās nokopēt', 'error');
    }
}

function importFen() {
    if (loadFen(fenInput.value)) {
        notify('FEN ielādēts', 'success');
        fenInput.value = '';
    }
}

// --- Presets ------------------------------------------------------------
const presets = [
    { name: 'Sākums', fen: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' },
    { name: 'Tikai karaļi', fen: '4k3/8/8/8/8/8/8/4K3 w - - 0 1' },
    { name: 'K+D vs K', fen: '4k3/8/8/8/8/8/8/3QK3 w - - 0 1' },
    { name: 'Matēšana (K+R)', fen: '4k3/8/8/8/8/8/8/R3K3 w - - 0 1' },
];

function loadPreset(fen) { loadFen(fen); }
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                    <span class="text-amber-400">⚒</span> {{ $t('nav.scenario') }}
                </h1>
                <p class="text-zinc-500 text-sm mt-2">Izveido pielāgotu pozīciju un spēlē no tās</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[auto_1fr] gap-6 lg:gap-10">
                <!-- BOARD -->
                <div class="flex flex-col items-center">
                    <!-- Edit mode: custom grid -->
                    <div v-if="!playMode"
                        class="inline-block rounded-lg overflow-hidden shadow-2xl"
                        :style="{ width: 'min(480px, calc(100vw - 3rem))' }">
                        <div class="grid grid-cols-8 aspect-square select-none">
                            <template v-for="(row, r) in board" :key="'r'+r">
                                <div v-for="(cell, c) in row" :key="'c'+r+'-'+c"
                                    @click="placeOnSquare(r, c)"
                                    :class="[
                                        'flex items-center justify-center text-[min(8vw,2.8rem)] font-black cursor-pointer transition-colors',
                                        (r + c) % 2 === 0 ? 'bg-[#f0d9b5]' : 'bg-[#b58863]',
                                        'hover:ring-2 hover:ring-amber-400/60 hover:ring-inset'
                                    ]">
                                    <span v-if="cell"
                                        :class="cell === cell.toUpperCase() ? 'text-white drop-shadow-[0_1px_0_#000]' : 'text-black'">
                                        {{ PIECE_SYMBOLS[cell] }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Play mode: real board -->
                    <div v-else>
                        <ChessBoard :fen="playFen" :last-move="lastMove" :interactive="true"
                            :orientation="'white'" @move="handleMove" />
                    </div>

                    <!-- Mode switch -->
                    <div class="flex gap-2 mt-4 w-full max-w-[480px]">
                        <button v-if="!playMode" @click="startPlaying"
                            :disabled="!validation.ok"
                            class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-40 shadow-lg shadow-amber-500/20 uppercase tracking-wider text-xs sm:text-sm hover:from-amber-400 hover:to-amber-500 transition-all">
                            ▶ Spēlēt no pozīcijas
                        </button>
                        <template v-else>
                            <button @click="undoMove"
                                class="flex-1 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl border border-white/10 hover:text-amber-400 hover:border-amber-500/30 transition-all uppercase tracking-wider text-xs sm:text-sm">
                                ↶ Atcelt
                            </button>
                            <button @click="stopPlaying"
                                class="flex-1 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl border border-white/10 hover:text-red-400 hover:border-red-500/30 transition-all uppercase tracking-wider text-xs sm:text-sm">
                                ✎ Rediģēt
                            </button>
                        </template>
                    </div>
                </div>

                <!-- CONTROLS -->
                <div class="space-y-5">
                    <!-- Edit mode panels -->
                    <template v-if="!playMode">
                        <!-- Piece palette -->
                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Figūru palete</h3>
                            <div v-for="group in palette" :key="group.label" class="mb-3 last:mb-0">
                                <p class="text-[10px] uppercase text-zinc-600 font-bold mb-2">{{ group.label }}</p>
                                <div class="grid grid-cols-6 gap-2">
                                    <button v-for="p in group.pieces" :key="p"
                                        @click="selectedPiece = p"
                                        :class="['aspect-square rounded-lg text-2xl sm:text-3xl font-black border transition-all',
                                            p === p.toUpperCase() ? 'bg-white/90 text-black' : 'bg-zinc-800 text-white',
                                            selectedPiece === p ? 'border-amber-400 ring-2 ring-amber-400/40 scale-110' : 'border-white/10 hover:border-white/30']">
                                        {{ PIECE_SYMBOLS[p] }}
                                    </button>
                                </div>
                            </div>
                            <button @click="selectedPiece = ''"
                                :class="['w-full mt-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-all',
                                    selectedPiece === '' ? 'border-amber-400 text-amber-400 bg-amber-400/10' : 'border-white/10 text-zinc-500 hover:text-zinc-300']">
                                ✕ Dzēšgumija
                            </button>
                        </section>

                        <!-- Position options -->
                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Pozīcijas opcijas</h3>

                            <div class="mb-4">
                                <p class="text-[10px] uppercase text-zinc-600 font-bold mb-2">Gājiens</p>
                                <div class="flex gap-2">
                                    <button @click="turn = 'w'"
                                        :class="['flex-1 py-2 rounded-lg text-xs font-bold border transition-all',
                                            turn === 'w' ? 'bg-white/90 text-black border-white' : 'bg-zinc-800 text-zinc-400 border-white/10 hover:text-zinc-200']">
                                        ♔ Baltais
                                    </button>
                                    <button @click="turn = 'b'"
                                        :class="['flex-1 py-2 rounded-lg text-xs font-bold border transition-all',
                                            turn === 'b' ? 'bg-zinc-700 text-white border-white/30' : 'bg-zinc-800 text-zinc-400 border-white/10 hover:text-zinc-200']">
                                        ♚ Melnais
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="text-[10px] uppercase text-zinc-600 font-bold mb-2">Rokāde</p>
                                <div class="grid grid-cols-4 gap-2">
                                    <label v-for="(val, key) in castling" :key="key"
                                        class="flex items-center justify-center gap-1 py-2 bg-zinc-800 rounded-lg cursor-pointer text-xs font-bold text-zinc-400 hover:text-zinc-200 border border-white/5">
                                        <input type="checkbox" v-model="castling[key]" class="accent-amber-500" />
                                        {{ key }}
                                    </label>
                                </div>
                            </div>

                            <div v-if="!validation.ok" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
                                ⚠ {{ validation.error }}
                            </div>
                            <div v-else-if="validation.inCheck" class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2">
                                Šahs!
                            </div>
                        </section>

                        <!-- FEN I/O -->
                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">FEN</h3>
                            <div class="bg-black/40 border border-white/5 rounded-lg px-3 py-2 mb-3 font-mono text-[11px] text-zinc-400 break-all">
                                {{ currentFen }}
                            </div>
                            <div class="flex gap-2 mb-3">
                                <button @click="copyFen" type="button"
                                    :aria-label="fenCopied ? 'FEN nokopēts' : 'Kopēt FEN'"
                                    :class="['flex-1 py-2 font-bold rounded-lg border text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60',
                                        fenCopied
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 animate-pop-success'
                                            : 'bg-zinc-800 text-zinc-300 border-white/10 hover:text-amber-400 hover:border-amber-500/30']">
                                    {{ fenCopied ? '✓ Nokopēts' : '⧉ Kopēt' }}
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <input v-model="fenInput" type="text" placeholder="Ielīmēt FEN..."
                                    class="flex-1 bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/50 font-mono" />
                                <button @click="importFen"
                                    class="px-4 py-2 bg-amber-500/10 text-amber-400 font-bold rounded-lg border border-amber-500/20 hover:bg-amber-500/20 text-xs uppercase tracking-wider transition-all">
                                    Importēt
                                </button>
                            </div>
                        </section>

                        <!-- Presets -->
                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Gatavie scenāriji</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="p in presets" :key="p.name" @click="loadPreset(p.fen)"
                                    class="py-2 px-3 bg-zinc-800 text-zinc-300 font-bold rounded-lg border border-white/10 hover:text-amber-400 hover:border-amber-500/30 text-xs transition-all text-left">
                                    {{ p.name }}
                                </button>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <button @click="resetToStart"
                                    class="flex-1 py-2 bg-zinc-800 text-zinc-400 font-bold rounded-lg border border-white/10 hover:text-amber-400 text-xs uppercase tracking-wider transition-all">
                                    ↺ Sākuma poz.
                                </button>
                                <button @click="clearBoard"
                                    class="flex-1 py-2 bg-zinc-800 text-red-400/70 font-bold rounded-lg border border-white/10 hover:text-red-400 hover:border-red-500/30 text-xs uppercase tracking-wider transition-all">
                                    ⌫ Notīrīt
                                </button>
                            </div>
                        </section>
                    </template>

                    <!-- Play mode panels -->
                    <template v-else>
                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Gājienu vēsture</h3>
                            <div v-if="moveHistory.length === 0" class="text-xs text-zinc-600 italic">
                                Vēl nav gājienu
                            </div>
                            <div v-else class="flex flex-wrap gap-1.5 max-h-64 overflow-y-auto">
                                <span v-for="(m, i) in moveHistory" :key="i"
                                    class="font-mono text-xs bg-zinc-800 text-zinc-300 px-2 py-1 rounded">
                                    <span v-if="i % 2 === 0" class="text-zinc-600 mr-1">{{ Math.floor(i/2)+1 }}.</span>{{ m }}
                                </span>
                            </div>
                        </section>

                        <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-2">Info</h3>
                            <p class="text-xs text-zinc-400">
                                Uzklikšķini figūru, tad tās galamērķi, lai izdarītu gājienu.
                                Spied <span class="text-amber-400 font-bold">Atcelt</span>, lai atgrieztu gājienu,
                                vai <span class="text-amber-400 font-bold">Rediģēt</span>, lai mainītu pozīciju.
                            </p>
                        </section>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
