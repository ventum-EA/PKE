<script setup>
import { ref, computed, watch } from 'vue';
import { getLegalMoves } from '../services/chess';

const props = defineProps({
    fen: { type: String, default: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' },
    orientation: { type: String, default: 'white' },
    interactive: { type: Boolean, default: true },
    lastMove: { type: Object, default: null },
    highlightSquares: { type: Array, default: () => [] },
    highlightColor: { type: String, default: 'rgba(235, 97, 80, 0.4)' },
});

const emit = defineEmits(['move', 'squareClick']);

const selectedSquare = ref(null);
const legalTargets = ref([]);
const showPromotion = ref(false);
const pendingPromotion = ref(null);

const PIECE_SYMBOLS = {
    K: '♚', Q: '♛', R: '♜', B: '♝', N: '♞', P: '♟',
    k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟',
};

const LIGHT = '#f0d9b5';
const DARK = '#b58863';
const SELECTED_LIGHT = '#f7ec5a';
const SELECTED_DARK = '#dac524';
const LAST_MOVE_LIGHT = '#cdd16a';
const LAST_MOVE_DARK = '#aaa23a';
const LEGAL_DOT = 'rgba(0,0,0,0.15)';
const LEGAL_CAPTURE = 'rgba(0,0,0,0.15)';

const SQ = 50; // logical square size inside SVG viewBox (400/8)
const BOARD = 400; // logical board size

const board = computed(() => {
    const grid = [];
    const rows = props.fen.split(' ')[0].split('/');
    for (let r = 0; r < 8; r++) {
        const row = [];
        for (const ch of rows[r]) {
            if (ch >= '1' && ch <= '8') {
                for (let i = 0; i < parseInt(ch); i++) row.push(null);
            } else {
                row.push(ch);
            }
        }
        grid.push(row);
    }
    return grid;
});

const turn = computed(() => (props.fen.split(' ')[1] || 'w'));

function toAlgebraic(row, col) {
    const r = props.orientation === 'white' ? row : 7 - row;
    const c = props.orientation === 'white' ? col : 7 - col;
    return String.fromCharCode(97 + c) + (8 - r);
}

function getPiece(row, col) {
    const r = props.orientation === 'white' ? row : 7 - row;
    const c = props.orientation === 'white' ? col : 7 - col;
    return board.value[r]?.[c] || null;
}

function squareFill(row, col) {
    const sq = toAlgebraic(row, col);
    const isLight = (row + col) % 2 === 0;
    if (selectedSquare.value === sq) return isLight ? SELECTED_LIGHT : SELECTED_DARK;
    if (props.lastMove && (props.lastMove.from === sq || props.lastMove.to === sq))
        return isLight ? LAST_MOVE_LIGHT : LAST_MOVE_DARK;
    return isLight ? LIGHT : DARK;
}

function isHighlighted(row, col) {
    return props.highlightSquares.includes(toAlgebraic(row, col));
}

function isLegalTarget(row, col) {
    return legalTargets.value.includes(toAlgebraic(row, col));
}

function hasPieceAt(row, col) { return getPiece(row, col) !== null; }

function getPieceAtAlgebraic(sq) {
    const col = sq.charCodeAt(0) - 97;
    const row = 8 - parseInt(sq[1]);
    return board.value[row]?.[col] || null;
}

function handleSquareClick(row, col) {
    if (!props.interactive) return;
    const sq = toAlgebraic(row, col);
    emit('squareClick', sq);

    if (selectedSquare.value) {
        const from = selectedSquare.value;
        if (legalTargets.value.includes(sq)) {
            const piece = getPieceAtAlgebraic(from);
            const toRow = parseInt(sq[1]);
            if (piece && (piece === 'P' || piece === 'p') && (toRow === 8 || toRow === 1)) {
                pendingPromotion.value = { from, to: sq };
                showPromotion.value = true;
                return;
            }
            emit('move', { from, to: sq });
        }
        selectedSquare.value = null;
        legalTargets.value = [];
    } else {
        const piece = getPiece(row, col);
        if (!piece) return;
        const isWhitePiece = piece === piece.toUpperCase();
        if ((turn.value === 'w' && !isWhitePiece) || (turn.value === 'b' && isWhitePiece)) return;
        selectedSquare.value = sq;
        const legal = getLegalMoves(props.fen);
        legalTargets.value = legal.filter(m => m.from === sq).map(m => m.to);
    }
}

function promote(piece) {
    showPromotion.value = false;
    if (pendingPromotion.value) {
        emit('move', { ...pendingPromotion.value, promotion: piece });
        pendingPromotion.value = null;
    }
    selectedSquare.value = null;
    legalTargets.value = [];
}

const boardAriaLabel = computed(() => {
    const parts = props.fen.split(' ');
    const t = parts[1] === 'b' ? 'melno' : 'balto';
    const fullmove = parts[5] || '1';
    const ori = props.orientation === 'white' ? 'baltā apakšā' : 'melnā apakšā';
    return `Šaha galdiņš, ${ori}. Gājiens ${fullmove}, ${t} gājiens.`;
});

const files = computed(() => {
    const f = ['a','b','c','d','e','f','g','h'];
    return props.orientation === 'white' ? f : [...f].reverse();
});
const ranks = computed(() => {
    const r = ['8','7','6','5','4','3','2','1'];
    return props.orientation === 'white' ? r : [...r].reverse();
});

watch(() => props.fen, () => { selectedSquare.value = null; legalTargets.value = []; });
</script>

<template>
    <div class="chess-board-wrapper">
        <svg viewBox="0 0 400 400" class="chess-board-svg" role="img" :aria-label="boardAriaLabel">
            <title>{{ boardAriaLabel }}</title>

            <!-- Squares -->
            <template v-for="row in 8" :key="'r'+row">
                <template v-for="col in 8" :key="'c'+col">
                    <rect
                        :x="(col-1)*SQ" :y="(row-1)*SQ" :width="SQ" :height="SQ"
                        :fill="squareFill(row-1, col-1)"
                        @click="handleSquareClick(row-1, col-1)"
                        :class="interactive ? 'cursor-pointer' : ''"
                    />
                    <rect v-if="isHighlighted(row-1, col-1)"
                        :x="(col-1)*SQ" :y="(row-1)*SQ" :width="SQ" :height="SQ"
                        :fill="highlightColor" pointer-events="none"
                    />
                </template>
            </template>

            <!-- Legal move indicators -->
            <template v-for="row in 8" :key="'ld'+row">
                <template v-for="col in 8" :key="'ldc'+col">
                    <circle v-if="isLegalTarget(row-1,col-1) && !hasPieceAt(row-1,col-1)"
                        :cx="(col-1)*SQ+SQ/2" :cy="(row-1)*SQ+SQ/2" :r="SQ*0.15"
                        :fill="LEGAL_DOT" pointer-events="none"
                    />
                    <circle v-if="isLegalTarget(row-1,col-1) && hasPieceAt(row-1,col-1)"
                        :cx="(col-1)*SQ+SQ/2" :cy="(row-1)*SQ+SQ/2" :r="SQ*0.45"
                        fill="none" :stroke="LEGAL_CAPTURE" :stroke-width="SQ*0.08"
                        pointer-events="none"
                    />
                </template>
            </template>

            <!-- Pieces -->
            <template v-for="row in 8" :key="'p'+row">
                <template v-for="col in 8" :key="'pc'+col">
                    <text v-if="getPiece(row-1,col-1)"
                        :x="(col-1)*SQ+SQ/2" :y="(row-1)*SQ+SQ/2 + 2"
                        text-anchor="middle" dominant-baseline="central"
                        font-size="38" class="pointer-events-none chess-piece"
                        :class="getPiece(row-1,col-1) === getPiece(row-1,col-1).toLowerCase() ? 'piece-black' : 'piece-white'"
                    >{{ PIECE_SYMBOLS[getPiece(row-1,col-1)] }}</text>
                </template>
            </template>

            <!-- Coordinates -->
            <text v-for="(f,i) in files" :key="'fl'+i"
                :x="i*SQ+SQ-3" :y="BOARD-3"
                :fill="(7+i)%2===0 ? DARK : LIGHT"
                font-size="10" font-weight="bold" text-anchor="end"
            >{{ f }}</text>
            <text v-for="(r,i) in ranks" :key="'rn'+i"
                x="3" :y="i*SQ+13"
                :fill="i%2===0 ? DARK : LIGHT"
                font-size="10" font-weight="bold"
            >{{ r }}</text>
        </svg>

        <!-- Promotion dialog -->
        <div v-if="showPromotion"
            role="dialog" aria-modal="true"
            aria-label="Izvēlies figūru promocēšanai"
            class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg z-10">
            <div class="bg-zinc-900 rounded-xl p-3 flex gap-2 shadow-2xl border border-white/10">
                <button v-for="p in (turn === 'w' ? ['Q','R','B','N'] : ['q','r','b','n'])" :key="p"
                    type="button" @click="promote(p.toLowerCase())"
                    class="w-14 h-14 text-4xl hover:bg-amber-500/20 rounded-lg transition-colors flex items-center justify-center cursor-pointer">
                    {{ PIECE_SYMBOLS[p] }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chess-board-wrapper {
    position: relative;
    width: 100%;
    max-width: min(90vw, 85vh, 800px);
    aspect-ratio: 1 / 1;
    margin: 0 auto;
}
.chess-board-svg {
    width: 100%;
    height: 100%;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
}
.chess-piece {
    user-select: none;
}
.piece-white {
    fill: #ffffff;
    stroke: #1a1a1a;
    stroke-width: 0.8px;
    paint-order: stroke;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));
}
.piece-black {
    fill: #111111;
    stroke: #666666;
    stroke-width: 0.4px;
    paint-order: stroke;
    filter: drop-shadow(0 1px 1px rgba(255,255,255,0.15));
}
</style>
