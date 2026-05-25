<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Chess } from 'chess.js';
import ChessBoard from '../components/ChessBoard.vue';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import { useNotification } from '../composables/useNotification';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import { useLocalized } from '../composables/useLocalized';

const { notify } = useNotification();
const { t } = useI18n();
const auth = useAuthStore();
const loc = useLocalized();
const { boardSize } = useResponsiveBoard({ maxSize: 480, padding: 48 });

const puzzles = [
    {
        id: 1,
        title_lv: 'Aizmugurējās rindas mats',
        title_en: 'Back Rank Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_easy'),
        fen: '6k1/5ppp/8/8/8/8/5PPP/R5K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Melnā karalim nav izejas uz 8. rindas, un bandinieki bloķē aizmuguri.',
        hint_en: 'The black king has no escape on the 8th rank, and pawns block the back row.',
        description_lv: 'Klasisks aizmugurējās rindas mats. Izmanto smago figūru pa atvērtu līniju.',
        description_en: 'Classic back rank mate. Use a heavy piece along an open file.',
    },
    {
        id: 2,
        title_lv: 'Skolēna mats',
        title_en: 'Scholar's Mate',
        category: t('puzzles.cat_trap'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqkbnr/pppp1ppp/2n5/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 2 3',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Vājākais punkts melnajām — f7. Meklē figūru, kas var to sasniegt un saņemt atbalstu.',
        hint_en: 'Black's weakest point is f7. Find a piece that can reach it with support.',
        description_lv: 'Slavenais skolēna mats — sadarbība starp dāmu un laidnieku pret vājo f7 punktu.',
        description_en: 'The famous Scholar's Mate — queen and bishop cooperate against the weak f7 square.',
    },
    {
        id: 3,
        title_lv: 'Galotnes mats ar dāmu',
        title_en: 'Queen Endgame Mate',
        category: t('puzzles.cat_endgame'),
        difficulty: t('puzzles.diff_easy'),
        fen: '4k3/8/4K3/8/8/8/8/4Q3 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Tavs karalis jau ir cieši pie pretinieka karaļa. Dāma pabeigs darbu.',
        hint_en: 'Your king is already close to the opponent's king. The queen finishes the job.',
        description_lv: 'Tipisks dāmas mats pret vientuļu karali, kur savs karalis atbalsta uzbrukumu.',
        description_en: 'Typical queen mate against a lone king, with your king supporting the attack.',
    },
    {
        id: 4,
        title_lv: 'Dāmas un torņa mats',
        title_en: 'Queen and Rook Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_easy'),
        fen: '3rkb1r/ppp2ppp/5n2/8/3Q4/8/PPP2PPP/R1B1R1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Dāma var doties uz d8 — to atbalsta tornis no e1.',
        hint_en: 'The queen can go to d8 — the rook on e1 provides support.',
        description_lv: 'Dāma ielaužas 8. rindā ar torņa atbalstu.',
        description_en: 'The queen invades the 8th rank with rook support.',
    },
    {
        id: 5,
        title_lv: 'Zirga atklātais šahs un mats',
        title_en: 'Knight Discovered Check and Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: '5rk1/pp3ppp/3p4/2nNp3/4P1Q1/8/PPP2PPP/R4RK1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Zirgs uz f6 dod šahu un atver līniju dāmai — dubulšahs nav bloķējams.',
        hint_en: 'Knight to f6 gives check and opens a line for the queen — double check can't be blocked.',
        description_lv: 'Zirgs dod atklāto šahu — dāma un zirgs strādā kopā.',
        description_en: 'The knight delivers a discovered check — queen and knight work together.',
    },
    {
        id: 6,
        title_lv: 'Laidnieka diagonāles mats',
        title_en: 'Bishop Diagonal Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqk2r/pppp1Bpp/2n2n2/2b1p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Laidnieks jau ir uz f7 — skatīties, vai dāma var ielauzties.',
        hint_en: 'The bishop is already on f7 — see if the queen can break through.',
        description_lv: 'Legāla mats — viens no vecākajiem zināmajiem šaha slazdiem.',
        description_en: 'Legal's Mate — one of the oldest known chess traps.',
    },
    {
        id: 7,
        title_lv: 'Mats ar diviem torņiem',
        title_en: 'Two-Rook Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_easy'),
        fen: '6k1/5ppp/8/8/8/8/1R3PPP/1R4K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Viens tornis kontrolē septīto rindu, otrs var noslēgt astoto.',
        hint_en: 'One rook controls the 7th rank, the other can seal the 8th.',
        description_lv: 'Divi torņi sadarbībā matē pa aizmugurējo rindu — kāpņu tehnikas kulminācija.',
        description_en: 'Two rooks cooperating on the back rank — the staircase technique in action.',
    },
    {
        id: 8,
        title_lv: 'Epauletes mats',
        title_en: 'Epaulette Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: '3r1rk1/1pp2ppp/8/8/8/8/1PP2PPP/3QR1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Melnā torņi bloķē sava karaļa evakuāciju — dāma var to izmantot.',
        hint_en: 'Black's own rooks block the king's escape — the queen can exploit this.',
        description_lv: 'Epauletes mats — pretinieka figūras (torņi) kļūst par šķēršļiem savējiem.',
        description_en: 'Epaulette mate — the opponent's pieces (rooks) become obstacles for their own king.',
    },
    {
        id: 9,
        title_lv: 'Arābu zirga mats',
        title_en: 'Arabian Knight Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: '5rk1/5Npp/8/8/8/8/5PPP/R5K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Tornis no a1 var aiziet uz a8 — zirgs un tornis strādā kopā.',
        hint_en: 'The rook from a1 can go to a8 — knight and rook work together.',
        description_lv: 'Arābu zirga mats — zirgs kontrolē evakuācijas laukus, tornis matē.',
        description_en: 'Arabian mate — the knight controls escape squares, the rook delivers mate.',
    },
    {
        id: 10,
        title_lv: 'Anestēzijas mats',
        title_en: 'Anastasia's Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: 'r4b1r/ppppkBpp/2n1b3/4N3/4P3/8/PPPP1PPP/RNBQK2R w KQ - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Skatīties, kur zirgs var aizsniegt karali — pretinieka figūras bloķē izejas.',
        hint_en: 'Look where the knight can reach the king — the opponent's pieces block escapes.',
        description_lv: 'Anestēzijas mats — zirgs matē, pretinieka figūras paralizē atstāto karali.',
        description_en: 'Anastasia's mate — the knight delivers mate while the opponent's pieces paralyze the king.',
    },
    {
        id: 11,
        title_lv: 'Zirga dakša',
        title_en: 'Knight Fork',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqk2r/ppppnppp/2n5/1B2N3/4P3/8/PPPP1PPP/RNBQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Nd7',
        hint_lv: 'Zirgs var uzbrukt vienlaicīgi karalim un figūrai — meklē laukumu, kur zirgs šaho.',
        hint_en: 'The knight can attack the king and a piece simultaneously — find the square where it gives check.',
        description_lv: 'Klasiska zirga dakša — vienlaicīgs uzbrukums divām vai vairākām figūrām.',
        description_en: 'Classic knight fork — simultaneous attack on two or more pieces.',
    },
    {
        id: 12,
        title_lv: 'Tapas taktika',
        title_en: 'Pin Tactic',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r2qkb1r/ppp2ppp/2np1n2/4p1B1/4P1b1/3P1N2/PPP2PPP/RN1QKB1R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxf6',
        hint_lv: 'Pēc apmaiņas laidnieks tapinās melnā dāmu caur karali.',
        hint_en: 'After the exchange, the bishop pins the black queen through the king.',
        description_lv: 'Tapas taktika — figūra nevar pārvietoties, jo aizsargā vērtīgāku figūru aiz sevis.',
        description_en: 'Pin tactic — a piece cannot move because it protects a more valuable piece behind it.',
    },
    {
        id: 13,
        title_lv: 'Torņa ielaušanās',
        title_en: 'Rook Invasion',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_medium'),
        fen: '4r1k1/pp3ppp/8/8/8/8/PPP2PPP/4R1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Re7',
        hint_lv: 'Tornis 7. rindā ir iznīcinoša pozīcija — tas uzbrūk bandinieku pamatnei.',
        hint_en: 'A rook on the 7th rank is devastating — it attacks the pawn base.',
        description_lv: 'Torņa ielaušanās septītajā rindā — klasisks pozicionālais priekšrocības paņēmiens.',
        description_en: 'Rook invasion on the 7th rank — a classic positional advantage technique.',
    },
    {
        id: 14,
        title_lv: 'Atklātā līnija',
        title_en: 'Open File',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq d6 0 3',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'exd5',
        hint_lv: 'Apmaiņa centrā atver līnijas tavām figūrām un liek pretiniekam atbildēt.',
        hint_en: 'Exchanging in the center opens lines for your pieces and forces a response.',
        description_lv: 'Apmaiņas pozīcija — centra bandinieku apmaiņa palielina figūru aktivitāti.',
        description_en: 'Exchange position — central pawn exchanges increase piece activity.',
    },
    {
        id: 15,
        title_lv: 'Stūra mats ar dāmu',
        title_en: 'Corner Queen Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: '5rk1/5ppp/8/1Q6/8/8/5PPP/6K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Dāma var nokļūt g5 vai b8 — bet tikai viena no tām ir mats.',
        hint_en: 'The queen can reach g5 or b8 — but only one of them is mate.',
        description_lv: 'Dāma matē karali, kas iesprostots stūrī aiz saviem bandiniešiem.',
        description_en: 'The queen checkmates a king trapped in the corner behind its own pawns.',
    },
    {
        id: 16,
        title_lv: 'Apmaiņas upuris',
        title_en: 'Exchange Sacrifice',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_medium'),
        fen: 'r1b1kb1r/pp1nqppp/2p1pn2/3p4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'cxd5',
        hint_lv: 'Centra bandinieku apmaiņa atver diagonāles laidniekam un līnijas torņiem.',
        hint_en: 'Central pawn exchange opens diagonals for the bishop and files for the rooks.',
        description_lv: 'Centralizēta bandinieku apmaiņa, kas aktivizē figūras.',
        description_en: 'Centralized pawn exchange that activates pieces.',
    },
    {
        id: 17,
        title_lv: 'Laidnieka piespraude',
        title_en: 'Bishop Pin',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_medium'),
        fen: 'rn1qkb1r/pppppppp/5n2/1B6/4P3/8/PPPP1PPP/RNBQK1NR w KQkq - 2 3',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxc6',
        hint_lv: 'Laidnieks jau tapina zirgu — apmaiņa iznīcina pretinieka struktūru.',
        hint_en: 'The bishop already pins the knight — exchanging destroys the opponent's structure.',
        description_lv: 'Laidnieka apmaiņa pret piestiprinātu zirgu — dubulbandinieki vājina pretinieku.',
        description_en: 'Bishop exchange against a pinned knight — doubled pawns weaken the opponent.',
    },
    {
        id: 18,
        title_lv: 'Noslāpētais mats',
        title_en: 'Smothered Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_hard'),
        fen: 'r5rk/pp4pp/3p2N1/nP1Qn3/6q1/B1P5/P4PPP/R3K2R w KQ - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Zirgs matē karali, kas nav spējīgs pārvietoties — to bloķē savas figūras.',
        hint_en: 'The knight checkmates a king that cannot move — blocked by its own pieces.',
        description_lv: 'Noslāpētais mats (smothered mate) — zirgs matē, jo pretinieka figūras bloķē visas izejas.',
        description_en: 'Smothered mate — the knight delivers checkmate because the opponent's pieces block all escapes.',
    },
    {
        id: 19,
        title_lv: 'Dāmas upuris',
        title_en: 'Queen Sacrifice',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_hard'),
        fen: 'r1bqr1k1/pppp1ppp/2n2n2/2b5/2B1P3/3P1N2/PPP2PPP/RNBQR1K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bg5',
        hint_lv: 'Laidnieks uz g5 piesprauž zirgu pret dāmu — pozicionālais spiediens.',
        hint_en: 'Bishop on g5 pins the knight against the queen — positional pressure.',
        description_lv: 'Laidnieka piespraude pret zirgu f6 — klasisks taktiskais motīvs.',
        description_en: 'Bishop pin against the f6 knight — a classic tactical motif.',
    },
    {
        id: 20,
        title_lv: 'Bandinieku virzīšanās',
        title_en: 'Pawn Advancement',
        category: t('puzzles.cat_endgame'),
        difficulty: t('puzzles.diff_easy'),
        fen: '8/8/8/8/8/5k2/6p1/6K1 b - - 0 1',
        playerColor: 'black',
        goal: 'best_move',
        solution: 'Kf2',
        hint_lv: 'Karalis iet uz f2 — pretinieks ir spiests atkāpties un bandinieks promocējas.',
        hint_en: 'King goes to f2 — the opponent must retreat and the pawn promotes.',
        description_lv: 'Opozīcija galotnē — karalis palīdz bandiniekam sasniegt promociju.',
        description_en: 'Opposition in the endgame — the king helps the pawn reach promotion.',
    },
    {
        id: 21,
        title_lv: 'Dubultuzbrukums',
        title_en: 'Double Attack',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_medium'),
        fen: '3rk2r/ppp2ppp/8/3np3/1b2N3/1B6/PPPP1PPP/R1BQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Nf6+',
        hint_lv: 'Zirgs ar šahu uzbrūk karalim un vienlaicīgi apdraud torni.',
        hint_en: 'The knight checks the king and simultaneously threatens the rook.',
        description_lv: 'Zirga dubultuzbrukums — šahs ar vienlaicīgu uzbrukumu tornim.',
        description_en: 'Knight double attack — check with a simultaneous attack on the rook.',
    },
    {
        id: 22,
        title_lv: 'Novirze (deflection)',
        title_en: 'Deflection',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_hard'),
        fen: '3r2k1/5ppp/8/8/2b5/5N2/5PPP/3R2K1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Rd8+',
        hint_lv: 'Tornis ar šahu piespiež melno apmainīt torņus, un laidnieks paliek neaizsargāts.',
        hint_en: 'The rook forces an exchange with check, leaving the bishop unprotected.',
        description_lv: 'Torņa apmaiņa ar šahu — pēc apmaiņas baltais iegūst figūru.',
        description_en: 'Rook exchange with check — after the trade, white wins a piece.',
    },
    {
        id: 23,
        title_lv: 'Atklātā rinda',
        title_en: 'Open Center',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqkbnr/pppppppp/2n5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 1 2',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'd4',
        hint_lv: 'Centra kontrole ar otro bandinieku — klasiskais atklātnes princips.',
        hint_en: 'Control the center with a second pawn — the classic opening principle.',
        description_lv: 'Divi bandinieki centrā kontrolē vairāk lauku un atver diagonāles.',
        description_en: 'Two pawns in the center control more squares and open diagonals.',
    },
    {
        id: 24,
        title_lv: 'Rokāde kā aizsardzība',
        title_en: 'Castling as Defense',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'O-O',
        hint_lv: 'Karalis jau ir nedrošs centrā — rokāde aizsargā un aktivizē torni.',
        hint_en: 'The king is unsafe in the center — castling provides safety and activates the rook.',
        description_lv: 'Rokāde — pamata atklātnes princips. Karalis dodas drošībā, tornis — spēlē.',
        description_en: 'Castling — a fundamental opening principle. King goes to safety, rook enters the game.',
    },
    {
        id: 25,
        title_lv: 'Grieķu upura mats',
        title_en: 'Greek Gift Sacrifice',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_hard'),
        fen: 'r1bq1rk1/ppp2ppp/2np4/2b1N3/4P3/3B4/PPPP1PPP/RNBQ1RK1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxh7+',
        hint_lv: 'Klasiskais laidnieka upuris uz h7 — karalis spiests pieņemt un tiek pakļauts uzbrukumam.',
        hint_en: 'The classic bishop sacrifice on h7 — the king must accept and faces a devastating attack.',
        description_lv: 'Grieķu upuris (Greek gift sacrifice) — Bxh7+ sāk iznīcinošu uzbrukumu.',
        description_en: 'Greek gift sacrifice — Bxh7+ initiates a devastating attack.',
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
    if (solved.value.has(current.value.id)) return;

    const g = game.value;

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

        if (solved.value.size === puzzles.length) {
            submitPuzzleElo();
        }
    } else {
        feedback.value = {
            type: 'error',
            message: `${result.san} nav pareizais gājiens. Mēģini vēlreiz.`,
        };
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
        notify(t('puzzles.all_complete'), 'success');
    }
}

function prevPuzzle() {
    if (currentIndex.value > 0) loadPuzzle(currentIndex.value - 1);
}

function resetPuzzle() {
    loadPuzzle(currentIndex.value);
}

async function submitPuzzleElo() {
    try {
        const { data } = await api.post('/training/complete', {
            correct: solved.value.size,
            total: puzzles.length,
            category: 'tactical',
            difficulty: 'medium',
        });
        const elo = data?.elo;
        if (elo && elo.change > 0) {
            auth.updateElo(elo.new_elo);
            notify(`ELO +${elo.change} (${elo.new_elo})`, 'success');
        }
    } catch {}
}

loadPuzzle(0);
</script>

<template>
    <div class="min-h-screen p-4 sm:p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
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

            <div class="grid grid-cols-1 lg:grid-cols-[auto_1fr] gap-6 lg:gap-10 justify-items-center lg:justify-items-start">
                <!-- BOARD -->
                <div class="flex flex-col items-center">
                    <ChessBoard :fen="displayFen" :orientation="current.playerColor" :last-move="lastMove"
                        :interactive="!solved.has(current.id)" :size="boardSize" @move="handleMove" />

                    <!-- Feedback banner -->
                    <div v-if="feedback"
                        role="status"
                        :class="['mt-4 w-full max-w-full px-4 py-3 rounded-xl border text-sm font-bold',
                            feedback.type === 'success'
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 animate-pop-success'
                                : 'bg-red-500/10 border-red-500/30 text-red-400 animate-shake']">
                        {{ feedback.type === 'success' ? '✓ ' : '✕ ' }}{{ feedback.message }}
                    </div>

                    <!-- Nav controls -->
                    <div class="flex gap-2 mt-4 w-full max-w-full">
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
                                <h2 class="text-xl sm:text-2xl font-black text-white mt-1">{{ loc(current, 'title') }}</h2>
                            </div>
                            <span v-if="solved.has(current.id)"
                                class="text-[10px] font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full uppercase shrink-0">
                                ✓ {{ $t('puzzles.solved') }}
                            </span>
                        </div>

                        <p class="text-sm text-zinc-400 leading-relaxed mb-4">{{ loc(current, 'description') }}</p>

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
                                {{ loc(current, 'hint') }}
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
                                    <p class="text-sm font-bold text-white truncate">{{ loc(p, 'title') }}</p>
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
