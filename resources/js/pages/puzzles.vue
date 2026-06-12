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
        title_en: 'Scholar\'s Mate',
        category: t('puzzles.cat_trap'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqkbnr/pppp1ppp/2n5/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 2 3',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Vājākais punkts melnajām — f7. Meklē figūru, kas var to sasniegt un saņemt atbalstu.',
        hint_en: 'Black\'s weakest point is f7. Find a piece that can reach it with support.',
        description_lv: 'Slavenais skolēna mats — sadarbība starp dāmu un laidnieku pret vājo f7 punktu.',
        description_en: 'The famous Scholar\'s Mate — queen and bishop cooperate against the weak f7 square.',
    },
    {
        id: 3,
        title_lv: 'Galotnes mats ar dāmu',
        title_en: 'Queen Endgame Mate',
        category: t('puzzles.cat_endgame'),
        difficulty: t('puzzles.diff_easy'),
        fen: '2k5/8/2K5/8/8/8/8/4Q3 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Tavs karalis jau ir cieši pie pretinieka karaļa. Dāma pabeigs darbu.',
        hint_en: 'Your king is already close to the opponent\'s king. The queen finishes the job.',
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
        fen: '6rk/6pp/8/6N1/8/8/8/R5K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Zirgs uz f6 dod šahu un atver līniju dāmai — dubulšahs nav bloķējams.',
        hint_en: 'Knight to f6 gives check and opens a line for the queen — double check can\'t be blocked.',
        description_lv: 'Zirgs dod atklāto šahu — dāma un zirgs strādā kopā.',
        description_en: 'The knight delivers a discovered check — queen and knight work together.',
    },
    {
        id: 6,
        title_lv: 'Itāļu spēle — centra kontrole',
        title_en: 'Italian Game — Center Control',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'd4',
        hint_lv: 'd4 uzbrūk centra bandiniekam un atver diagonāles laidniekam.',
        hint_en: 'd4 attacks the center pawn and opens diagonals for the bishop.',
        description_lv: 'Itāļu spēlē d4 ir galvenais gājiens — centra kontrole un figūru aktivizācija.',
        description_en: 'In the Italian Game, d4 is the key move — center control and piece activation.',
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
        fen: '3rkr2/8/4Q3/8/8/8/8/4K3 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Melnā torņi bloķē sava karaļa evakuāciju — dāma var to izmantot.',
        hint_en: 'Black\'s own rooks block the king\'s escape — the queen can exploit this.',
        description_lv: 'Epauletes mats — pretinieka figūras (torņi) kļūst par šķēršļiem savējiem.',
        description_en: 'Epaulette mate — the opponent\'s pieces (rooks) become obstacles for their own king.',
    },
    {
        id: 9,
        title_lv: 'Arābu zirga mats',
        title_en: 'Arabian Knight Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: 'k7/8/2N5/8/8/8/8/R3K3 w Q - 0 1',
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
        title_en: 'Anastasia\'s Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_medium'),
        fen: '4k3/4p3/3NK3/8/8/8/8/7R w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Skatīties, kur zirgs var aizsniegt karali — pretinieka figūras bloķē izejas.',
        hint_en: 'Look where the knight can reach the king — the opponent\'s pieces block escapes.',
        description_lv: 'Anestēzijas mats — zirgs matē, pretinieka figūras paralizē atstāto karali.',
        description_en: 'Anastasia\'s mate — the knight delivers mate while the opponent\'s pieces paralyze the king.',
    },
    {
        id: 11,
        title_lv: 'Zirga dakša',
        title_en: 'Knight Fork',
        category: t('puzzles.cat_tactics'),
        difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/2N2N2/PPPP1PPP/R1BQK2R w KQkq - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxc6',
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
        fen: '6k1/5ppp/8/8/8/8/5PPP/Q5K1 w - - 0 1',
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
        fen: 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxc6',
        hint_lv: 'Laidnieks jau tapina zirgu — apmaiņa iznīcina pretinieka struktūru.',
        hint_en: 'The bishop already pins the knight — exchanging destroys the opponent\'s structure.',
        description_lv: 'Laidnieka apmaiņa pret piestiprinātu zirgu — dubulbandinieki vājina pretinieku.',
        description_en: 'Bishop exchange against a pinned knight — doubled pawns weaken the opponent.',
    },
    {
        id: 18,
        title_lv: 'Noslāpētais mats',
        title_en: 'Smothered Mate',
        category: t('puzzles.cat_mate'),
        difficulty: t('puzzles.diff_hard'),
        fen: '6rk/6pp/8/6N1/8/8/8/6K1 w - - 0 1',
        playerColor: 'white',
        goal: 'mate_in_1',
        hint_lv: 'Zirgs matē karali, kas nav spējīgs pārvietoties — to bloķē savas figūras.',
        hint_en: 'The knight checkmates a king that cannot move — blocked by its own pieces.',
        description_lv: 'Noslāpētais mats (smothered mate) — zirgs matē, jo pretinieka figūras bloķē visas izejas.',
        description_en: 'Smothered mate — the knight delivers checkmate because the opponent\'s pieces block all escapes.',
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
        fen: '8/8/8/8/8/5k2/6p1/3K4 b - - 0 1',
        playerColor: 'black',
        goal: 'best_move',
        solution: 'Ke3',
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
        solution: 'Rxd8+',
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
        fen: 'r1bq1rk1/ppp2ppp/2n1p3/3p4/3P4/3B1N2/PPP2PPP/RNBQ1RK1 w - - 0 1',
        playerColor: 'white',
        goal: 'best_move',
        solution: 'Bxh7+',
        hint_lv: 'Klasiskais laidnieka upuris uz h7 — karalis spiests pieņemt un tiek pakļauts uzbrukumam.',
        hint_en: 'The classic bishop sacrifice on h7 — the king must accept and faces a devastating attack.',
        description_lv: 'Grieķu upuris (Greek gift sacrifice) — Bxh7+ sāk iznīcinošu uzbrukumu.',
        description_en: 'Greek gift sacrifice — Bxh7+ initiates a devastating attack.',
    },

    // ═══ NEW PUZZLES 26–40 ═══
    {
        id: 26, title_lv: 'Torņa ielaušanās mats', title_en: 'Rook Back Rank Invasion',
        category: t('puzzles.cat_mate'), difficulty: t('puzzles.diff_easy'),
        fen: '6k1/5ppp/8/8/8/6P1/5P1P/4R1K1 w - - 0 1', playerColor: 'white', goal: 'mate_in_1',
        hint_lv: 'Tornis e1 var ielauzties pa e-līniju. Melnā karalis ir ieslodzīts aiz saviem bandiniekiem f7, g7, h7.',
        hint_en: 'The rook on e1 can invade along the e-file. Black\'s king is trapped behind its own pawns f7, g7, h7.',
        description_lv: 'Aizmugurējās rindas mats: tornis dodas uz e8, un melnā karalis nevar aizbēgt, jo viņa paša bandinieki bloķē atkāpšanās laukumus. Šis motīvs parādās ļoti bieži — vienmēr pārliecinieties, ka jūsu karalim ir "lodziņš" (h3 vai g3 baltajam, h6 vai g6 melnajam).',
        description_en: 'Back rank mate: the rook goes to e8, and the black king cannot escape because its own pawns block the retreat squares. This pattern appears very often — always make sure your king has a "luft" (h3 or g3 for White, h6 or g6 for Black).',
    },
    {
        id: 27, title_lv: 'Dāmas mats stūrī', title_en: 'Queen Corner Mate',
        category: t('puzzles.cat_mate'), difficulty: t('puzzles.diff_easy'),
        fen: '1k6/8/1K6/8/8/8/8/7Q w - - 0 1', playerColor: 'white', goal: 'mate_in_1',
        hint_lv: 'Melnā karalis ir stūra tuvumā uz b8. Jūsu karalis b6 kontrolē a7, b7, c7. Kur dāma var dot matu?',
        hint_en: 'Black\'s king is near the corner on b8. Your king on b6 controls a7, b7, c7. Where can the queen deliver mate?',
        description_lv: 'Karalis un dāma kopā dzen pretinieka karali uz stūri. Galotnes pamati: dāma viena nevar dot matu — vienmēr nepieciešams karaļa atbalsts. Šeit Db7# vai Dh8# — dāma kontrolē visu rindu vai diagonāli, kamēr karalis bloķē atkāpšanos.',
        description_en: 'King and queen together drive the enemy king to the corner. Endgame basics: the queen alone cannot deliver mate — the king\'s support is always needed. Here Qb7# or Qh8# — the queen controls the rank or diagonal while the king blocks escape.',
    },
    {
        id: 28, title_lv: 'Zirga dakša ar šahu', title_en: 'Knight Fork with Check',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_medium'),
        fen: 'r1b2rk1/ppppqppp/2n5/8/2B1N3/8/PPP2PPP/R2Q1RK1 w - - 0 1', playerColor: 'white', goal: 'best_move', solution: 'Nf6+',
        hint_lv: 'Zirgs e4 var lēkt uz f6 ar šahu. Kas vēl atrodas šī zirga "tuvumā" pēc gājiena?',
        hint_en: 'The knight on e4 can jump to f6 with check. What else is within the knight\'s reach after that move?',
        description_lv: 'Zirga dakša ar šahu: Zf6+ dod šahu karalim g8 un vienlaikus uzbrūk dāmai e7 (un tornim f8!). Karalis OBLIGĀTI jāpārvieto, un zirgs nākamajā gājienā sit dāmu. Dakša ar šahu ir visspēcīgākā — pretiniekam nav izvēles, jāreaģē uz šahu.',
        description_en: 'Knight fork with check: Nf6+ gives check to the king on g8 and simultaneously attacks the queen on e7 (and the rook on f8!). The king MUST move, and the knight captures the queen next. A fork with check is the most powerful — the opponent has no choice but to deal with the check.',
    },
    {
        id: 29, title_lv: 'Torņu kāpņu mats', title_en: 'Rook Ladder Mate',
        category: t('puzzles.cat_mate'), difficulty: t('puzzles.diff_easy'),
        fen: '6k1/8/6K1/8/8/8/R7/R7 w - - 0 1', playerColor: 'white', goal: 'mate_in_1',
        hint_lv: 'Jums ir divi torņi un karalis blakus pretinieka karalim. Viens tornis var aizvērt pēdējo izeju.',
        hint_en: 'You have two rooks and a king next to the enemy king. One rook can close the last escape.',
        description_lv: 'Kāpņu mats ar diviem torņiem: viens tornis atrodas uz a2 (kontrolē otro rindu kā "barjeru"), otrs tornis dodas uz a8 — MATS. Divu torņu mats ir viena no pirmajām tehnikām, ko jāapgūst: torņi pārmaiņus virzās pa rindām, spiežot karali uz malu.',
        description_en: 'Ladder mate with two rooks: one rook is on a2 (controlling the 2nd rank as a "barrier"), the other rook goes to a8 — MATE. The two-rook mate is one of the first techniques to learn: rooks alternately advance along ranks, pushing the king to the edge.',
    },
    {
        id: 30, title_lv: 'Karalis + Dāma mats', title_en: 'King + Queen Mate',
        category: t('puzzles.cat_mate'), difficulty: t('puzzles.diff_easy'),
        fen: 'k7/8/1K6/8/8/8/8/7Q w - - 0 1', playerColor: 'white', goal: 'mate_in_1',
        hint_lv: 'Melnā karalis ir stūrī uz a8. Jūsu karalis kontrolē b7. Dāma var dot matu no vairākiem virzieniem.',
        hint_en: 'Black\'s king is in the corner on a8. Your king controls b7. The queen can deliver mate from several directions.',
        description_lv: 'Galotnes pamati: karalis + dāma vienmēr var dot matu vientuļam karalim. Tehnika: (1) ierobežojiet pretinieka karali ar dāmu, (2) tuviniet savu karali, (3) spiežat karali uz malu un stūri, (4) matējiet. Nekad neradiet patu — atstājiet pretinieka karalim vismaz vienu likumīgu gājienu, līdz esat gatavs matēt.',
        description_en: 'Endgame basics: king + queen can always checkmate a lone king. Technique: (1) restrict the enemy king with the queen, (2) bring your king closer, (3) push the king to the edge and corner, (4) deliver mate. Never create stalemate — leave the enemy king at least one legal move until you\'re ready to mate.',
    },
    {
        id: 31, title_lv: 'Zirga dakša: Karalis + Tornis', title_en: 'Knight Fork: King + Rook',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_medium'),
        fen: 'r3k2r/ppp2ppp/3p4/4N3/2B5/8/PPP2PPP/R3K2R w KQkq - 0 1', playerColor: 'white', goal: 'best_move', solution: 'Nxf7',
        hint_lv: 'Zirgs e5 var lēkt uz f7 — sitiens ar dakšu. Kas atrodas ap f7 laukumu?',
        hint_en: 'The knight on e5 can jump to f7 — a capture with a fork. What\'s around the f7 square?',
        description_lv: 'Klasiskā zirga dakša: Zxf7 sit bandinieku un vienlaikus uzbrūk karalim e8 UN tornim h8. Karalis jāpārvieto (zaudē rokādes tiesības), un zirgs sit torni. Ieguvums: bandinieks + tornis par neko. Vienmēr meklējiet zirga dakšas — tās ir visbiežākais taktiskais motīvs!',
        description_en: 'Classic knight fork: Nxf7 captures the pawn and simultaneously attacks the king on e8 AND the rook on h8. The king must move (loses castling rights), and the knight takes the rook. Gain: pawn + rook for nothing. Always look for knight forks — they\'re the most common tactical motif!',
    },
    {
        id: 32, title_lv: 'Atklātais uzbrukums', title_en: 'Discovered Attack',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_medium'),
        fen: 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4', playerColor: 'white', goal: 'best_move', solution: 'Bxf7+',
        hint_lv: 'Laidnis c4 var sist f7 bandinieku ar šahu! Pēc karaļa gājiena — ko laidnis ir "atklājis"?',
        hint_en: 'The bishop on c4 can capture the f7 pawn with check! After the king moves — what has the bishop "uncovered"?',
        description_lv: 'Lxf7+ sit bandinieku un dod šahu. Melnā karalis zaudē rokādes tiesības (jāiet uz f8 vai e7). Turklāt — laidnis tagad "redz" jaunu diagonāli. Šī ir f7 laukuma vājuma izmantošana — f7 (melnajam) un f2 (baltajam) ir vājākie laukumi spēles sākumā, jo tos aizsargā TIKAI karalis.',
        description_en: 'Bxf7+ captures the pawn and gives check. Black\'s king loses castling rights (must go to f8 or e7). Moreover — the bishop now "sees" a new diagonal. This exploits the f7 weakness — f7 (for Black) and f2 (for White) are the weakest squares at the start because ONLY the king defends them.',
    },
    {
        id: 33, title_lv: 'Aizsarga likvidēšana', title_en: 'Remove the Defender',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_medium'),
        fen: 'r2qkb1r/ppp2ppp/2n1bn2/3pp1B1/3PP3/2N2N2/PPP2PPP/R2QKB1R w KQkq - 0 1', playerColor: 'white', goal: 'best_move', solution: 'Bxf6',
        hint_lv: 'Zirgs f6 aizsargā d5. Laidnis g5 var sist šo zirgu — kas notiek ar d5 pēc tam?',
        hint_en: 'The knight on f6 defends d5. The bishop on g5 can capture this knight — what happens to d5 afterwards?',
        description_lv: 'Aizsarga likvidēšana: Lxf6 sit zirgu, kas aizsargāja d5 bandinieku. Pēc gxf6 (bandinieku struktūra sabojāta!) d5 vairs nav aizsargāts un baltais var to sist ar exd5. Papildu ieguvums: melnā bandinieki f6+f7 ir dubultoti un vāji. Vienmēr pajautājiet: "kas aizsargā šo figūru? Vai es varu likvidēt aizsargu?"',
        description_en: 'Remove the defender: Bxf6 captures the knight that was defending the d5 pawn. After gxf6 (pawn structure ruined!) d5 is no longer defended and White can capture with exd5. Bonus: Black\'s pawns f6+f7 are doubled and weak. Always ask: "what defends this piece? Can I remove the defender?"',
    },
    {
        id: 34, title_lv: 'Figūras slazdis', title_en: 'Trapped Piece',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_hard'),
        fen: 'rn1qkbnr/pbpp1ppp/1p6/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 0 1', playerColor: 'white', goal: 'best_move', solution: 'g4',
        hint_lv: 'Melnā laidnis b7 ir iespiests. Gājiens g4 draud g5, kas ierobežos melnā figūras vēl vairāk.',
        hint_en: 'Black\'s bishop on b7 is cramped. The move g4 threatens g5, restricting Black\'s pieces even further.',
        description_lv: 'Figūras slazdis: g4 sāk plānu ierobežot melnā laidni b7. Pēc g4-g5 nākotnē melnā zirgs tiks padzīts no f6 (ja tas tur atradīsies), un laidnis zaudēs aktīvo diagonāli. Ieslodzīta figūra ir gandrīz tikpat slikti kā zaudēta figūra — tā nevar piedalīties spēlē.',
        description_en: 'Trapped piece: g4 starts a plan to restrict Black\'s bishop on b7. After g4-g5 in the future, Black\'s knight will be chased from f6 (if it gets there), and the bishop loses its active diagonal. A trapped piece is almost as bad as a lost piece — it cannot participate in the game.',
    },
    {
        id: 35, title_lv: 'Ruy Lopez piespraušana', title_en: 'Ruy Lopez Pin',
        category: t('puzzles.cat_tactics'), difficulty: t('puzzles.diff_medium'),
        fen: 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3', playerColor: 'white', goal: 'best_move', solution: 'Bb5',
        hint_lv: 'Zirgs c6 aizsargā e5 bandinieku. Vai laidnis var piespraust zirgu pie karaļa?',
        hint_en: 'The knight on c6 defends the e5 pawn. Can the bishop pin it to the king?',
        description_lv: 'Ruy Lopez (Spāņu partija) — viena no slavenākajām šaha atklātnēm. Lb5 piesprauž zirgu c6: zirgs nevar kustēties, jo aiz tā stāv karalis (absolūtā piespraušana). Tāpēc e5 bandinieks kļūst neaizsargāts. Praksē melnajam ir vairāki aizsardzības veidi (a6, d6, Nf6), bet piespraušanas ideja ir universāla un jāatpazīst.',
        description_en: 'Ruy Lopez (Spanish Game) — one of the most famous chess openings. Bb5 pins the knight on c6: the knight cannot move because the king is behind it (absolute pin). Therefore the e5 pawn becomes undefended. In practice Black has several defenses (a6, d6, Nf6), but the pin idea is universal and must be recognized.',
    },
    {
        id: 36, title_lv: 'Karaļu opozīcija', title_en: 'King Opposition',
        category: t('puzzles.cat_endgame'), difficulty: t('puzzles.diff_medium'),
        fen: '8/8/4k3/8/8/4P3/8/4K3 w - - 0 1', playerColor: 'white', goal: 'best_move', solution: 'Ke2',
        hint_lv: 'Nevirziet bandinieku! Vispirms virziet karali uz priekšu, lai iegūtu opozīciju. Ke2 — karalis nostājas TIEŠI pretī melnajam karalim.',
        hint_en: 'Don\'t push the pawn! First advance the king to gain the opposition. Ke2 — the king stands DIRECTLY opposite the black king.',
        description_lv: 'Opozīcija ir galotnes pamatprincips. Ja jūsu karalis stāv tieši pretī pretinieka karalim ar nepāra skaitu tukšu laukumu starp tiem un IR PRETINIEKA GĀJIENS — jums IR opozīcija. Ke2 iegūst opozīciju (e2-e3-e4-e5-e6 — 3 laukumi starp karaļiem, nepāra skaitlis). Melnajam jāatkāpjas, un baltais var virzīt bandinieku ar karaļa atbalstu. Ja e4? uzreiz — melnais spēlē Ke5! un VIŅAM ir opozīcija.',
        description_en: 'Opposition is a fundamental endgame principle. If your king faces the enemy king with an odd number of empty squares between them and IT\'S THE OPPONENT\'S MOVE — you HAVE the opposition. Ke2 gains the opposition (e2-e3-e4-e5-e6 — 3 squares between kings, odd number). Black must give way, and White can advance the pawn with king support. If e4? immediately — Black plays Ke5! and HE has the opposition.',
    },
    {
        id: 37, title_lv: 'Bandinieka paaugstināšana', title_en: 'Pawn Promotion',
        category: t('puzzles.cat_endgame'), difficulty: t('puzzles.diff_easy'),
        fen: '8/P5k1/8/8/8/8/6K1/8 w - - 0 1', playerColor: 'white', goal: 'best_move', solution: 'a8=Q',
        hint_lv: 'Bandinieks ir uz a7 — vienu soli no paaugstināšanas! Virziet uz a8 un izvēlieties dāmu.',
        hint_en: 'The pawn is on a7 — one step from promotion! Push to a8 and choose a queen.',
        description_lv: 'Bandinieka paaugstināšana: kad bandinieks sasniedz pretējo malu (8. rindu baltajam, 1. rindu melnajam), tas OBLIGĀTI kļūst par citu figūru — parasti dāmu. a8=D — bandinieks kļūst par dāmu, un jums tagad ir milzīgs materiāla pārsvars. Galotnē vienmēr cenšaties izveidot "brīvo bandinieku" (passed pawn) — bandinieku, kuram ceļā nav pretinieka bandinieku.',
        description_en: 'Pawn promotion: when a pawn reaches the opposite end (8th rank for White, 1st rank for Black), it MUST become another piece — usually a queen. a8=Q — the pawn becomes a queen, giving you a huge material advantage. In endgames, always try to create a "passed pawn" — a pawn with no enemy pawns in its path.',
    },
    {
        id: 38, title_lv: 'Rokāde drošībai', title_en: 'Castle to Safety',
        category: t('puzzles.cat_strategy'), difficulty: t('puzzles.diff_easy'),
        fen: 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/3P1N2/PPP2PPP/RNBQK2R w KQkq - 4 4', playerColor: 'white', goal: 'best_move', solution: 'O-O',
        hint_lv: 'Karalis centrā ir nedrošs — melnā figūras var ātri organizēt uzbrukumu. Rokāde pārvieto karali drošā pozīcijā.',
        hint_en: 'The king in the center is unsafe — Black\'s pieces can quickly organize an attack. Castling moves the king to safety.',
        description_lv: 'Rokāde (O-O vai O-O-O) ir VIENĪGAIS gājiens šahā, kurā pārvietojas DIVAS figūras: karalis un tornis. Īsā rokāde (O-O): karalis e1→g1, tornis h1→f1. Rokādes nosacījumi: (1) ne karalis, ne tornis nav iepriekš gājis, (2) starp tiem nav figūru, (3) karalis nav šahā, (4) karalis neiet caur šahu. Rokāde ir OBLIGĀTA atklātnes pirmajās 10 gājienos — kavēšanās var maksāt partiju.',
        description_en: 'Castling (O-O or O-O-O) is the ONLY move in chess where TWO pieces move: the king and the rook. Short castling (O-O): king e1→g1, rook h1→f1. Castling requirements: (1) neither king nor rook has moved before, (2) no pieces between them, (3) king is not in check, (4) king doesn\'t pass through check. Castling is MANDATORY in the first 10 opening moves — delaying can cost you the game.',
    },
    {
        id: 39, title_lv: 'Attīsti figūras', title_en: 'Develop Your Pieces',
        category: t('puzzles.cat_strategy'), difficulty: t('puzzles.diff_easy'),
        fen: 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1', playerColor: 'white', goal: 'best_move', solution: 'Nc3',
        hint_lv: 'Jūs esat izspēlējis e4. Nevirziet otru bandinieku! Attīstiet figūru — zirgu vai laidni.',
        hint_en: 'You\'ve played e4. Don\'t push another pawn! Develop a piece — a knight or bishop.',
        description_lv: 'Attīstība ir atklātnes galvenais princips: katru gājienu izvediet JAUNU figūru no sākuma pozīcijas uz aktīvu laukumu. Zirgs uz c3 kontrolē centru (d5, e4), aizsargā e4 bandinieku un atbrīvo ceļu dāmai. Iesācēju biežākā kļūda: virzīt 3-4 bandiniekus pēc kārtas, neattīstot nevienu figūru. Figūras ir jūsu armija — bandiniekiem vieni nevar uzvarēt.',
        description_en: 'Development is the main opening principle: every move, bring a NEW piece from its starting position to an active square. The knight on c3 controls the center (d5, e4), defends the e4 pawn, and clears the path for the queen. The most common beginner mistake: pushing 3-4 pawns in a row without developing any pieces. Pieces are your army — pawns alone cannot win.',
    },
    {
        id: 40, title_lv: 'Kontrolē centru', title_en: 'Control the Center',
        category: t('puzzles.cat_strategy'), difficulty: t('puzzles.diff_easy'),
        fen: 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1', playerColor: 'white', goal: 'best_move', solution: 'd4',
        hint_lv: 'Centrs ir d4, d5, e4, e5 — galdiņa "sirds". Jums jau ir e4. Kāds bandinieks vēl var kontrolēt centru?',
        hint_en: 'The center is d4, d5, e4, e5 — the "heart" of the board. You already have e4. Which pawn can also control the center?',
        description_lv: 'Centra kontrole ir šaha pamats. D4+e4 kopā kontrolē lielāko daļu centra — pretiniekam ir grūtāk attīstīt figūras, jo jūsu bandinieki draud tās sist. Divi bandinieki centrā dod "telpisko pārsvaru" — jūsu figūrām ir vairāk vietas nekā pretinieka figūrām. Ja kontrolējat centru, kontrolējat spēli.',
        description_en: 'Center control is the foundation of chess. d4+e4 together control most of the center — it\'s harder for the opponent to develop pieces because your pawns threaten to capture them. Two pawns in the center give "spatial advantage" — your pieces have more room than the opponent\'s. If you control the center, you control the game.',
    },

];

const currentIndex = ref(0);

// Persist progress in localStorage
const PUZZLE_STORAGE_KEY = 'pke_puzzle_progress';
function loadProgress() {
    try {
        const raw = localStorage.getItem(PUZZLE_STORAGE_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            return {
                solved: new Set(data.solved || []),
                attempted: new Set(data.attempted || []),
            };
        }
    } catch { /* intentionally silenced */ }
    return { solved: new Set(), attempted: new Set() };
}
function saveProgress() {
    try {
        localStorage.setItem(PUZZLE_STORAGE_KEY, JSON.stringify({
            solved: [...solved.value],
            attempted: [...attempted.value],
        }));
    } catch { /* intentionally silenced */ }
}

const { solved: initSolved, attempted: initAttempted } = loadProgress();
const solved = ref(initSolved);
const attempted = ref(initAttempted);
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
    saveProgress();

    const normalize = (s) => s.replace(/[+#?!]/g, '');

    const isSolved =
        current.value.goal === 'mate_in_1'
            ? (g.isCheckmate ? g.isCheckmate() : g.in_checkmate?.())
            : current.value.goal === 'best_move'
                ? normalize(result.san) === normalize(current.value.solution)
                : false;

    if (isSolved) {
        solved.value.add(current.value.id);
        saveProgress();
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
    } catch { /* intentionally silenced */ }
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

                <!-- INFO PANEL — w-full + min-w-0 so the card width is stable
                     instead of shrink-to-fit (it used to grow/shrink with the
                     puzzle description length on single-column layouts) -->
                <div class="space-y-5 w-full min-w-0">
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
                                {{ current.playerColor === 'white' ? '♔ ' + $t('puzzles.white_to_move') : '♚ ' + $t('puzzles.black_to_move') }}
                            </span>
                            <span class="text-[10px] font-bold text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full uppercase">
                                {{ $t('puzzles.goal_mate_1') }}
                            </span>
                        </div>

                        <button @click="showHint = !showHint"
                            class="w-full py-2.5 bg-black/40 text-amber-400/80 font-bold rounded-lg border border-white/5 hover:text-amber-400 hover:border-amber-500/30 text-xs uppercase tracking-wider transition-all">
                            💡 {{ showHint ? $t('puzzles.hide_hint') : $t('puzzles.show_hint') }}
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
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('puzzles.all_puzzles') }}</h3>
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
