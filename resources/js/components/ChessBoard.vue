<script setup>
import { ref, computed, watch } from 'vue';
import { getLegalMoves } from '../services/chess';

const props = defineProps({
    fen: { type: String, default: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' },
    orientation: { type: String, default: 'white' }, // 'white' or 'black'
    interactive: { type: Boolean, default: true },
    lastMove: { type: Object, default: null }, // { from: 'e2', to: 'e4' }
    highlightSquares: { type: Array, default: () => [] }, // ['e4', 'e5'] for error marking
    highlightColor: { type: String, default: 'rgba(235, 97, 80, 0.4)' },
    size: { type: Number, default: 400 },
});

const emit = defineEmits(['move', 'squareClick']);

const selectedSquare = ref(null);
const legalTargets = ref([]);
const showPromotion = ref(false);
const promotionSquare = ref(null);
const pendingPromotion = ref(null);

const PIECE_SYMBOLS = {
    K: '♔', Q: '♕', R: '♖', B: '♗', N: '♘', P: '♙',
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

const sqSize = computed(() => props.size / 8);

// Parse FEN into 8x8 grid
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

const turn = computed(() => {
    const parts = props.fen.split(' ');
    return parts[1] || 'w';
});

function toAlgebraic(row, col) {
    const r = props.orientation === 'white' ? row : 7 - row;
    const c = props.orientation === 'white' ? col : 7 - col;
    return String.fromCharCode(97 + c) + (8 - r);
}

function fromAlgebraic(sq) {
    const col = sq.charCodeAt(0) - 97;
    const row = 8 - parseInt(sq[1]);
    if (props.orientation === 'white') return { row, col };
    return { row: 7 - row, col: 7 - col };
}

function getPiece(row, col) {
    const r = props.orientation === 'white' ? row : 7 - row;
    const c = props.orientation === 'white' ? col : 7 - col;
    return board.value[r]?.[c] || null;
}

function squareColor(row, col) {
    return (row + col) % 2 === 0 ? LIGHT : DARK;
}

function squareFill(row, col) {
    const sq = toAlgebraic(row, col);
    const isLight = (row + col) % 2 === 0;

    if (selectedSquare.value === sq) return isLight ? SELECTED_LIGHT : SELECTED_DARK;
    if (props.lastMove && (props.lastMove.from === sq || props.lastMove.to === sq)) {
        return isLight ? LAST_MOVE_LIGHT : LAST_MOVE_DARK;
    }
    return isLight ? LIGHT : DARK;
}

function isHighlighted(row, col) {
    const sq = toAlgebraic(row, col);
    return props.highlightSquares.includes(sq);
}

function isLegalTarget(row, col) {
    const sq = toAlgebraic(row, col);
    return legalTargets.value.includes(sq);
}

function hasPieceAt(row, col) {
    return getPiece(row, col) !== null;
}

function handleSquareClick(row, col) {
    if (!props.interactive) return;
    const sq = toAlgebraic(row, col);

    emit('squareClick', sq);

    if (selectedSquare.value) {
        const from = selectedSquare.value;
        if (legalTargets.value.includes(sq)) {
            // Check if promotion
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

        // Only select own pieces
        const isWhitePiece = piece === piece.toUpperCase();
        if ((turn.value === 'w' && !isWhitePiece) || (turn.value === 'b' && isWhitePiece)) return;

        selectedSquare.value = sq;
        const legal = getLegalMoves(props.fen);
        legalTargets.value = legal.filter(m => m.from === sq).map(m => m.to);
    }
}

function getPieceAtAlgebraic(sq) {
    const col = sq.charCodeAt(0) - 97;
    const row = 8 - parseInt(sq[1]);
    return board.value[row]?.[col] || null;
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

// Coordinate labels
const files = computed(() => {
    const f = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
    return props.orientation === 'white' ? f : [...f].reverse();
});
const ranks = computed(() => {
    const r = ['8', '7', '6', '5', '4', '3', '2', '1'];
    return props.orientation === 'white' ? r : [...r].reverse();
});

// Deselect when FEN changes
watch(() => props.fen, () => {
    selectedSquare.value = null;
    legalTargets.value = [];
});
</script>

<template>
    <div class="inline-block relative select-none" :style="{ width: size + 'px' }">
        <svg :viewBox="`0 0 ${size} ${size}`" :width="size" :height="size" class="rounded-lg overflow-hidden shadow-xl">
            <!-- Squares -->
            <template v-for="row in 8" :key="'r'+row">
                <template v-for="col in 8" :key="'c'+col">
                    <rect
                        :x="(col-1) * sqSize"
                        :y="(row-1) * sqSize"
                        :width="sqSize"
                        :height="sqSize"
                        :fill="squareFill(row-1, col-1)"
                        @click="handleSquareClick(row-1, col-1)"
                        class="cursor-pointer"
                    />
                    <!-- Error highlight overlay -->
                    <rect v-if="isHighlighted(row-1, col-1)"
                        :x="(col-1) * sqSize"
                        :y="(row-1) * sqSize"
                        :width="sqSize"
                        :height="sqSize"
                        :fill="highlightColor"
                        pointer-events="none"
                    />
                </template>
            </template>

            <!-- Legal move dots -->
            <template v-for="row in 8" :key="'ld'+row">
                <template v-for="col in 8" :key="'ldc'+col">
                    <circle v-if="isLegalTarget(row-1, col-1) && !hasPieceAt(row-1, col-1)"
                        :cx="(col-1) * sqSize + sqSize/2"
                        :cy="(row-1) * sqSize + sqSize/2"
                        :r="sqSize * 0.15"
                        :fill="LEGAL_DOT"
                        pointer-events="none"
                    />
                    <!-- Capture ring -->
                    <circle v-if="isLegalTarget(row-1, col-1) && hasPieceAt(row-1, col-1)"
                        :cx="(col-1) * sqSize + sqSize/2"
                        :cy="(row-1) * sqSize + sqSize/2"
                        :r="sqSize * 0.45"
                        fill="none"
                        :stroke="LEGAL_CAPTURE"
                        :stroke-width="sqSize * 0.08"
                        pointer-events="none"
                    />
                </template>
            </template>

            <!-- Pieces -->
            <template v-for="row in 8" :key="'p'+row">
                <template v-for="col in 8" :key="'pc'+col">
                    <text v-if="getPiece(row-1, col-1)"
                        :x="(col-1) * sqSize + sqSize/2"
                        :y="(row-1) * sqSize + sqSize/2"
                        text-anchor="middle"
                        dominant-baseline="central"
                        :font-size="sqSize * 0.75"
                        class="pointer-events-none"
                        :style="{ filter: getPiece(row-1, col-1) === getPiece(row-1, col-1).toLowerCase() ? 'drop-shadow(0 1px 1px rgba(255,255,255,0.3))' : 'drop-shadow(0 1px 2px rgba(0,0,0,0.3))' }"
                    >{{ PIECE_SYMBOLS[getPiece(row-1, col-1)] }}</text>
                </template>
            </template>

            <!-- Coordinates -->
            <text v-for="(f, i) in files" :key="'fl'+i"
                :x="i * sqSize + sqSize - 3"
                :y="size - 3"
                :fill="(7 + i) % 2 === 0 ? DARK : LIGHT"
                font-size="10" font-weight="bold" text-anchor="end"
            >{{ f }}</text>
            <text v-for="(r, i) in ranks" :key="'rn'+i"
                x="3"
                :y="i * sqSize + 13"
                :fill="(i) % 2 === 0 ? DARK : LIGHT"
                font-size="10" font-weight="bold"
            >{{ r }}</text>
        </svg>

        <!-- Promotion dialog -->
        <div v-if="showPromotion"
            class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg z-10">
            <div class="bg-zinc-900 rounded-xl p-3 flex gap-2 shadow-2xl border border-white/10">
                <button v-for="p in (turn === 'w' ? ['Q','R','B','N'] : ['q','r','b','n'])" :key="p"
                    @click="promote(p.toLowerCase())"
                    class="w-14 h-14 text-4xl hover:bg-amber-500/20 rounded-lg transition-colors flex items-center justify-center">
                    {{ PIECE_SYMBOLS[p] }}
                </button>
            </div>
        </div>
    </div>
</template>
