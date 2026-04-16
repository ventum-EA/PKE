<script setup>
/**
 * OpeningDrill — interactive replay of a canonical opening move sequence.
 *
 * Props:
 *   - opening: { name_lv, eco, moves } — moves is a space-separated SAN string
 *     e.g. "e4 c5 Nf3 d6 d4 cxd4"
 *
 * Emits:
 *   - complete: fired when the user plays every move correctly
 *   - close: fired when the user clicks the close button
 *
 * The drill shows one move at a time. On each step, the user must find the
 * expected move on the board. After 2 wrong attempts a hint appears showing
 * the target square. The opponent's reply (the next move in the sequence)
 * plays automatically after a short pause so the flow feels natural.
 */
import { ref, computed, onMounted, watch } from 'vue';
import { Chess } from 'chess.js';
import ChessBoard from './ChessBoard.vue';

const props = defineProps({
    opening: {
        type: Object,
        required: true,
    },
});
const emit = defineEmits(['complete', 'close']);

// Split the SAN sequence into an array. Handle occasional double spaces.
const sanMoves = computed(() =>
    (props.opening.moves || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
);

const game = ref(null);
const currentFen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
const currentStep = ref(0); // index into sanMoves
const attempts = ref(0);
const feedback = ref(null); // null | 'correct' | 'wrong'
const hintVisible = ref(false);
const lastMove = ref(null); // { from, to } for board highlighting
const isComplete = ref(false);
const isEnginePlaying = ref(false);

const totalMoves = computed(() => sanMoves.value.length);
const expectedSan = computed(() => sanMoves.value[currentStep.value] || null);
const progressPercent = computed(() =>
    totalMoves.value > 0 ? Math.round((currentStep.value / totalMoves.value) * 100) : 0
);

// The side whose turn it is, derived from FEN so it matches chess.js exactly.
const turnColor = computed(() => {
    const parts = currentFen.value.split(' ');
    return parts[1] === 'w' ? 'white' : 'black';
});

// The expected move converted to {from, to} so we can highlight on hint.
const expectedSquares = computed(() => {
    if (!expectedSan.value || !game.value) return null;
    try {
        const probe = new Chess(currentFen.value);
        const move = probe.move(expectedSan.value);
        if (!move) return null;
        return { from: move.from, to: move.to };
    } catch {
        return null;
    }
});

onMounted(() => {
    reset();
});

watch(() => props.opening, () => {
    reset();
});

function reset() {
    game.value = new Chess();
    currentFen.value = game.value.fen();
    currentStep.value = 0;
    attempts.value = 0;
    feedback.value = null;
    hintVisible.value = false;
    lastMove.value = null;
    isComplete.value = false;
    isEnginePlaying.value = false;
}

async function handleMove({ from, to, promotion }) {
    if (isComplete.value || isEnginePlaying.value || !game.value) return;
    if (!expectedSan.value) return;

    // Try to make the move on a probe board to see what SAN it produces.
    const probe = new Chess(currentFen.value);
    let candidate;
    try {
        candidate = probe.move({ from, to, promotion: promotion || 'q' });
    } catch {
        return;
    }
    if (!candidate) return;

    attempts.value += 1;

    // Compare SAN strings (strip check/mate markers from both for leniency)
    const normalize = (s) => s.replace(/[+#?!]/g, '');
    if (normalize(candidate.san) === normalize(expectedSan.value)) {
        // Correct — commit the move on the real game board
        game.value.move(expectedSan.value);
        currentFen.value = game.value.fen();
        lastMove.value = { from: candidate.from, to: candidate.to };
        feedback.value = 'correct';
        hintVisible.value = false;
        currentStep.value += 1;

        if (currentStep.value >= totalMoves.value) {
            isComplete.value = true;
            emit('complete', { attempts: attempts.value });
        } else {
            // Auto-play the opponent's reply after a brief pause, so the user
            // always has "their turn" next.
            setTimeout(playOpponentReply, 650);
        }
    } else {
        feedback.value = 'wrong';
        // Show hint after 2 wrong attempts on this step
        if (attempts.value >= 2) {
            hintVisible.value = true;
        }
        setTimeout(() => {
            if (feedback.value === 'wrong') feedback.value = null;
        }, 1200);
    }
}

function playOpponentReply() {
    if (isComplete.value || !game.value) return;
    const reply = sanMoves.value[currentStep.value];
    if (!reply) return;

    isEnginePlaying.value = true;
    try {
        const probe = new Chess(currentFen.value);
        const move = probe.move(reply);
        if (move) {
            game.value.move(reply);
            currentFen.value = game.value.fen();
            lastMove.value = { from: move.from, to: move.to };
            currentStep.value += 1;
            // Reset per-step counters so attempts are tracked per user move
            attempts.value = 0;

            if (currentStep.value >= totalMoves.value) {
                isComplete.value = true;
                emit('complete', { attempts: attempts.value });
            }
        }
    } finally {
        isEnginePlaying.value = false;
        feedback.value = null;
    }
}

function skipMove() {
    if (!expectedSan.value || !game.value) return;
    try {
        const move = game.value.move(expectedSan.value);
        if (move) {
            currentFen.value = game.value.fen();
            lastMove.value = { from: move.from, to: move.to };
            currentStep.value += 1;
            hintVisible.value = false;
            feedback.value = null;
            attempts.value = 0;

            if (currentStep.value >= totalMoves.value) {
                isComplete.value = true;
                emit('complete', { attempts: 0 });
            } else {
                setTimeout(playOpponentReply, 400);
            }
        }
    } catch {
        // ignore
    }
}
</script>

<template>
    <div role="dialog" aria-modal="true" aria-labelledby="opening-drill-title"
        class="bg-zinc-900/95 border border-amber-500/20 rounded-3xl p-5 sm:p-6">
        <div class="flex items-start justify-between gap-3 mb-5">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-mono text-[10px] font-black bg-amber-500/10 text-amber-400 px-2 py-1 rounded shrink-0">
                        {{ opening.eco || opening.opening_eco }}
                    </span>
                    <h3 id="opening-drill-title" class="text-base sm:text-lg font-black text-white truncate">
                        {{ opening.name_lv || opening.opening_name }}
                    </h3>
                </div>
                <p class="text-[10px] uppercase tracking-wider text-zinc-500 font-bold">
                    Interaktīvs treniņš · atkārto galveno līniju
                </p>
            </div>
            <button type="button" @click="emit('close')"
                aria-label="Aizvērt treniņu"
                class="px-3 py-1.5 text-xs font-bold rounded-lg text-zinc-500 border border-white/5 hover:text-white hover:border-white/20 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                ✕
            </button>
        </div>

        <!-- Progress bar -->
        <div class="mb-5">
            <div class="flex items-center justify-between text-[10px] uppercase tracking-wider text-zinc-500 font-bold mb-2">
                <span>Gājiens {{ Math.min(currentStep + 1, totalMoves) }} / {{ totalMoves }}</span>
                <span>{{ progressPercent }}%</span>
            </div>
            <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-500 ease-out"
                    :style="{ width: progressPercent + '%' }"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[auto_1fr] gap-5 sm:gap-6">
            <!-- Board -->
            <div class="flex flex-col items-center">
                <ChessBoard :fen="currentFen"
                    orientation="white"
                    :last-move="lastMove"
                    :highlight-squares="hintVisible && expectedSquares ? [expectedSquares.from, expectedSquares.to] : []"
                    :interactive="!isComplete && !isEnginePlaying"
                   
                    @move="handleMove" />

                <!-- Feedback banner -->
                <div v-if="feedback === 'correct'" role="status"
                    class="mt-3 w-full max-w-[380px] px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold text-center animate-pop-success">
                    ✓ Pareizi!
                </div>
                <div v-else-if="feedback === 'wrong'" role="alert"
                    class="mt-3 w-full max-w-[380px] px-4 py-2 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold text-center animate-shake">
                    ✕ Nepareizi, mēģini vēlreiz
                </div>
                <div v-else-if="isEnginePlaying"
                    class="mt-3 w-full max-w-[380px] px-4 py-2 rounded-xl bg-zinc-900 border border-white/10 text-zinc-400 text-xs font-bold text-center"
                    role="status" aria-live="polite">
                    Pretinieks spēlē…
                </div>
            </div>

            <!-- Info panel -->
            <div class="flex flex-col gap-3">
                <div v-if="isComplete"
                    class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 animate-pop-success"
                    role="alert">
                    <p class="text-2xl mb-2" aria-hidden="true">🎉</p>
                    <p class="text-sm font-black text-emerald-400 mb-1">Apsveicu! Atklātne izpildīta.</p>
                    <p class="text-xs text-zinc-400">
                        Tu esi veiksmīgi izspēlējis visu {{ totalMoves }} gājienu līniju. Turpini praktizēt, lai to nostiprinātu atmiņā.
                    </p>
                </div>

                <div v-else class="bg-black/30 border border-white/5 rounded-xl p-4">
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500 font-bold mb-2">Tavs uzdevums</p>
                    <p class="text-sm text-zinc-300 mb-3">
                        Spēlē kā <span class="font-black" :class="turnColor === 'white' ? 'text-white' : 'text-zinc-400'">
                            {{ turnColor === 'white' ? 'baltais ♔' : 'melnais ♚' }}
                        </span> un izpildi nākamo līnijas gājienu.
                    </p>
                    <div v-if="hintVisible && expectedSan"
                        class="bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2 mt-3"
                        role="status">
                        <p class="text-[10px] uppercase tracking-wider text-amber-400/70 font-bold mb-1">💡 Padoms</p>
                        <p class="text-xs text-amber-300 font-mono">Pareizais gājiens: <span class="font-black">{{ expectedSan }}</span></p>
                    </div>
                </div>

                <!-- Move history -->
                <div class="bg-black/30 border border-white/5 rounded-xl p-4">
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500 font-bold mb-2">Līnijas gājieni</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(m, i) in sanMoves" :key="i"
                            :class="['font-mono text-[11px] px-2 py-1 rounded transition-all',
                                i < currentStep ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                i === currentStep ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30 ring-1 ring-amber-400/40' :
                                'bg-zinc-800 text-zinc-500 border border-white/5']">
                            <span v-if="i % 2 === 0" class="text-zinc-600 mr-0.5">{{ Math.floor(i / 2) + 1 }}.</span>{{ m }}
                        </span>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex gap-2">
                    <button type="button" @click="reset"
                        class="flex-1 py-2.5 bg-zinc-800 text-zinc-300 font-bold rounded-xl border border-white/10 hover:text-amber-400 hover:border-amber-500/30 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                        ↺ No sākuma
                    </button>
                    <button v-if="!isComplete" type="button" @click="skipMove"
                        class="flex-1 py-2.5 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                        → Izlaist
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
