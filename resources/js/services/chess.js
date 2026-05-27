import { Chess } from 'chess.js';
import { isBookMove } from './openings';

export function parsePgn(pgn) {
    const game = new Chess();
    try {
        game.loadPgn(pgn, { sloppy: true });
    } catch {
        const bare = new Chess();
        const cleaned = pgn
            .replace(/\{[^}]*\}/g, '').replace(/\([^)]*\)/g, '')
            .replace(/(1-0|0-1|1\/2-1\/2|\*)\s*$/, '').trim();
        const moveTokens = cleaned
            .replace(/\d+\.\.\./g, '').replace(/\d+\./g, '')
            .split(/\s+/).filter(m => m.length > 0);
        for (const token of moveTokens) {
            try { bare.move(token, { sloppy: true }); } catch { break; }
        }
        return extractMoves(bare);
    }
    return extractMoves(game);
}

function extractMoves(game) {
    const history = game.history({ verbose: true });
    const headers = game.header();
    const moves = [];
    const replay = new Chess();
    for (let i = 0; i < history.length; i++) {
        const h = history[i];
        const fenBefore = replay.fen();
        replay.move(h.san);
        const fenAfter = replay.fen();
        moves.push({
            san: h.san, uci: h.from + h.to + (h.promotion || ''),
            fen_before: fenBefore, fen_after: fenAfter,
            moveNumber: Math.floor(i / 2) + 1,
            color: i % 2 === 0 ? 'white' : 'black',
            from: h.from, to: h.to,
            captured: h.captured || null, flags: h.flags, piece: h.piece,
        });
    }
    return { moves, headers };
}

export function createGame(fen = null) {
    return fen ? new Chess(fen) : new Chess();
}

export function getLegalMoves(fen) {
    const game = new Chess(fen);
    return game.moves({ verbose: true }).map(m => ({
        from: m.from, to: m.to, san: m.san,
        uci: m.from + m.to + (m.promotion || ''),
        flags: m.flags, captured: m.captured,
    }));
}

export function isLegalMove(fen, from, to, promotion = null) {
    const game = new Chess(fen);
    try { return game.move({ from, to, promotion: promotion || undefined }) !== null; }
    catch { return false; }
}

export function makeMove(fen, from, to, promotion = null) {
    const game = new Chess(fen);
    try {
        const move = game.move({ from, to, promotion: promotion || undefined });
        if (!move) return null;
        return {
            fen: game.fen(), san: move.san,
            uci: move.from + move.to + (move.promotion || ''),
            captured: move.captured || null,
            isCheck: game.inCheck(), isCheckmate: game.isCheckmate(),
            isDraw: game.isDraw(), isStalemate: game.isStalemate(),
            isGameOver: game.isGameOver(),
        };
    } catch { return null; }
}

export { detectOpening } from './openings';

export function classifyEvalDiff(evalBefore, evalAfter, color) {
    const diff = color === 'white'
        ? evalBefore - evalAfter
        : evalAfter - evalBefore;
    if (diff <= 0.05) return 'best';
    if (diff <= 0.15) return 'excellent';
    if (diff <= 0.35) return 'good';
    if (diff <= 0.9)  return 'inaccuracy';
    if (diff <= 2.5)  return 'mistake';
    return 'blunder';
}

export function isMoveInBook(moves, moveIndex) {
    const sanList = moves.slice(0, moveIndex + 1).map(m => m.san || m.move_san);
    return isBookMove(sanList);
}

const PIECE_VALUES = { p: 1, n: 3, b: 3.25, r: 5, q: 9, k: 0 };

function parseFenBoard(fen) {
    const rows = fen.split(' ')[0].split('/');
    const board = [];
    for (let r = 0; r < 8; r++) {
        const row = [];
        for (const ch of rows[r]) {
            if (ch >= '1' && ch <= '8') {
                for (let i = 0; i < parseInt(ch); i++) row.push(null);
            } else {
                row.push(ch);
            }
        }
        board.push(row);
    }
    return board;
}

function pieceAt(board, sq) {
    const col = sq.charCodeAt(0) - 97;
    const row = 8 - parseInt(sq[1]);
    return board[row]?.[col] || null;
}

export function analyzePosition(fen) {
    const board = parseFenBoard(fen);
    const parts = fen.split(' ');
    const turn = parts[1] || 'w';
    const castling = parts[2] || '-';

    const white = { material: 0, pieces: [], pawns: [], developed: 0, kingSquare: null };
    const black = { material: 0, pieces: [], pawns: [], developed: 0, kingSquare: null };

    for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
            const p = board[r][c];
            if (!p) continue;
            const sq = String.fromCharCode(97 + c) + (8 - r);
            const isWhite = p === p.toUpperCase();
            const side = isWhite ? white : black;
            const lower = p.toLowerCase();

            if (lower === 'k') { side.kingSquare = sq; }
            side.material += PIECE_VALUES[lower] || 0;
            side.pieces.push({ piece: lower, square: sq, isWhite });

            if (lower === 'p') {
                side.pawns.push(sq);
            }

            if ('nb'.includes(lower)) {
                const backRank = isWhite ? 7 : 0;
                if (r !== backRank) side.developed++;
            }
        }
    }

    const centerSquares = ['d4', 'd5', 'e4', 'e5'];
    let whiteCenterPawns = 0, blackCenterPawns = 0;
    for (const sq of centerSquares) {
        const p = pieceAt(board, sq);
        if (p === 'P') whiteCenterPawns++;
        if (p === 'p') blackCenterPawns++;
    }

    const whiteCanCastle = castling.includes('K') || castling.includes('Q');
    const blackCanCastle = castling.includes('k') || castling.includes('q');

    const whiteCastled = white.kingSquare && ['g1', 'c1'].includes(white.kingSquare);
    const blackCastled = black.kingSquare && ['g8', 'c8'].includes(black.kingSquare);

    function doubledPawns(pawns) {
        const files = pawns.map(s => s[0]);
        return files.filter((f, i) => files.indexOf(f) !== i).length;
    }

    function isolatedPawns(pawns) {
        const files = pawns.map(s => s.charCodeAt(0) - 97);
        let count = 0;
        for (const f of files) {
            const hasNeighbour = files.some(nf => Math.abs(nf - f) === 1);
            if (!hasNeighbour) count++;
        }
        return count;
    }

    function kingShield(kingSquare, pawns, isWhite) {
        if (!kingSquare) return 0;
        const kFile = kingSquare.charCodeAt(0) - 97;
        const kRank = parseInt(kingSquare[1]);
        const shieldRank = isWhite ? kRank + 1 : kRank - 1;
        let shield = 0;
        for (let f = Math.max(0, kFile - 1); f <= Math.min(7, kFile + 1); f++) {
            const sq = String.fromCharCode(97 + f) + shieldRank;
            if (pawns.includes(sq)) shield++;
        }
        return shield;
    }

    const materialBalance = white.material - black.material;
    const totalPieces = white.pieces.length + black.pieces.length;
    const minorMajor = white.pieces.filter(p => 'nbrq'.includes(p.piece)).length +
                       black.pieces.filter(p => 'nbrq'.includes(p.piece)).length;
    const hasQueens = white.pieces.some(p => p.piece === 'q') || black.pieces.some(p => p.piece === 'q');

    return {
        turn, materialBalance, totalPieces, minorMajor, hasQueens,
        white: {
            ...white, centerPawns: whiteCenterPawns,
            canCastle: whiteCanCastle, castled: whiteCastled,
            doubledPawns: doubledPawns(white.pawns),
            isolatedPawns: isolatedPawns(white.pawns),
            kingShield: kingShield(white.kingSquare, white.pawns, true),
        },
        black: {
            ...black, centerPawns: blackCenterPawns,
            canCastle: blackCanCastle, castled: blackCastled,
            doubledPawns: doubledPawns(black.pawns),
            isolatedPawns: isolatedPawns(black.pawns),
            kingShield: kingShield(black.kingSquare, black.pawns, false),
        },
    };
}

function getGamePhase(moveIndex, totalMoves, fenBefore) {
    const pos = analyzePosition(fenBefore);
    if (moveIndex < 24 && pos.minorMajor >= 10) return 'opening';
    if (pos.totalPieces <= 10) return 'endgame';
    if (!pos.hasQueens && pos.minorMajor <= 4) return 'endgame';
    return 'middlegame';
}

export function categorizeError(moveIndex, totalMoves, move) {
    const fen = move.fen_before || move.fenBefore || '';
    const phase = getGamePhase(moveIndex, totalMoves, fen);

    if (phase === 'opening') return 'opening';
    if (phase === 'endgame') return 'endgame';

    const san = move.san || move.move_san || '';
    const isCheck = san.includes('+') || san.includes('#');
    const isCapture = !!move.captured || san.includes('x');
    if (isCapture || isCheck) return 'tactical';
    if (san.includes('O-O') || (move.piece || '').toLowerCase() === 'k') return 'tactical';

    return 'positional';
}

export const ERROR_CATEGORIES = {
    tactical:   { icon: '⚔', key: 'tactical' },
    positional: { icon: '◈', key: 'positional' },
    opening:    { icon: '♟', key: 'opening' },
    endgame:    { icon: '♔', key: 'endgame' },
};

function detectConsequence(fenBefore, fenAfter, moveSan, color) {
    const before = analyzePosition(fenBefore);
    const after = analyzePosition(fenAfter);
    const side = color === 'white' ? 'white' : 'black';
    const opp = color === 'white' ? 'black' : 'white';
    const hints = [];

    const matBefore = before[side].material - before[opp].material;
    const matAfter = after[side].material - after[opp].material;
    const matSwing = matBefore - matAfter;
    if (matSwing >= 3) hints.push('material_loss_major');
    else if (matSwing >= 1) hints.push('material_loss_minor');

    if (before[side].canCastle && !after[side].canCastle && !after[side].castled) {
        hints.push('lost_castling');
    }

    if (after[side].kingShield < before[side].kingShield) {
        hints.push('king_exposed');
    }

    if (after[side].doubledPawns > before[side].doubledPawns) {
        hints.push('doubled_pawns');
    }

    if (after[side].isolatedPawns > before[side].isolatedPawns) {
        hints.push('isolated_pawn');
    }

    if (after[side].centerPawns < before[side].centerPawns) {
        hints.push('lost_center');
    }

    if (color === 'white' && after.black.developed > before.black.developed + 1) {
        hints.push('opp_development');
    }
    if (color === 'black' && after.white.developed > before.white.developed + 1) {
        hints.push('opp_development');
    }

    if (moveSan.includes('+')) hints.push('gives_check');

    return hints;
}

function detectPositiveTraits(fenBefore, fenAfter, moveSan, color) {
    const before = analyzePosition(fenBefore);
    const after = analyzePosition(fenAfter);
    const side = color === 'white' ? 'white' : 'black';
    const opp = color === 'white' ? 'black' : 'white';
    const traits = [];

    const matBefore = before[side].material - before[opp].material;
    const matAfter = after[side].material - after[opp].material;
    if (matAfter > matBefore + 0.5) traits.push('material_gain');

    if (!before[side].castled && after[side].castled) traits.push('castled');

    if (after[side].centerPawns > before[side].centerPawns) traits.push('center_control');

    if (after[side].developed > before[side].developed) traits.push('development');

    if (moveSan.includes('+')) traits.push('check');
    if (moveSan.includes('#')) traits.push('checkmate');

    if (moveSan.includes('x')) traits.push('capture');

    return traits;
}

const CONSEQUENCE_TEXT = {
    en: {
        material_loss_major: 'This loses significant material (a piece or more).',
        material_loss_minor: 'This leads to loss of a pawn or minor exchange.',
        lost_castling:       'This forfeits the right to castle, leaving the king vulnerable.',
        king_exposed:        'The pawn shield in front of the king is weakened.',
        doubled_pawns:       'This creates doubled pawns, a long-term structural weakness.',
        isolated_pawn:       'This creates an isolated pawn that will be difficult to defend.',
        lost_center:         'Center control is surrendered, giving the opponent more space.',
        opp_development:     'The opponent gains a significant lead in piece development.',
    },
    lv: {
        material_loss_major: 'Šis gājiens zaudē nozīmīgu materiālu (figūru vai vairāk).',
        material_loss_minor: 'Šis gājiens noved pie bandinieka vai nelabvēlīgas apmaiņas zaudēšanas.',
        lost_castling:       'Ar šo gājienu tiek zaudētas rokādes tiesības, atstājot karali neaizsargātu.',
        king_exposed:        'Bandinieku vairogs karaļa priekšā ir novājināts.',
        doubled_pawns:       'Šis gājiens rada dubultotos bandiniekus — ilgtermiņa strukturāla vājība.',
        isolated_pawn:       'Šis rada izolētu bandinieku, kuru būs grūti aizstāvēt.',
        lost_center:         'Centra kontrole tiek atdota, dodot pretiniekam vairāk telpas.',
        opp_development:     'Pretinieks iegūst ievērojamu pārsvaru figūru attīstībā.',
    },
};

const POSITIVE_TEXT = {
    en: {
        material_gain:  'Good capture — winning material.',
        castled:        'Excellent! Castling secures the king and activates the rook.',
        center_control: 'Strengthens central control — a key positional principle.',
        development:    'Good development — bringing pieces into the game efficiently.',
        check:          'Applies pressure with check, forcing the opponent to react.',
        checkmate:      'Checkmate!',
        capture:        'Strong exchange.',
    },
    lv: {
        material_gain:  'Labs sitiens — iegūts materiāls.',
        castled:        'Lieliski! Rokāde pasargā karali un aktivizē torni.',
        center_control: 'Stiprina centra kontroli — svarīgs pozicionāls princips.',
        development:    'Laba attīstība — figūras tiek efektīvi ieviestas spēlē.',
        check:          'Šahs rada spiedienu, piespiežot pretinieku reaģēt.',
        checkmate:      'Mats!',
        capture:        'Spēcīga apmaiņa.',
    },
};

const TEACHING_TIPS = {
    en: {
        tactical: {
            inaccuracy: 'Tip: Before each move, check if your opponent has any tactical threats (forks, pins, skewers). Look for undefended pieces.',
            mistake:    'Tip: Always ask "is this piece safe after I move it?" Check all captures and checks your opponent can make.',
            blunder:    'Tip: Use the "blunder check" — before playing, imagine your opponent\'s best response. If it wins material, reconsider.',
        },
        positional: {
            inaccuracy: 'Tip: Consider pawn structure before pushing pawns. Pawns can\'t move backward — every push is permanent.',
            mistake:    'Tip: Place your pieces on squares where they control the most space. Knights love outposts; bishops love open diagonals.',
            blunder:    'Tip: Don\'t weaken your king position without a concrete reason. Advancing pawns in front of your king is very risky.',
        },
        opening: {
            inaccuracy: 'Tip: In the opening, prioritize: 1) control the center, 2) develop pieces, 3) castle early. Avoid moving the same piece twice.',
            mistake:    'Tip: Don\'t bring your queen out too early — it becomes a target. Develop knights and bishops first.',
            blunder:    'Tip: Don\'t grab pawns in the opening if it costs development time. A lead in development can be decisive.',
        },
        endgame: {
            inaccuracy: 'Tip: In endgames, activate your king! The king becomes a powerful piece when there are fewer threats.',
            mistake:    'Tip: Passed pawns must be pushed! In endgames, a pawn reaching the 8th rank decides the game.',
            blunder:    'Tip: In king + pawn endgames, the "opposition" (kings facing each other) is critical. Study basic endgame patterns.',
        },
    },
    lv: {
        tactical: {
            inaccuracy: 'Padoms: Pirms katra gājiena pārbaudiet, vai pretiniekam nav taktisko draudu (dakšas, piespraudumus, šķērsšāvienus). Meklējiet neaizsargātas figūras.',
            mistake:    'Padoms: Vienmēr jautājiet "vai šī figūra ir droša pēc gājiena?" Pārbaudiet visus sitienu un šachu variantus, ko pretinieks var veikt.',
            blunder:    'Padoms: Izmantojiet "kļūdu pārbaudi" — pirms gājiena iedomājieties pretinieka labāko atbildi. Ja tā laimē materiālu, pārdomājiet.',
        },
        positional: {
            inaccuracy: 'Padoms: Apsveriet bandinieku struktūru pirms to virzīšanas. Bandinieki nevar iet atpakaļ — katrs gājiens ir neatgriezenisks.',
            mistake:    'Padoms: Novietojiet figūras uz laukiem, kur tās kontrolē visvairāk telpas. Zirgi mīl stipros punktus; laidnieki mīl atvērtas diagonāles.',
            blunder:    'Padoms: Nevājiniet karaļa pozīciju bez konkrēta iemesla. Bandinieku virzīšana karaļa priekšā ir ļoti riskanta.',
        },
        opening: {
            inaccuracy: 'Padoms: Atklātnē prioritātes: 1) kontrolēt centru, 2) attīstīt figūras, 3) agri rokēties. Izvairieties no vienas figūras atkārtotas kustināšanas.',
            mistake:    'Padoms: Neizvediet dāmu pārāk agri — tā kļūst par mērķi. Vispirms attīstiet zirgus un laidniekus.',
            blunder:    'Padoms: Neķeriet bandiniekus atklātnē, ja tas maksā attīstības tempu. Pārsvars attīstībā var būt izšķirošs.',
        },
        endgame: {
            inaccuracy: 'Padoms: Galotnēs aktivizējiet karali! Karalis kļūst par spēcīgu figūru, kad ir mazāk draudu.',
            mistake:    'Padoms: Brīvie bandinieki ir jāvirza! Galotnēs bandinieks, kas sasniedz 8. rindu, izšķir spēli.',
            blunder:    'Padoms: Karaļa un bandinieku galotnēs "opozīcija" (karaļi viens pret otru) ir kritiska. Apgūstiet pamata galotņu modeļus.',
        },
    },
};

const GOOD_MOVE_TEXT = {
    en: {
        best:      'Excellent! This is the engine\'s top choice — the strongest move in this position.',
        excellent: 'Very strong move — nearly as good as the absolute best.',
        good:      'Solid move. You maintained a good position.',
    },
    lv: {
        best:      'Lieliski! Šis ir dzinēja pirmā izvēle — spēcīgākais gājiens šajā pozīcijā.',
        excellent: 'Ļoti spēcīgs gājiens — gandrīz tikpat labs kā absolūti labākais.',
        good:      'Stabils gājiens. Jūs saglabājāt labu pozīciju.',
    },
};

export function generateExplanation(classification, category, move, bestMove, locale = 'lv', extra = {}) {
    const lang = locale === 'en' ? 'en' : 'lv';
    const { fenBefore, fenAfter, color, evalBefore, evalAfter } = extra;

    if (['best', 'excellent', 'good'].includes(classification)) {
        let positives = [];
        if (fenBefore && fenAfter && color) {
            positives = detectPositiveTraits(fenBefore, fenAfter, move, color);
        }
        const posTexts = positives.map(p => POSITIVE_TEXT[lang]?.[p]).filter(Boolean);
        const mainText = GOOD_MOVE_TEXT[lang]?.[classification] || '';
        const detail = posTexts.length > 0 ? posTexts.join(' ') : '';

        return {
            text: mainText,
            detail,
            tip: null,
            evalSwing: null,
            isPositive: true,
        };
    }

    let consequences = [];
    if (fenBefore && fenAfter && color) {
        consequences = detectConsequence(fenBefore, fenAfter, move, color);
    }

    const conseqTexts = consequences.map(c => CONSEQUENCE_TEXT[lang]?.[c]).filter(Boolean);

    let mainText;
    if (conseqTexts.length > 0) {
        const intro = lang === 'en'
            ? `${move} is ${classification === 'blunder' ? 'a serious error' : classification === 'mistake' ? 'a mistake' : 'inaccurate'}.`
            : `${move} ir ${classification === 'blunder' ? 'nopietna kļūda' : classification === 'mistake' ? 'kļūda' : 'neprecīzs'}.`;
        mainText = intro + ' ' + conseqTexts.join(' ');
    } else {
        // Fallback generic
        const templates = {
            en: {
                tactical:   { inaccuracy: `${move} misses a tactical opportunity.`, mistake: `Tactical error — ${move} loses material or position.`, blunder: `Serious tactical blunder! ${move} gives the opponent a decisive advantage.` },
                positional: { inaccuracy: `${move} slightly weakens the position.`, mistake: `Positional mistake — ${move} loses control of key squares.`, blunder: `Major positional error! ${move} critically damages the position structure.` },
                opening:    { inaccuracy: `${move} deviates from the best opening line.`, mistake: `Opening error — ${move} falls behind in development.`, blunder: `Critical opening mistake! ${move} creates immediate problems.` },
                endgame:    { inaccuracy: `In the endgame, ${move} is imprecise.`, mistake: `Endgame mistake — ${move} loses a winning advantage.`, blunder: `Critical endgame blunder! ${move} turns a win into a loss.` },
            },
            lv: {
                tactical:   { inaccuracy: `Gājiens ${move} neizmanto taktisko iespēju.`, mistake: `Taktiska kļūda — ${move} zaudē materiālu vai pozīciju.`, blunder: `Nopietna taktiska kļūda! ${move} ļauj pretiniekam iegūt izšķirošu pārsvaru.` },
                positional: { inaccuracy: `${move} nedaudz pavājina pozīciju.`, mistake: `Pozicionāla kļūda — ${move} zaudē kontroli pār svarīgiem laukiem.`, blunder: `Rupja pozicionāla kļūda! ${move} pilnībā sagrauj pozīcijas struktūru.` },
                opening:    { inaccuracy: `Atklātnē ${move} novirzās no labākās līnijas.`, mistake: `Kļūda atklātnē — ${move} zaudē tempu attīstībā.`, blunder: `Nopietna atklātnes kļūda! ${move} rada tūlītējas problēmas.` },
                endgame:    { inaccuracy: `Galotnē ${move} ir neprecīzs.`, mistake: `Galotnes kļūda — ${move} zaudē izdevīgu pozīciju.`, blunder: `Rupja galotnes kļūda! ${move} pārvērš uzvaru zaudējumā.` },
            },
        };
        mainText = templates[lang]?.[category]?.[classification] || `${move} → ${bestMove}`;
    }

    if (bestMove && bestMove !== move) {
        mainText += lang === 'en'
            ? ` Better: ${bestMove}`
            : ` Labāk: ${bestMove}`;
    }

    let evalSwing = null;
    if (evalBefore !== undefined && evalAfter !== undefined) {
        const swing = Math.abs(evalAfter - evalBefore);
        if (swing >= 0.5) {
            evalSwing = {
                before: evalBefore,
                after: evalAfter,
                swing: Math.round(swing * 100) / 100,
            };
        }
    }

    const tip = TEACHING_TIPS[lang]?.[category]?.[classification] || null;

    return {
        text: mainText,
        detail: null,
        tip,
        evalSwing,
        isPositive: false,
    };
}

export function generateGameSummary(analyzedMoves, locale = 'lv') {
    const lang = locale === 'en' ? 'en' : 'lv';
    const total = analyzedMoves.length;
    if (total === 0) return null;

    const blunders = analyzedMoves.filter(m => m.classification === 'blunder').length;
    const mistakes = analyzedMoves.filter(m => m.classification === 'mistake').length;
    const inaccuracies = analyzedMoves.filter(m => m.classification === 'inaccuracy').length;
    const bestMoves = analyzedMoves.filter(m => m.classification === 'best').length;
    const bookMoves = analyzedMoves.filter(m => m.classification === 'book').length;
    const errorTotal = blunders + mistakes + inaccuracies;
    const accuracy = Math.round(((total - errorTotal) / total) * 100);

    const openingErrors = analyzedMoves.filter(m => m.error_category === 'opening').length;
    const middlegameErrors = analyzedMoves.filter(m => ['tactical', 'positional'].includes(m.error_category)).length;
    const endgameErrors = analyzedMoves.filter(m => m.error_category === 'endgame').length;

    const phases = [
        { key: 'opening', count: openingErrors },
        { key: 'middlegame', count: middlegameErrors },
        { key: 'endgame', count: endgameErrors },
    ].sort((a, b) => b.count - a.count);
    const worstPhase = phases[0].count > 0 ? phases[0].key : null;

    let level;
    if (accuracy >= 95) level = 'brilliant';
    else if (accuracy >= 85) level = 'great';
    else if (accuracy >= 70) level = 'good';
    else if (accuracy >= 55) level = 'average';
    else level = 'needs_work';

    const levelText = {
        en: { brilliant: 'Brilliant game!', great: 'Great performance!', good: 'Good game.', average: 'Average performance.', needs_work: 'Room for improvement.' },
        lv: { brilliant: 'Izcila partija!', great: 'Lielisks sniegums!', good: 'Laba partija.', average: 'Vidējs sniegums.', needs_work: 'Ir ko uzlabot.' },
    };

    const phaseText = {
        en: { opening: 'opening', middlegame: 'middlegame', endgame: 'endgame' },
        lv: { opening: 'atklātnē', middlegame: 'vidussplēlē', endgame: 'galotnē' },
    };

    let advice = '';
    if (worstPhase) {
        advice = lang === 'en'
            ? `Most errors occurred in the ${phaseText.en[worstPhase]}. Focus your training there.`
            : `Visvairāk kļūdu bija ${phaseText.lv[worstPhase]}. Koncentrējiet treniņus šajā jomā.`;
    }

    return {
        accuracy, level,
        levelText: levelText[lang][level],
        blunders, mistakes, inaccuracies,
        bestMoves, bookMoves,
        errorTotal, total,
        worstPhase, advice,
        openingErrors, middlegameErrors, endgameErrors,
    };
}

export { Chess };
