<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import { useStockfish } from '../services/stockfish';
import { useSounds } from '../composables/useSounds';
import { useNotification } from '../composables/useNotification';
import ChessBoard from '../components/ChessBoard.vue';
import { Chess, makeMove } from '../services/chess';

const { t } = useI18n();
const { boardSize } = useResponsiveBoard({ maxSize: 440, padding: 32 });
const engine = useStockfish();
const { playMove: playMoveSound, playWin, playGameEnd } = useSounds();
const { notify } = useNotification();

const ENDGAMES = [
    { id: 'kq_vs_k', title_lv: 'Karalis + Dāma pret Karali', title_en: 'King + Queen vs King',
      fen: '8/8/8/4k3/8/8/8/4QK2 w - - 0 1', maxMoves: 20, difficulty: 1,
      desc_lv: 'Matējiet ar karali un dāmu. Spiežiet pretinieka karali uz stūri. Uzmanību no pata!',
      desc_en: 'Checkmate with king and queen. Push the enemy king to the corner. Watch out for stalemate!',
      tip_lv: 'Dāma ierobežo, karalis tuvinās. Nekad neradiet patu — atstājiet vienu gājienu!' },
    { id: 'kr_vs_k', title_lv: 'Karalis + Tornis pret Karali', title_en: 'King + Rook vs King',
      fen: '8/8/8/4k3/8/8/8/R3K3 w - - 0 1', maxMoves: 30, difficulty: 2,
      desc_lv: 'Matējiet ar karali un torni. Tehnika: sadaliet galdiņu, tuviniet karali, spiežiet uz malu.',
      desc_en: 'Checkmate with king and rook. Technique: divide the board, bring king closer, push to the edge.',
      tip_lv: 'Tornis sadala galdiņu, karalis tuvojas. Matējiet uz malas, ne stūrī.' },
    { id: 'kr_vs_k_2', title_lv: 'K+T pret K (grūtāka pozīcija)', title_en: 'K+R vs K (harder)',
      fen: '4k3/8/8/8/8/8/8/R3K3 w - - 0 1', maxMoves: 35, difficulty: 2,
      desc_lv: 'Karalis centrā — jāspiež uz malu. Prasīs vairāk gājienu.',
      desc_en: 'King in the center — needs to be pushed to the edge. Will take more moves.',
      tip_lv: 'Nesteigieties. Tornis kontrolē rindu, karalis tuvojas. 15-20 gājieni ir normāli.' },
    { id: 'kbb_vs_k', title_lv: 'Karalis + 2 Laidņi pret Karali', title_en: 'King + 2 Bishops vs King',
      fen: '8/8/8/4k3/8/8/2B1B3/4K3 w - - 0 1', maxMoves: 30, difficulty: 3,
      desc_lv: 'Grūtākā pamata galotne. Abi laidņi kontrolē blakus diagonāles, karalis spiež pretinieku.',
      desc_en: 'The hardest basic endgame. Both bishops control adjacent diagonals, king pushes the opponent.',
      tip_lv: 'Laidņi uz blakus diagonālēm. Spiežiet karali uz stūri, kur mats ir iespējams.' },
    { id: 'kp_vs_k', title_lv: 'Karalis + Bandinieks pret Karali', title_en: 'King + Pawn vs King',
      fen: '8/8/8/8/4k3/8/4P3/4K3 w - - 0 1', maxMoves: 25, difficulty: 2,
      desc_lv: 'Paaugstiniet bandinieku! Iegūstiet opozīciju ar karali PIRMS bandinieka virzīšanas.',
      desc_en: 'Promote the pawn! Gain opposition with the king BEFORE pushing the pawn.',
      tip_lv: 'Ke2 (opozīcija) nevis e4 (bandinieks pirmais). Karalis iet PIRMS bandinieka.' },
];

const selectedEndgame = ref(null);
const game = ref(null);
const displayFen = ref(null);
const moveCount = ref(0);
const gameStatus = ref(null); // null, 'won', 'lost', 'stalemate', 'exceeded'
const isThinking = ref(false);
const lastMove = ref(null);

const orientation = computed(() => 'white');
const isPlaying = computed(() => selectedEndgame.value && !gameStatus.value);

function startEndgame(eg) {
    selectedEndgame.value = eg;
    game.value = new Chess(eg.fen);
    displayFen.value = eg.fen;
    moveCount.value = 0;
    gameStatus.value = null;
    lastMove.value = null;
}

function reset() {
    if (selectedEndgame.value) startEndgame(selectedEndgame.value);
}

function backToList() {
    selectedEndgame.value = null;
    gameStatus.value = null;
}

async function handleMove({ from, to, promotion }) {
    if (!isPlaying.value || isThinking.value) return;
    const result = makeMove(game.value.fen(), from, to, promotion || 'q');
    if (!result) return;

    game.value = new Chess(result.fen);
    displayFen.value = result.fen;
    moveCount.value++;
    lastMove.value = { from, to };
    playMoveSound(result);

    // Check if player won
    if (result.isCheckmate) {
        gameStatus.value = 'won';
        playWin();
        notify('Mats! Jūs uzvarējāt ' + moveCount.value + ' gājienos!', 'success');
        return;
    }
    if (result.isStalemate) {
        gameStatus.value = 'stalemate';
        playGameEnd();
        notify('Pats! Neizšķirts — pretiniekam nebija likumīgu gājienu.', 'error');
        return;
    }
    if (result.isDraw) {
        gameStatus.value = 'stalemate';
        playGameEnd();
        return;
    }

    // Check move limit
    if (moveCount.value >= (selectedEndgame.value?.maxMoves || 30)) {
        gameStatus.value = 'exceeded';
        playGameEnd();
        notify('Pārsniegts gājienu limits! Mēģiniet vēlreiz.', 'error');
        return;
    }

    // Engine response (defending side — low depth for weak play)
    isThinking.value = true;
    try {
        const engineMove = await engine.getMove(result.fen, 2, 500);
        if (engineMove) {
            const eResult = makeMove(game.value.fen(), engineMove.from, engineMove.to, engineMove.promotion);
            if (eResult) {
                game.value = new Chess(eResult.fen);
                displayFen.value = eResult.fen;
                lastMove.value = { from: engineMove.from, to: engineMove.to };
                playMoveSound(eResult);
            }
        }
    } catch { /* engine error */ }
    isThinking.value = false;
}

onUnmounted(() => { engine.stop?.(); });
</script>

<template>
    <div class="min-h-screen p-2 sm:p-6 lg:p-10 text-white">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight mb-1"><span class="text-amber-400">♜</span> {{ $t('nav.endgame_trainer') || 'Galotņu treniņš' }}</h1>
            <p class="text-zinc-500 text-sm mb-6">{{ $t('endgame.subtitle') || 'Praktizējiet galotnes tehnikas pret datoru' }}</p>

            <!-- Endgame selection -->
            <div v-if="!selectedEndgame" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <button v-for="eg in ENDGAMES" :key="eg.id" @click="startEndgame(eg)"
                    class="text-left p-5 bg-zinc-900/50 border border-white/5 rounded-2xl hover:border-amber-500/20 hover:-translate-y-0.5 transition-all group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">♔</span>
                        <div>
                            <p class="font-bold text-sm text-zinc-200 group-hover:text-amber-400 transition-colors">{{ eg.title_lv }}</p>
                            <p class="text-[10px] text-zinc-600">{{ eg.maxMoves }} {{ $t('endgame.max_moves') || 'gājienu limits' }} · {{ '⭐'.repeat(eg.difficulty) }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500">{{ eg.desc_lv }}</p>
                </button>
            </div>

            <!-- Active endgame -->
            <div v-else class="flex flex-col lg:flex-row gap-6">
                <!-- Board -->
                <div class="flex-shrink-0 mx-auto lg:mx-0" :style="{ width: boardSize + 'px', maxWidth: '100%' }">
                    <ChessBoard :fen="displayFen" :orientation="orientation" :interactive="isPlaying && !isThinking"
                        :size="boardSize" :last-move="lastMove" player-color="white" @move="handleMove" />
                </div>

                <!-- Info panel -->
                <div class="flex-1 min-w-0">
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h2 class="font-bold text-lg text-zinc-200 mb-1">{{ selectedEndgame.title_lv }}</h2>
                        <p class="text-xs text-zinc-500 mb-4">{{ selectedEndgame.desc_lv }}</p>

                        <!-- Stats -->
                        <div class="flex gap-4 mb-4 text-xs">
                            <div><span class="text-zinc-600">Gājieni:</span> <span class="font-bold text-amber-400 tabular-nums">{{ moveCount }} / {{ selectedEndgame.maxMoves }}</span></div>
                            <div v-if="isThinking"><span class="text-zinc-600">Dators domā...</span></div>
                        </div>

                        <!-- Tip -->
                        <div class="bg-amber-500/5 border border-amber-500/10 rounded-xl p-3 mb-4">
                            <p class="text-xs text-amber-400/80"><span class="font-bold">💡 Padoms:</span> {{ selectedEndgame.tip_lv }}</p>
                        </div>

                        <!-- Result -->
                        <div v-if="gameStatus" class="mb-4">
                            <div v-if="gameStatus === 'won'" class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-center">
                                <p class="text-lg font-black text-emerald-400">🎉 Mats!</p>
                                <p class="text-xs text-emerald-400/60 mt-1">Jūs matējāt {{ moveCount }} gājienos</p>
                            </div>
                            <div v-else-if="gameStatus === 'stalemate'" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-center">
                                <p class="text-lg font-black text-red-400">⚠ Pats!</p>
                                <p class="text-xs text-red-400/60 mt-1">Neizšķirts — pretiniekam nebija likumīgu gājienu. Atstājiet vienu izeju!</p>
                            </div>
                            <div v-else-if="gameStatus === 'exceeded'" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-center">
                                <p class="text-lg font-black text-red-400">⏱ Gājienu limits!</p>
                                <p class="text-xs text-red-400/60 mt-1">Pārsniedzāt {{ selectedEndgame.maxMoves }} gājienus. Mēģiniet efektīvāk!</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button @click="reset" class="flex-1 py-2.5 text-xs font-bold rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-all">
                                🔄 {{ $t('common.retry') || 'Mēģināt vēlreiz' }}
                            </button>
                            <button @click="backToList" class="flex-1 py-2.5 text-xs font-bold rounded-xl text-zinc-400 border border-white/5 hover:border-white/10 transition-all">
                                ← {{ $t('common.back') || 'Atpakaļ' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
