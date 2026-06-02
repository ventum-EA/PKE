<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { getLegalMoves } from '../services/chess';
import { preloadPieces, getPieceImageUrl } from '../services/pieceImages';

const { t } = useI18n();

const props = defineProps({
    fen: { type: String, default: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' },
    orientation: { type: String, default: 'white' },
    interactive: { type: Boolean, default: true },
    lastMove: { type: Object, default: null },
    highlightSquares: { type: Array, default: () => [] },
    highlightColor: { type: String, default: 'rgba(235, 97, 80, 0.4)' },
    size: { type: Number, default: 400 },
    resizable: { type: Boolean, default: false },
    minSize: { type: Number, default: 280 },
    maxSize: { type: Number, default: 680 },
    ghost: { type: Boolean, default: false },
});

const emit = defineEmits(['move', 'squareClick', 'update:size']);

const selectedSquare = ref(null);
const legalTargets = ref([]);
const showPromotion = ref(false);
const pendingPromotion = ref(null);
const localSize = ref(props.size);
const isResizing = ref(false);

// Drag state
const dragging = ref(null);
const dragPos = ref({ x: 0, y: 0 });
const svgRef = ref(null);

// Piece images
const pieceImages = ref(null);
const useSvgPieces = computed(() => pieceImages.value && pieceImages.value.size > 0);

// CSS filter per piece style — all use same SVG shapes but look different
const PIECE_FILTERS = {
    standard: 'none',
    neo: 'contrast(1.3) brightness(0.95)',
    high_contrast: 'contrast(1.8) brightness(1.1)',
    warm: 'sepia(0.35) saturate(1.3) brightness(1.05)',
};
const pieceFilter = computed(() => PIECE_FILTERS[pieceKey.value] || 'none');

onMounted(async () => {
    try {
        pieceImages.value = await preloadPieces('cburnett');
    } catch {
        // Falls back to Unicode symbols
    }
});

function pieceImgUrl(fenChar) {
    return getPieceImageUrl(pieceImages.value, fenChar);
}

watch(() => props.size, (v) => { localSize.value = v; });

const PIECE_SYMBOLS = {
    K: '♔', Q: '♕', R: '♖', B: '♗', N: '♘', P: '♙',
    k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟',
};

import { useBoardTheme } from '../composables/useBoardTheme';

const { boardColors, pieceColors, pieceKey } = useBoardTheme();

const LIGHT = computed(() => boardColors.value.light);
const DARK = computed(() => boardColors.value.dark);
const SELECTED_LIGHT = '#f7ec5a';
const SELECTED_DARK = '#dac524';
const LAST_MOVE_LIGHT = computed(() => boardColors.value.lastMoveLight || '#cdd16a');
const LAST_MOVE_DARK = computed(() => boardColors.value.lastMoveDark || '#aaa23a');
const LEGAL_DOT = 'rgba(0,0,0,0.15)';
const LEGAL_CAPTURE = 'rgba(0,0,0,0.15)';

const WHITE_PIECE = computed(() => pieceColors.value.white);
const BLACK_PIECE = computed(() => pieceColors.value.black);

const sqSize = computed(() => localSize.value / 8);

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
        return isLight ? LAST_MOVE_LIGHT.value : LAST_MOVE_DARK.value;
    return isLight ? LIGHT.value : DARK.value;
}

function isHighlighted(row, col) { return props.highlightSquares.includes(toAlgebraic(row, col)); }
function isLegalTarget(row, col) { return legalTargets.value.includes(toAlgebraic(row, col)); }
function hasPieceAt(row, col) { return getPiece(row, col) !== null; }

function getPieceAtAlgebraic(sq) {
    const col = sq.charCodeAt(0) - 97;
    const row = 8 - parseInt(sq[1]);
    return board.value[row]?.[col] || null;
}

function isOwnPiece(piece) {
    if (!piece) return false;
    const isWhite = piece === piece.toUpperCase();
    return (turn.value === 'w' && isWhite) || (turn.value === 'b' && !isWhite);
}

function selectSquare(sq) {
    selectedSquare.value = sq;
    const legal = getLegalMoves(props.fen);
    legalTargets.value = legal.filter(m => m.from === sq).map(m => m.to);
}

function tryMove(from, to) {
    const piece = getPieceAtAlgebraic(from);
    const toRow = parseInt(to[1]);
    if (piece && (piece === 'P' || piece === 'p') && (toRow === 8 || toRow === 1)) {
        pendingPromotion.value = { from, to };
        showPromotion.value = true;
        return;
    }
    emit('move', { from, to });
    clearSelection();
}

function clearSelection() {
    selectedSquare.value = null;
    legalTargets.value = [];
}

// Click handling
function handleSquareClick(row, col) {
    if (!props.interactive || dragging.value) return;
    const sq = toAlgebraic(row, col);
    emit('squareClick', sq);

    if (selectedSquare.value) {
        if (legalTargets.value.includes(sq)) {
            tryMove(selectedSquare.value, sq);
        } else {
            const piece = getPiece(row, col);
            if (piece && isOwnPiece(piece)) {
                selectSquare(sq);
            } else {
                clearSelection();
            }
        }
    } else {
        const piece = getPiece(row, col);
        if (piece && isOwnPiece(piece)) {
            selectSquare(sq);
        }
    }
}

// Drag handling
function getSvgCoords(e) {
    const svg = svgRef.value;
    if (!svg) return { x: 0, y: 0 };
    const rect = svg.getBoundingClientRect();
    const clientX = e.clientX ?? e.touches?.[0]?.clientX ?? 0;
    const clientY = e.clientY ?? e.touches?.[0]?.clientY ?? 0;
    const scale = localSize.value / rect.width;
    return {
        x: (clientX - rect.left) * scale,
        y: (clientY - rect.top) * scale,
    };
}

function coordsToSquare(x, y) {
    const col = Math.floor(x / sqSize.value);
    const row = Math.floor(y / sqSize.value);
    if (col < 0 || col > 7 || row < 0 || row > 7) return null;
    return toAlgebraic(row, col);
}

function handlePointerDown(row, col, e) {
    if (!props.interactive) return;
    const piece = getPiece(row, col);
    if (!piece || !isOwnPiece(piece)) return;

    const sq = toAlgebraic(row, col);
    selectSquare(sq);

    const coords = getSvgCoords(e);
    dragging.value = { piece, from: sq, row, col };
    dragPos.value = coords;

    const onMove = (ev) => {
        ev.preventDefault();
        dragPos.value = getSvgCoords(ev);
    };
    const onUp = (ev) => {
        const upCoords = getSvgCoords(ev);
        const targetSq = coordsToSquare(upCoords.x, upCoords.y);

        if (targetSq && targetSq !== sq && legalTargets.value.includes(targetSq)) {
            tryMove(sq, targetSq);
        } else {
            // Keep selection if dropped on same square (click-click mode)
        }

        dragging.value = null;
        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
        document.removeEventListener('pointercancel', onUp);
    };
    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
    document.addEventListener('pointercancel', onUp);
}

function isDraggedPiece(row, col) {
    return dragging.value && dragging.value.row === row && dragging.value.col === col;
}

// Promotion
function promote(piece) {
    showPromotion.value = false;
    if (pendingPromotion.value) {
        emit('move', { ...pendingPromotion.value, promotion: piece });
        pendingPromotion.value = null;
    }
    clearSelection();
}

// Resize
function startResize(e) {
    if (!props.resizable) return;
    e.preventDefault();
    isResizing.value = true;
    const startX = e.clientX ?? e.touches?.[0]?.clientX;
    const startY = e.clientY ?? e.touches?.[0]?.clientY;
    const startSize = localSize.value;
    function onMove(ev) {
        const cx = ev.clientX ?? ev.touches?.[0]?.clientX;
        const cy = ev.clientY ?? ev.touches?.[0]?.clientY;
        const delta = Math.max(cx - startX, cy - startY);
        localSize.value = Math.min(props.maxSize, Math.max(props.minSize, startSize + delta));
        emit('update:size', localSize.value);
    }
    function onUp() {
        isResizing.value = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        document.removeEventListener('touchmove', onMove);
        document.removeEventListener('touchend', onUp);
    }
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
    document.addEventListener('touchmove', onMove, { passive: false });
    document.addEventListener('touchend', onUp);
}

const boardAriaLabel = computed(() => {
    const parts = props.fen.split(' ');
    const turnLabel = parts[1] === 'b' ? t('common.black') : t('common.white');
    const fullmove = parts[5] || '1';
    const orientLabel = props.orientation === 'white' ? t('common.white') : t('common.black');
    return t('board.aria_label', { orientation: orientLabel, move: fullmove, turn: turnLabel });
});

const files = computed(() => {
    const f = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
    return props.orientation === 'white' ? f : [...f].reverse();
});
const ranks = computed(() => {
    const r = ['8', '7', '6', '5', '4', '3', '2', '1'];
    return props.orientation === 'white' ? r : [...r].reverse();
});

watch(() => props.fen, () => { clearSelection(); dragging.value = null; });

const promotionPieces = computed(() => turn.value === 'w' ? ['Q','R','B','N'] : ['q','r','b','n']);
const pieceNames = { Q: 'queen', R: 'rook', B: 'bishop', N: 'knight', q: 'queen', r: 'rook', b: 'bishop', n: 'knight' };
</script>

<template>
    <div class="inline-block relative select-none w-full" :style="{ maxWidth: localSize + 'px' }"
        :class="ghost ? 'ring-2 ring-violet-500/40 rounded-xl' : ''">

        <!-- Ghost board label -->
        <div v-if="ghost"
            class="absolute -top-6 left-0 right-0 text-center text-[10px] font-black uppercase tracking-widest text-violet-400/70 pointer-events-none z-10">
            {{ t('sandbox.label') }}
        </div>

        <svg ref="svgRef" :viewBox="`0 0 ${localSize} ${localSize}`"
            class="w-full h-auto rounded-lg overflow-hidden shadow-xl touch-none"
            :class="ghost ? 'opacity-95' : ''"
            role="img" :aria-label="boardAriaLabel">
            <title>{{ boardAriaLabel }}</title>

            <!-- Squares -->
            <template v-for="row in 8" :key="'r'+row">
                <template v-for="col in 8" :key="'c'+col">
                    <rect
                        :x="(col-1) * sqSize" :y="(row-1) * sqSize"
                        :width="sqSize" :height="sqSize"
                        :fill="squareFill(row-1, col-1)"
                        @click="handleSquareClick(row-1, col-1)"
                        class="cursor-pointer"
                    />
                    <rect v-if="isHighlighted(row-1, col-1)"
                        :x="(col-1) * sqSize" :y="(row-1) * sqSize"
                        :width="sqSize" :height="sqSize"
                        :fill="highlightColor" pointer-events="none"
                    />
                </template>
            </template>

            <!-- Legal move dots -->
            <template v-for="row in 8" :key="'ld'+row">
                <template v-for="col in 8" :key="'ldc'+col">
                    <circle v-if="isLegalTarget(row-1, col-1) && !hasPieceAt(row-1, col-1)"
                        :cx="(col-1) * sqSize + sqSize/2" :cy="(row-1) * sqSize + sqSize/2"
                        :r="sqSize * 0.15" :fill="LEGAL_DOT" pointer-events="none" />
                    <circle v-if="isLegalTarget(row-1, col-1) && hasPieceAt(row-1, col-1)"
                        :cx="(col-1) * sqSize + sqSize/2" :cy="(row-1) * sqSize + sqSize/2"
                        :r="sqSize * 0.45" fill="none" :stroke="LEGAL_CAPTURE" :stroke-width="sqSize * 0.08"
                        pointer-events="none" />
                </template>
            </template>

            <!-- Pieces (static — hidden when being dragged) -->
            <template v-for="row in 8" :key="'p'+row">
                <template v-for="col in 8" :key="'pc'+col">
                    <!-- SVG image piece -->
                    <image v-if="getPiece(row-1, col-1) && !isDraggedPiece(row-1, col-1) && useSvgPieces && pieceImgUrl(getPiece(row-1, col-1))"
                        :x="(col-1) * sqSize + sqSize * 0.05" :y="(row-1) * sqSize + sqSize * 0.05"
                        :width="sqSize * 0.9" :height="sqSize * 0.9"
                        :href="pieceImgUrl(getPiece(row-1, col-1))"
                        :style="pieceFilter !== 'none' ? { filter: pieceFilter } : {}"
                        :class="interactive && isOwnPiece(getPiece(row-1, col-1)) ? 'cursor-grab active:cursor-grabbing' : 'pointer-events-none'"
                        @pointerdown.prevent="handlePointerDown(row-1, col-1, $event)"
                    />
                    <!-- Unicode fallback -->
                    <text v-else-if="getPiece(row-1, col-1) && !isDraggedPiece(row-1, col-1)"
                        :x="(col-1) * sqSize + sqSize/2" :y="(row-1) * sqSize + sqSize/2"
                        text-anchor="middle" dominant-baseline="central"
                        :font-size="sqSize * 0.75"
                        :class="interactive && isOwnPiece(getPiece(row-1, col-1)) ? 'cursor-grab active:cursor-grabbing' : 'pointer-events-none'"
                        :fill="getPiece(row-1, col-1) === getPiece(row-1, col-1).toUpperCase() ? WHITE_PIECE.fill : BLACK_PIECE.fill"
                        :stroke="getPiece(row-1, col-1) === getPiece(row-1, col-1).toUpperCase() ? WHITE_PIECE.stroke : BLACK_PIECE.stroke"
                        :stroke-width="getPiece(row-1, col-1) === getPiece(row-1, col-1).toUpperCase() ? WHITE_PIECE.strokeWidth : BLACK_PIECE.strokeWidth"
                        style="paint-order: stroke;"
                        @pointerdown.prevent="handlePointerDown(row-1, col-1, $event)"
                    >{{ PIECE_SYMBOLS[getPiece(row-1, col-1)] }}</text>
                </template>
            </template>

            <!-- Dragged piece (follows pointer) -->
            <image v-if="dragging && useSvgPieces && pieceImgUrl(dragging.piece)"
                :x="dragPos.x - sqSize * 0.45" :y="dragPos.y - sqSize * 0.45"
                :width="sqSize * 0.9" :height="sqSize * 0.9"
                :href="pieceImgUrl(dragging.piece)"
                class="pointer-events-none"
                :style="{ filter: (pieceFilter !== 'none' ? pieceFilter + ' ' : '') + 'drop-shadow(0 4px 8px rgba(0,0,0,0.5))', opacity: 0.9 }"
            />
            <text v-else-if="dragging"
                :x="dragPos.x" :y="dragPos.y"
                text-anchor="middle" dominant-baseline="central"
                :font-size="sqSize * 0.9"
                class="pointer-events-none"
                :fill="dragging.piece === dragging.piece.toUpperCase() ? WHITE_PIECE.fill : BLACK_PIECE.fill"
                :stroke="dragging.piece === dragging.piece.toUpperCase() ? WHITE_PIECE.stroke : BLACK_PIECE.stroke"
                :stroke-width="dragging.piece === dragging.piece.toUpperCase() ? WHITE_PIECE.strokeWidth : BLACK_PIECE.strokeWidth"
                style="paint-order: stroke; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5)); opacity: 0.9;"
            >{{ PIECE_SYMBOLS[dragging.piece] }}</text>

            <!-- Coordinates -->
            <text v-for="(f, i) in files" :key="'fl'+i"
                :x="i * sqSize + sqSize - 3" :y="localSize - 3"
                :fill="(7 + i) % 2 === 0 ? DARK : LIGHT"
                font-size="10" font-weight="bold" text-anchor="end"
            >{{ f }}</text>
            <text v-for="(r, i) in ranks" :key="'rn'+i"
                x="3" :y="i * sqSize + 13"
                :fill="(i) % 2 === 0 ? DARK : LIGHT"
                font-size="10" font-weight="bold"
            >{{ r }}</text>
        </svg>

        <!-- Resize handle -->
        <div v-if="resizable"
            @mousedown="startResize" @touchstart="startResize"
            class="absolute -bottom-1.5 -right-1.5 w-5 h-5 cursor-nwse-resize z-10 group"
            :title="t('board.resize')">
            <svg viewBox="0 0 20 20" class="w-full h-full text-zinc-500 group-hover:text-amber-400 transition-colors">
                <line x1="14" y1="20" x2="20" y2="14" stroke="currentColor" stroke-width="2"/>
                <line x1="8" y1="20" x2="20" y2="8" stroke="currentColor" stroke-width="2"/>
            </svg>
        </div>

        <!-- Promotion dialog -->
        <div v-if="showPromotion" role="dialog" aria-modal="true" :aria-label="t('board.promotion_choose')"
            class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg z-10">
            <div class="bg-zinc-900 rounded-xl p-3 flex gap-2 shadow-2xl border border-white/10">
                <button v-for="p in promotionPieces" :key="p" type="button"
                    :aria-label="t('board.piece_' + pieceNames[p])"
                    @click="promote(p.toLowerCase())"
                    class="w-14 h-14 hover:bg-amber-500/20 rounded-lg transition-colors flex items-center justify-center">
                    <img v-if="useSvgPieces && pieceImgUrl(p)" :src="pieceImgUrl(p)" class="w-10 h-10" :alt="pieceNames[p]" />
                    <span v-else class="text-4xl">{{ PIECE_SYMBOLS[p] }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
