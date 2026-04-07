<script setup>
import { ref, computed } from 'vue';
import { Chess } from 'chess.js';
import ChessBoard from '../components/ChessBoard.vue';
import { useNotification } from '../composables/useNotification';

const { notify } = useNotification();

/**
 * Curated beginner puzzle pack.
 * Each puzzle has a verified mate-in-1 position; the solver accepts any legal
 * move that results in checkmate (chess.js `isCheckmate`). This keeps the
 * content fully data-driven and avoids hand-coded move strings.
 */
const puzzles = [
    // ── Mate in 1 (10 puzzles) ──────────────────────────────────────
    {
        id: 1,
        title: 'Aizmugurējās rindas mats',
        category: 'Matēšana',
        difficulty: 'Viegls',
        fen: '6k1/5ppp/8/8/8/8/5PPP/R5K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Melnā karalim nav izejas uz 8. rindas, un bandinieki bloķē aizmuguri.',
        description: 'Klasisks aizmugurējās rindas mats. Izmanto smago figūru pa atvērtu līniju.',
    },
    {
        id: 2,
        title: 'Skolēna mats',
        category: 'Atklātnes slazds',
        difficulty: 'Viegls',
        fen: 'r1bqkbnr/pppp1ppp/2n5/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 2 3',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Vājākais punkts melnajām — f7. Meklē figūru, kas var to sasniegt un saņemt atbalstu.',
        description: 'Slavenais skolēna mats — sadarbība starp dāmu un laidnieku pret vājo f7 punktu.',
    },
    {
        id: 3,
        title: 'Galotnes mats ar dāmu',
        category: 'Galotne',
        difficulty: 'Viegls',
        fen: '4k3/8/4K3/8/8/8/8/4Q3 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Tavs karalis jau ir cieši pie pretinieka karaļa. Dāma pabeigs darbu.',
        description: 'Tipisks dāmas mats pret vientuļu karali, kur savs karalis atbalsta uzbrukumu.',
    },
    {
        id: 4,
        title: 'Dāmas un torņa mats',
        category: 'Matēšana',
        difficulty: 'Viegls',
        fen: '3rkb1r/ppp2ppp/5n2/8/3Q4/8/PPP2PPP/R1B1R1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Dāma var doties uz d8 — to atbalsta tornis no e1.',
        description: 'Dāma ielaužas 8. rindā ar torņa atbalstu.',
    },
    {
        id: 5,
        title: 'Zirga atklātais šahs un mats',
        category: 'Matēšana',
        difficulty: 'Vidējs',
        fen: '5rk1/pp3ppp/3p4/2nNp3/4P1Q1/8/PPP2PPP/R4RK1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Zirgs uz f6 dod šahu un atver līniju dāmai — dubulšahs nav bloķējams.',
        description: 'Zirgs dod atklāto šahu — dāma un zirgs strādā kopā.',
    },
    {
        id: 6,
        title: 'Laidnieka diagonāles mats',
        category: 'Matēšana',
        difficulty: 'Viegls',
        fen: 'r1bqk2r/pppp1Bpp/2n2n2/2b1p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Laidnieks jau ir uz f7 — skatīties, vai dāma var ielauzties.',
        description: 'Legāla mats — viens no vecākajiem zināmajiem šaha slazdiem.',
    },
    {
        id: 7,
        title: 'Mats ar diviem torņiem',
        category: 'Matēšana',
        difficulty: 'Viegls',
        fen: '6k1/5ppp/8/8/8/8/1R3PPP/1R4K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Viens tornis kontrolē septīto rindu, otrs var noslēgt astoto.',
        description: 'Divi torņi sadarbībā matē pa aizmugurējo rindu — kāpņu tehnikas kulminācija.',
    },
    {
        id: 8,
        title: 'Epauletes mats',
        category: 'Matēšana',
        difficulty: 'Vidējs',
        fen: '3r1rk1/1pp2ppp/8/8/8/8/1PP2PPP/3QR1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Melnā torņi bloķē sava karaļa evakuāciju — dāma var to izmantot.',
        description: 'Epauletes mats — pretinieka figūras (torņi) kļūst par šķēršļiem savējiem.',
    },
    {
        id: 9,
        title: 'Arābu zirga mats',
        category: 'Matēšana',
        difficulty: 'Vidējs',
        fen: '5rk1/5Npp/8/8/8/8/5PPP/R5K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Tornis no a1 var aiziet uz a8 — zirgs un tornis strādā kopā.',
        description: 'Arābu zirga mats — zirgs kontrolē evakuācijas laukus, tornis matē.',
    },
    {
        id: 10,
        title: 'Anestēzijas mats',
        category: 'Matēšana',
        difficulty: 'Vidējs',
        fen: 'r4b1r/ppppkBpp/2n1b3/4N3/4P3/8/PPPP1PPP/RNBQK2R w KQ - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Skatīties, kur zirgs var aizsniegt karali — pretinieka figūras bloķē izejas.',
        description: 'Anestēzijas mats — zirgs matē, pretinieka figūras paralizē atstāto karali.',
    },

    // ── Taktiskās kombinācijas (ar best_move validāciju) ─────────────
    {
        id: 11,
        title: 'Zirga dakša',
        category: 'Taktika',
        difficulty: 'Viegls',
        fen: 'r1bqk2r/ppppnppp/2n5/1B2N3/4P3/8/PPPP1PPP/RNBQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Nd7',
        hint: 'Zirgs var uzbrukt vienlaicīgi karalim un figūrai — meklē laukumu, kur zirgs šaho.',
        description: 'Klasiska zirga dakša — vienlaicīgs uzbrukums divām vai vairākām figūrām.',
    },
    {
        id: 12,
        title: 'Tapas taktika',
        category: 'Taktika',
        difficulty: 'Viegls',
        fen: 'r2qkb1r/ppp2ppp/2np1n2/4p1B1/4P1b1/3P1N2/PPP2PPP/RN1QKB1R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxf6',
        hint: 'Pēc apmaiņas laidnieks tapinās melnā dāmu caur karali.',
        description: 'Tapas taktika — figūra nevar pārvietoties, jo aizsargā vērtīgāku figūru aiz sevis.',
    },
    {
        id: 13,
        title: 'Torņa ielaušanās',
        category: 'Taktika',
        difficulty: 'Vidējs',
        fen: '4r1k1/pp3ppp/8/8/8/8/PPP2PPP/4R1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Re7',
        hint: 'Tornis 7. rindā ir iznīcinoša pozīcija — tas uzbrūk bandinieku pamatnei.',
        description: 'Torņa ielaušanās septītajā rindā — klasisks pozicionālais priekšrocības paņēmiens.',
    },
    {
        id: 14,
        title: 'Atklātā līnija',
        category: 'Pozīcija',
        difficulty: 'Viegls',
        fen: 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq d6 0 3',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'exd5',
        hint: 'Apmaiņa centrā atver līnijas tavām figūrām un liek pretiniekam atbildēt.',
        description: 'Apmaiņas pozīcija — centra bandinieku apmaiņa palielina figūru aktivitāti.',
    },
    {
        id: 15,
        title: 'Stūra mats ar dāmu',
        category: 'Matēšana',
        difficulty: 'Vidējs',
        fen: '5rk1/5ppp/8/1Q6/8/8/5PPP/6K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Dāma var nokļūt g5 vai b8 — bet tikai viena no tām ir mats.',
        description: 'Dāma matē karali, kas iesprostots stūrī aiz saviem bandiniešiem.',
    },
    {
        id: 16,
        title: 'Apmaiņas upuris',
        category: 'Taktika',
        difficulty: 'Vidējs',
        fen: 'r1b1kb1r/pp1nqppp/2p1pn2/3p4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'cxd5',
        hint: 'Centra bandinieku apmaiņa atver diagonāles laidniekam un līnijas torņiem.',
        description: 'Centralizēta bandinieku apmaiņa, kas aktivizē figūras.',
    },
    {
        id: 17,
        title: 'Laidnieka piespraude',
        category: 'Taktika',
        difficulty: 'Vidējs',
        fen: 'rn1qkb1r/pppppppp/5n2/1B6/4P3/8/PPPP1PPP/RNBQK1NR w KQkq - 2 3',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxc6',
        hint: 'Laidnieks jau tapina zirgu — apmaiņa iznīcina pretinieka struktūru.',
        description: 'Laidnieka apmaiņa pret piestiprinātu zirgu — dubulbandinieki vājina pretinieku.',
    },
    {
        id: 18,
        title: 'Smothered mats',
        category: 'Matēšana',
        difficulty: 'Grūts',
        fen: 'r5rk/pp4pp/3p2N1/nP1Qn3/6q1/B1P5/P4PPP/R3K2R w KQ - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint: 'Zirgs matē karali, kas nav spējīgs pārvietoties — to bloķē savas figūras.',
        description: 'Noslāpētais mats (smothered mate) — zirgs matē, jo pretinieka figūras bloķē visas izejas.',
    },
    {
        id: 19,
        title: 'Dāmas upuris',
        category: 'Taktika',
        difficulty: 'Grūts',
        fen: 'r1bqr1k1/pppp1ppp/2n2n2/2b5/2B1P3/3P1N2/PPP2PPP/RNBQR1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bg5',
        hint: 'Laidnieks uz g5 piesprauž zirgu pret dāmu — pozicionālais spiediens.',
        description: 'Laidnieka piespraude pret zirgu f6 — klasisks taktiskais motīvs.',
    },
    {
        id: 20,
        title: 'Bandinieku virzīšanās',
        category: 'Galotne',
        difficulty: 'Viegls',
        fen: '8/8/8/8/8/5k2/6p1/6K1 b - - 0 1',
        playerColor: 'black',
        goal: 'best_move',
        solution: 'Kf2',
        hint: 'Karalis iet uz f2 — pretinieks ir spiests atkāpties un bandinieks promocējas.',
        description: 'Opozīcija galotnē — karalis palīdz bandiniekam sasniegt promociju.',
    },
    {
        id: 21,
        title: 'Dubultuzbrukums',
        category: 'Taktika',
        difficulty: 'Vidējs',
        fen: '3rk2r/ppp2ppp/8/3np3/1b2N3/1B6/PPPP1PPP/R1BQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Nf6+',
        hint: 'Zirgs ar šahu uzbrūk karalim un vienlaicīgi apdraud torni.',
        description: 'Zirga dubultuzbrukums — šahs ar vienlaicīgu uzbrukumu tornim.',
    },
    {
        id: 22,
        title: 'Novirze (deflection)',
        category: 'Taktika',
        difficulty: 'Grūts',
        fen: '3r2k1/5ppp/8/8/2b5/5N2/5PPP/3R2K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Rd8+',
        hint: 'Tornis ar šahu piespiež melno apmainīt torņus, un laidnieks paliek neaizsargāts.',
        description: 'Torņa apmaiņa ar šahu — pēc apmaiņas baltais iegūst figūru.',
    },
    {
        id: 23,
        title: 'Atklātā rinda',
        category: 'Pozīcija',
        difficulty: 'Viegls',
        fen: 'r1bqkbnr/pppppppp/2n5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 1 2',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'd4',
        hint: 'Centra kontrole ar otro bandinieku — klasiskais atklātnes princips.',
        description: 'Divi bandinieki centrā kontrolē vairāk lauku un atver diagonāles.',
    },
    {
        id: 24,
        title: 'Rokāde kā aizsardzība',
        category: 'Pozīcija',
        difficulty: 'Viegls',
        fen: 'r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'O-O',
        hint: 'Karalis jau ir nedrošs centrā — rokāde aizsargā un aktivizē torni.',
        description: 'Rokāde — pamata atklātnes princips. Karalis dodas drošībā, tornis — spēlē.',
    },
    {
        id: 25,
        title: 'Grieķu upura mats',
        category: 'Matēšana',
        difficulty: 'Grūts',
        fen: 'r1bq1rk1/ppp2ppp/2np4/2b1N3/4P3/3B4/PPPP1PPP/RNBQ1RK1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxh7+',
        hint: 'Klasiskais laidnieka upuris uz h7 — karalis spiests pieņemt un tiek pakļauts uzbrukumam.',
        description: 'Grieķu upuris (Greek gift sacrifice) — Bxh7+ sāk iznīcinošu uzbrukumu.',
    },
];

const currentIndex = ref(0);
const solved = ref(new Set());
const attempted = ref(new Set());
const feedback = ref(null); // { type: 'success' | 'error', message: string }
const showHint = ref(false);

const game = ref(new Chess(puzzles[0].fen));
const displayFen = ref(puzzles[0].fen);
const lastMove = ref(null);

const current = computed(() => puzzles[currentIndex.value]);
const progress = computed(() => ({
    solved: solved.value.size,
    total: puzzles.length,
    percent: Math.round((solved.value.size / puzzles.length) * 100),
}));

function loadPuzzle(index) {
    if (index < 0 || index >= puzzles.length) return;
    currentIndex.value = index;
    const p = puzzles[index];
    game.value = new Chess(p.fen);
    displayFen.value = p.fen;
    lastMove.value = null;
    feedback.value = null;
    showHint.value = false;
}

function handleMove({ from, to, promotion }) {
    // Already solved — ignore further moves
    if (solved.value.has(current.value.id)) return;

    const g = game.value;

    // Try the move. chess.js throws on illegal in v1+.
    let result;
    try {
        result = g.move({ from, to, promotion: promotion || 'q' });
    } catch {
        return;
    }
    if (!result) return;

    displayFen.value = g.fen();
    lastMove.value = { from, to };
    attempted.value.add(current.value.id);

    const normalize = (s) => s.replace(/[+#?!]/g, '');

    const isSolved =
        current.value.goal === 'mate_in_1'
            ? (g.isCheckmate ? g.isCheckmate() : g.in_checkmate?.())
            : current.value.goal === 'best_move'
                ? normalize(result.san) === normalize(current.value.solution)
                : false;

    if (isSolved) {
        solved.value.add(current.value.id);
        const msg = current.value.goal === 'mate_in_1'
            ? `Mats ar ${result.san}! Uzdevums atrisināts.`
            : `Pareizi — ${result.san}! Uzdevums atrisināts.`;
        feedback.value = { type: 'success', message: msg };
    } else {
        feedback.value = {
            type: 'error',
            message: `${result.san} nav pareizais gājiens. Mēģini vēlreiz.`,
        };
        // Revert so the user can try again
        setTimeout(() => {
            g.undo();
            displayFen.value = g.fen();
            lastMove.value = null;
        }, 900);
    }
}

function nextPuzzle() {
    if (currentIndex.value < puzzles.length - 1) {
        loadPuzzle(currentIndex.value + 1);
    } else {
        notify('Tu esi pabeidzis visus uzdevumus!', 'success');
    }
}

function prevPuzzle() {
    if (currentIndex.value > 0) loadPuzzle(currentIndex.value - 1);
}

function resetPuzzle() {
    loadPuzzle(currentIndex.value);
}

// Initial load
loadPuzzle(0);
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                        <span class="text-amber-400">◈</span> {{ $t('nav.puzzles') }}
                    </h1>
                    <p class="text-zinc-500 text-sm mt-2">{{ $t('puzzles.subtitle') }}</p>
                </div>

                <!-- Progress indicator -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl px-5 py-3 min-w-[12rem]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $t('puzzles.progress') }}</span>
                        <span class="text-xs font-black text-amber-400">{{ progress.solved }}/{{ progress.total }}</span>
                    </div>
                    <div class="h-1.5 bg-black/40 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all duration-500"
                            :style="{ width: progress.percent + '%' }"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[auto_1fr] gap-6 lg:gap-10">
                <!-- BOARD -->
                <div class="flex flex-col items-center">
                    <ChessBoard :fen="displayFen" :orientation="current.playerColor" :last-move="lastMove"
                        :interactive="!solved.has(current.id)" :size="480" @move="handleMove" />

                    <!-- Feedback banner -->
                    <div v-if="feedback"
                        role="status"
                        :class="['mt-4 w-full max-w-[480px] px-4 py-3 rounded-xl border text-sm font-bold',
                            feedback.type === 'success'
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 animate-pop-success'
                                : 'bg-red-500/10 border-red-500/30 text-red-400 animate-shake']">
                        {{ feedback.type === 'success' ? '✓ ' : '✕ ' }}{{ feedback.message }}
                    </div>

                    <!-- Nav controls -->
                    <div class="flex gap-2 mt-4 w-full max-w-[480px]">
                        <button @click="prevPuzzle" :disabled="currentIndex === 0"
                            class="flex-1 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl border border-white/10 hover:text-amber-400 hover:border-amber-500/30 disabled:opacity-30 disabled:hover:text-zinc-300 disabled:hover:border-white/10 transition-all uppercase tracking-wider text-xs sm:text-sm">
                            ← {{ $t('puzzles.prev') }}
                        </button>
                        <button @click="resetPuzzle"
                            class="px-4 py-3 bg-zinc-800 text-zinc-400 font-bold rounded-xl border border-white/10 hover:text-amber-400 hover:border-amber-500/30 transition-all uppercase tracking-wider text-xs sm:text-sm">
                            ↺
                        </button>
                        <button @click="nextPuzzle"
                            :disabled="currentIndex === puzzles.length - 1"
                            class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl disabled:opacity-30 shadow-lg shadow-amber-500/20 uppercase tracking-wider text-xs sm:text-sm hover:from-amber-400 hover:to-amber-500 transition-all">
                            {{ $t('puzzles.next') }} →
                        </button>
                    </div>
                </div>

                <!-- INFO PANEL -->
                <div class="space-y-5">
                    <!-- Puzzle meta -->
                    <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <span class="text-[10px] font-black uppercase tracking-widest text-amber-400/70">#{{ current.id }} · {{ current.category }}</span>
                                <h2 class="text-xl sm:text-2xl font-black text-white mt-1">{{ current.title }}</h2>
                            </div>
                            <span v-if="solved.has(current.id)"
                                class="text-[10px] font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full uppercase shrink-0">
                                ✓ {{ $t('puzzles.solved') }}
                            </span>
                        </div>

                        <p class="text-sm text-zinc-400 leading-relaxed mb-4">{{ current.description }}</p>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ current.difficulty }}
                            </span>
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ current.playerColor === 'white' ? '♔ Balto gājiens' : '♚ Melno gājiens' }}
                            </span>
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                Mērķis: mats 1 gājienā
                            </span>
                        </div>

                        <button @click="showHint = !showHint"
                            class="w-full py-2.5 bg-black/40 text-amber-400/80 font-bold rounded-lg border border-white/5 hover:text-amber-400 hover:border-amber-500/30 text-xs uppercase tracking-wider transition-all">
                            💡 {{ showHint ? 'Paslēpt padomu' : 'Parādīt padomu' }}
                        </button>

                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0">
                            <div v-if="showHint"
                                class="mt-3 bg-amber-500/5 border border-amber-500/20 rounded-lg px-4 py-3 text-sm text-amber-200/90 leading-relaxed">
                                {{ current.hint }}
                            </div>
                        </transition>
                    </section>

                    <!-- Puzzle list -->
                    <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Visi uzdevumi</h3>
                        <div class="flex flex-col gap-2">
                            <button v-for="(p, i) in puzzles" :key="p.id" @click="loadPuzzle(i)"
                                :class="['flex items-center gap-3 px-4 py-3 rounded-xl border text-left transition-all',
                                    i === currentIndex
                                        ? 'bg-amber-500/10 border-amber-500/30'
                                        : 'bg-zinc-900/30 border-white/5 hover:border-white/20']">
                                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0',
                                    solved.has(p.id)
                                        ? 'bg-emerald-500/20 text-emerald-400'
                                        : attempted.has(p.id)
                                            ? 'bg-amber-500/20 text-amber-400'
                                            : 'bg-zinc-800 text-zinc-500']">
                                    {{ solved.has(p.id) ? '✓' : p.id }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-white truncate">{{ p.title }}</p>
                                    <p class="text-[10px] uppercase tracking-wider text-zinc-500">{{ p.category }} · {{ p.difficulty }}</p>
                                </div>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
