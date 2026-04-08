<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Chess } from 'chess.js';
import ChessBoard from '../components/ChessBoard.vue';
import { useStockfish } from '../services/stockfish';
import { useNotification } from '../composables/useNotification';

const { notify } = useNotification();
const engine = useStockfish();

/**
 * Curated endgame position library. Each entry is a well-known instructional
 * endgame from chess theory. The goal field describes what the user should
 * achieve; the tip is a strategic hint they can reveal on demand.
 *
 * The user plays as the side indicated by `playerColor`; the engine plays
 * the defending side at adjustable skill.
 */
const positions = [
    {
        id: 'kq-vs-k',
        title: 'Karaļa + dāmas mats',
        type: 'Pamata mats',
        difficulty: 'Iesācēju',
        fen: '4k3/8/4K3/8/8/8/8/Q7 w - - 0 1',
        playerColor: 'white',
        goal: 'Matē melno karali ar dāmu un karali. Klasisks pamatmats, ko jāprot katram šahistam.',
        tip: 'Izmanto dāmu, lai noslēgtu karali pie malas, tad pievelc savu karali atbalstam. Uzmanies — pārāk tuvu noliktā dāma var radīt patu (stalemate)!',
    },
    {
        id: 'kr-vs-k',
        title: 'Karaļa + torņa mats',
        type: 'Pamata mats',
        difficulty: 'Iesācēju',
        fen: '4k3/8/4K3/8/8/8/8/R7 w - - 0 1',
        playerColor: 'white',
        goal: 'Matē melno karali ar torni un karali. Pielieto „kāpņu" tehniku.',
        tip: 'Tornis nogriež karaļa pārvietošanos pa rindu, tavs karalis pievelkas tuvāk. Soli pa solim spied pretinieka karali uz dēļa malu.',
    },
    {
        id: 'kbb-vs-k',
        title: 'Divu laidnieku mats',
        type: 'Pamata mats',
        difficulty: 'Vidēji',
        fen: '4k3/8/4K3/8/8/8/8/3BB3 w - - 0 1',
        playerColor: 'white',
        goal: 'Matē ar diviem laidniekiem un karali. Mērķis — iedzīt karali stūrī.',
        tip: 'Divi laidnieki kopā kontrolē divas blakusesošas diagonāles. Pievelc tos kopā kā „W" formu, lai sašaurinātu karaļa lauku.',
    },
    {
        id: 'kp-promotion',
        title: 'Bandinieka promocija',
        type: 'Bandinieku galotne',
        difficulty: 'Iesācēju',
        fen: '8/8/8/3k4/8/8/4P3/4K3 w - - 0 1',
        playerColor: 'white',
        goal: 'Promocē bandinieku par dāmu. Uzvarēsi, ja pareizi izmantosi opozīciju.',
        tip: 'Pirms bandinieka virzīšanas — paņem opozīciju ar savu karali. Karaļa pozīcija ir svarīgāka par bandinieka ātrumu.',
    },
    {
        id: 'opposition',
        title: 'Karaļu opozīcija',
        type: 'Bandinieku galotne',
        difficulty: 'Vidēji',
        fen: '8/8/8/4k3/8/4K3/4P3/8 w - - 0 1',
        playerColor: 'white',
        goal: 'Iegūsti opozīciju un virzi bandinieku uz promociju. Klasisks „triangulācijas" piemērs.',
        tip: 'Opozīcija nozīmē, ka tavs karalis ir tieši pretī pretinieka karalim ar nepāra skaitu lauku starp tiem. Tas, kuram NAV jākustās, ir ieguvis opozīciju.',
    },
    {
        id: 'r-vs-k',
        title: 'Tornis pret karali — uzbrukums',
        type: 'Tornu galotne',
        difficulty: 'Vidēji',
        fen: '7k/8/6K1/8/8/8/8/R7 w - - 0 1',
        playerColor: 'white',
        goal: 'Pabeidz mata uzbrukumu. Karalis jau ir pievilkts — tornis dod izšķirošo gājienu.',
        tip: 'Šeit Ra1-a8 ir mats — pretiniekam nav nevienas evakuācijas lauku. Vienmēr pārliecinies, ka tornis nav jāupurē par tukšu šahu.',
    },
];

// --- Active session state ----------------------------------------------
const selectedPosition = ref(positions[0]);
const playGame = ref(null);
const displayFen = ref(positions[0].fen);
const lastMove = ref(null);
const moveHistory = ref([]);
const status = ref('idle'); // 'idle' | 'playing' | 'engine_thinking' | 'finished'
const result = ref(null);
const showTip = ref(false);
const skillLevel = ref(10); // 0-20
const engineReady = ref(false);

const playerToMove = computed(() => {
    if (!playGame.value) return false;
    return playGame.value.turn() === (selectedPosition.value.playerColor === 'white' ? 'w' : 'b');
});

onMounted(async () => {
    try {
        await engine.init();
        engineReady.value = true;
    } catch (err) {
        notify('Neizdevās ielādēt Stockfish dzinēju', 'error');
    }
    loadPosition(positions[0]);
});

onUnmounted(() => {
    try { engine.stop?.(); } catch {}
});

function loadPosition(pos) {
    selectedPosition.value = pos;
    playGame.value = new Chess(pos.fen);
    displayFen.value = pos.fen;
    lastMove.value = null;
    moveHistory.value = [];
    status.value = 'playing';
    result.value = null;
    showTip.value = false;
}

function resetPosition() {
    if (selectedPosition.value) loadPosition(selectedPosition.value);
}

async function handleMove({ from, to, promotion }) {
    if (status.value !== 'playing' || !playGame.value || !playerToMove.value) return;

    let move;
    try {
        move = playGame.value.move({ from, to, promotion: promotion || 'q' });
    } catch {
        return;
    }
    if (!move) return;

    displayFen.value = playGame.value.fen();
    lastMove.value = { from, to };
    moveHistory.value.push(move.san);

    if (checkGameOver()) return;

    // Engine reply
    status.value = 'engine_thinking';
    try {
        const uci = await engine.getMove(displayFen.value, skillLevel.value, 1500);
        if (!uci) {
            status.value = 'playing';
            return;
        }
        const engineMove = playGame.value.move({
            from: uci.slice(0, 2),
            to: uci.slice(2, 4),
            promotion: uci.length > 4 ? uci[4] : undefined,
        });
        if (engineMove) {
            displayFen.value = playGame.value.fen();
            lastMove.value = { from: engineMove.from, to: engineMove.to };
            moveHistory.value.push(engineMove.san);
        }
    } catch {
        notify('Dzinēja kļūda', 'error');
    } finally {
        status.value = 'playing';
        checkGameOver();
    }
}

function checkGameOver() {
    if (!playGame.value) return false;
    const g = playGame.value;
    const isMate = g.isCheckmate ? g.isCheckmate() : g.in_checkmate?.();
    const isDraw = g.isDraw ? g.isDraw() : g.in_draw?.();
    const isStalemate = g.isStalemate ? g.isStalemate() : g.in_stalemate?.();

    if (isMate) {
        status.value = 'finished';
        // Whoever JUST moved delivered the mate. If it's now the player's turn, the engine mated us.
        const playerWon = g.turn() !== (selectedPosition.value.playerColor === 'white' ? 'w' : 'b');
        result.value = playerWon ? 'win' : 'loss';
        return true;
    }
    if (isStalemate) {
        status.value = 'finished';
        result.value = 'stalemate';
        return true;
    }
    if (isDraw) {
        status.value = 'finished';
        result.value = 'draw';
        return true;
    }
    return false;
}

const resultMessage = computed(() => {
    if (!result.value) return null;
    return {
        win: { text: '✓ Lieliski! Tu uzvarēji.', class: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 animate-pop-success' },
        loss: { text: '✕ Stockfish tevi pārspēja. Mēģini vēlreiz!', class: 'bg-red-500/10 border-red-500/30 text-red-400 animate-shake' },
        stalemate: { text: '⚠ Pats! Uzmanies — pārāk tuvi karaļi var sasiet pretinieku.', class: 'bg-amber-500/10 border-amber-500/30 text-amber-400 animate-fade-in' },
        draw: { text: '= Neizšķirts. Mēģini vēlreiz precīzāk.', class: 'bg-blue-500/10 border-blue-500/30 text-blue-400 animate-fade-in' },
    }[result.value];
});
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                    <span class="text-amber-400">♔</span> {{ $t('nav.endgame') }}
                </h1>
                <p class="text-zinc-500 text-sm mt-2">{{ $t('endgame.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[auto_1fr] gap-6 lg:gap-10">
                <!-- BOARD -->
                <div class="flex flex-col items-center">
                    <ChessBoard :fen="displayFen" :orientation="selectedPosition.playerColor"
                        :last-move="lastMove" :interactive="status === 'playing' && playerToMove"
                        :size="480" @move="handleMove" />

                    <!-- Status / result -->
                    <div v-if="status === 'engine_thinking'"
                        class="mt-4 w-full max-w-[480px] px-4 py-3 rounded-xl bg-zinc-900/60 border border-white/10 text-sm font-bold text-zinc-300 text-center"
                        role="status" aria-live="polite">
                        <span class="inline-block w-2 h-2 bg-amber-400 rounded-full animate-pulse mr-2"></span>
                        {{ $t('endgame.engine_thinking') }}
                    </div>

                    <div v-if="resultMessage"
                        :class="['mt-4 w-full max-w-[480px] px-4 py-3 rounded-xl border text-sm font-bold text-center', resultMessage.class]"
                        role="alert">
                        {{ resultMessage.text }}
                    </div>

                    <!-- Controls -->
                    <div class="flex gap-2 mt-4 w-full max-w-[480px]">
                        <button @click="resetPosition"
                            class="flex-1 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl border border-white/10 hover:text-amber-400 hover:border-amber-500/30 transition-all uppercase tracking-wider text-xs sm:text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            ↺ {{ $t('endgame.restart') }}
                        </button>
                    </div>

                    <!-- Skill -->
                    <div class="mt-4 w-full max-w-[480px] bg-zinc-900/50 border border-white/5 rounded-xl p-3 flex items-center gap-3">
                        <label for="endgame-skill" class="text-[10px] uppercase tracking-wider text-zinc-500 font-bold shrink-0">
                            {{ $t('endgame.skill_level') }}
                        </label>
                        <input id="endgame-skill" v-model.number="skillLevel" type="range" min="0" max="20" step="1"
                            class="flex-1 accent-amber-500" />
                        <span class="text-sm font-black text-amber-400 w-8 text-right">{{ skillLevel }}</span>
                    </div>
                </div>

                <!-- INFO PANEL -->
                <div class="space-y-5">
                    <!-- Position meta -->
                    <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6"
                        aria-labelledby="endgame-current-heading">
                        <span class="text-[10px] font-black uppercase tracking-widest text-amber-400/70">{{ selectedPosition.type }}</span>
                        <h2 id="endgame-current-heading" class="text-xl sm:text-2xl font-black text-white mt-1">
                            {{ selectedPosition.title }}
                        </h2>

                        <p class="text-sm text-zinc-400 leading-relaxed mt-3">{{ selectedPosition.goal }}</p>

                        <div class="flex flex-wrap gap-2 mt-4 mb-4">
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ selectedPosition.difficulty }}
                            </span>
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ selectedPosition.playerColor === 'white' ? '♔ Tu spēlē ar baltajām' : '♚ Tu spēlē ar melnajām' }}
                            </span>
                        </div>

                        <button @click="showTip = !showTip" type="button"
                            :aria-expanded="showTip" :aria-controls="`tip-${selectedPosition.id}`"
                            class="w-full py-2.5 bg-black/40 text-amber-400/80 font-bold rounded-lg border border-white/5 hover:text-amber-400 hover:border-amber-500/30 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            💡 {{ showTip ? $t('endgame.hide_tip') : $t('endgame.show_tip') }}
                        </button>
                        <div v-if="showTip" :id="`tip-${selectedPosition.id}`"
                            class="mt-3 bg-amber-500/5 border border-amber-500/20 rounded-lg px-4 py-3 text-sm text-amber-200/90 leading-relaxed">
                            {{ selectedPosition.tip }}
                        </div>
                    </section>

                    <!-- Move history -->
                    <section v-if="moveHistory.length > 0" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6"
                        aria-labelledby="endgame-history-heading">
                        <h3 id="endgame-history-heading" class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">
                            {{ $t('endgame.move_history') }}
                        </h3>
                        <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto">
                            <span v-for="(m, i) in moveHistory" :key="i"
                                class="font-mono text-xs bg-zinc-800 text-zinc-300 px-2 py-1 rounded">
                                <span v-if="i % 2 === 0" class="text-zinc-600 mr-1">{{ Math.floor(i / 2) + 1 }}.</span>{{ m }}
                            </span>
                        </div>
                    </section>

                    <!-- Position picker -->
                    <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6"
                        aria-labelledby="endgame-library-heading">
                        <h3 id="endgame-library-heading" class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">
                            {{ $t('endgame.library') }}
                        </h3>
                        <ul class="flex flex-col gap-2" role="list">
                            <li v-for="p in positions" :key="p.id">
                                <button @click="loadPosition(p)" type="button"
                                    :aria-current="p.id === selectedPosition.id ? 'true' : undefined"
                                    :class="['w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60',
                                        p.id === selectedPosition.id
                                            ? 'bg-amber-500/10 border-amber-500/30'
                                            : 'bg-zinc-900/30 border-white/5 hover:border-white/20']">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white truncate">{{ p.title }}</p>
                                        <p class="text-[10px] uppercase tracking-wider text-zinc-500">{{ p.type }} · {{ p.difficulty }}</p>
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
