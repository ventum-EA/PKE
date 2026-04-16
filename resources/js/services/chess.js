/**
 * Chess logic service wrapping chess.js.
 * Provides PGN parsing, move validation, FEN extraction, and game state.
 */
import { Chess } from 'chess.js';

/**
 * Parse a PGN string and extract all positions (FENs) and moves.
 * Returns { moves: [{san, uci, fen_before, fen_after, moveNumber, color}], headers }
 */
export function parsePgn(pgn) {
    const game = new Chess();

    try {
        game.loadPgn(pgn, { sloppy: true });
    } catch {
        // Try loading as move list without headers
        const bare = new Chess();
        const cleaned = pgn
            .replace(/\{[^}]*\}/g, '')
            .replace(/\([^)]*\)/g, '')
            .replace(/(1-0|0-1|1\/2-1\/2|\*)\s*$/, '')
            .trim();

        const moveTokens = cleaned
            .replace(/\d+\.\.\./g, '')
            .replace(/\d+\./g, '')
            .split(/\s+/)
            .filter(m => m.length > 0);

        for (const token of moveTokens) {
            try {
                bare.move(token, { sloppy: true });
            } catch {
                break; // Stop at first invalid move
            }
        }

        // Reconstruct from bare game
        return extractMoves(bare);
    }

    return extractMoves(game);
}

function extractMoves(game) {
    const history = game.history({ verbose: true });
    const headers = game.header();
    const moves = [];

    // Rebuild positions step by step
    const replay = new Chess();
    for (let i = 0; i < history.length; i++) {
        const h = history[i];
        const fenBefore = replay.fen();
        replay.move(h.san);
        const fenAfter = replay.fen();

        moves.push({
            san: h.san,
            uci: h.from + h.to + (h.promotion || ''),
            fen_before: fenBefore,
            fen_after: fenAfter,
            moveNumber: Math.floor(i / 2) + 1,
            color: i % 2 === 0 ? 'white' : 'black',
            from: h.from,
            to: h.to,
            captured: h.captured || null,
            flags: h.flags,
        });
    }

    return { moves, headers };
}

/**
 * Create a new Chess instance for interactive play.
 */
export function createGame(fen = null) {
    return fen ? new Chess(fen) : new Chess();
}

/**
 * Convert a UCI move (e.g. "d2d4") to SAN (e.g. "d4") for a given position.
 * Returns the UCI string unchanged if conversion fails.
 */
export function uciToSan(fen, uci) {
    if (!uci || uci === '(none)' || uci.length < 4) return uci;
    try {
        const game = new Chess(fen);
        const from = uci.substring(0, 2);
        const to = uci.substring(2, 4);
        const promotion = uci.length > 4 ? uci[4] : undefined;
        const result = game.move({ from, to, promotion });
        return result ? result.san : uci;
    } catch {
        return uci;
    }
}

/**
 * Get legal moves for a position.
 * Returns array of { from, to, san, uci, flags }
 */
export function getLegalMoves(fen) {
    const game = new Chess(fen);
    return game.moves({ verbose: true }).map(m => ({
        from: m.from,
        to: m.to,
        san: m.san,
        uci: m.from + m.to + (m.promotion || ''),
        flags: m.flags,
        captured: m.captured,
    }));
}

/**
 * Check if a move is legal in a given position.
 */
export function isLegalMove(fen, from, to, promotion = null) {
    const game = new Chess(fen);
    try {
        const move = game.move({ from, to, promotion: promotion || undefined });
        return move !== null;
    } catch {
        return false;
    }
}

/**
 * Make a move and return the new FEN, or null if illegal.
 */
export function makeMove(fen, from, to, promotion = null) {
    const game = new Chess(fen);
    try {
        const move = game.move({ from, to, promotion: promotion || undefined });
        if (!move) return null;
        return {
            fen: game.fen(),
            san: move.san,
            uci: move.from + move.to + (move.promotion || ''),
            captured: move.captured || null,
            isCheck: game.inCheck(),
            isCheckmate: game.isCheckmate(),
            isDraw: game.isDraw(),
            isStalemate: game.isStalemate(),
            isGameOver: game.isGameOver(),
        };
    } catch {
        return null;
    }
}

/**
 * Detect opening name from first moves — delegates to ECO database.
 */
export { detectOpening } from './openings';

/**
 * Classify eval difference into move quality.
 */
export function classifyEvalDiff(evalBefore, evalAfter, color) {
    // Eval is always from white's perspective
    // For white, a drop means bad; for black, a rise means bad
    const diff = color === 'white'
        ? evalBefore - evalAfter  // White wants eval to stay high
        : evalAfter - evalBefore; // Black wants eval to stay low (negative)

    if (diff <= 0.05) return 'best';
    if (diff <= 0.15) return 'excellent';
    if (diff <= 0.3) return 'good';
    if (diff <= 0.8) return 'inaccuracy';
    if (diff <= 2.0) return 'mistake';
    return 'blunder';
}

/**
 * Categorize error type based on game phase and move characteristics.
 */
export function categorizeError(moveIndex, totalMoves, move) {
    const phase = totalMoves > 0 ? moveIndex / totalMoves : 0;
    if (phase < 0.2) return 'opening';
    if (phase > 0.7) return 'endgame';
    if (move.captured || move.san.includes('+') || move.san.includes('#')) return 'tactical';
    return 'positional';
}

/**
 * Generate explanation for a move error in Latvian.
 */
export function generateExplanation(classification, category, move, bestMove, evalDiff) {
    if (['best', 'excellent', 'good'].includes(classification)) return null;

    // Clean up bestMove
    if (!bestMove || bestMove === '(none)' || bestMove === move || /^\d+\.?$/.test(bestMove)) {
        bestMove = null;
    }

    const alt = bestMove ? ` Labākais gājiens bija ${bestMove}.` : '';
    const diff = evalDiff ? ` (${evalDiff > 0 ? '+' : ''}${evalDiff.toFixed(1)})` : '';

    const tactical_inaccuracies = [
        `${move} neizmanto taktisko iespēju šajā pozīcijā.${alt}`,
        `Ar ${move} tiek palaists garām taktisks motīvs.${alt}`,
        `Pozīcijā bija spēcīgāka taktiska turpināšana nekā ${move}.${alt}`,
    ];
    const tactical_mistakes = [
        `Taktiska kļūda${diff} — ${move} zaudē materiālu.${alt}`,
        `${move} noved pie materiāla zaudējuma.${alt}`,
        `Ar ${move} tiek atdota figūra vai bandinieks.${alt}`,
    ];
    const tactical_blunders = [
        `Rupja taktiska kļūda${diff}! ${move} ļauj pretiniekam izšķirošu pārsvaru.${alt}`,
        `${move} ir katastrofāla kļūda — pozīcija ir zaudēta.${alt}`,
    ];
    const positional_inaccuracies = [
        `${move} nedaudz pavājina pozīciju.${alt}`,
        `Ar ${move} tiek zaudēta neliela pozicionāla priekšrocība.${alt}`,
        `${move} nav optimāls — pozīcija kļūst sarežģītāka.${alt}`,
    ];
    const positional_mistakes = [
        `Pozicionāla kļūda${diff} — ${move} zaudē kontroli pār svarīgiem laukiem.${alt}`,
        `${move} noved pie pozīcijas pasliktināšanās.${alt}`,
        `Ar ${move} tiek pavājināta bandinieku struktūra.${alt}`,
    ];
    const positional_blunders = [
        `Rupja pozicionāla kļūda${diff}! ${move} sagrauj pozīcijas struktūru.${alt}`,
        `${move} pilnībā iznīcina pozicionālo pārsvaru.${alt}`,
    ];
    const opening_inaccuracies = [
        `Atklātnē ${move} novirzās no labākās teorijas līnijas.${alt}`,
        `${move} nav teorētiski labākais gājiens šajā atklātnē.${alt}`,
        `Ar ${move} tiek zaudēts attīstības temps.${alt}`,
    ];
    const opening_mistakes = [
        `Kļūda atklātnē${diff} — ${move} zaudē tempu.${alt}`,
        `${move} ir teorētiski vājš gājiens šajā atklātnē.${alt}`,
    ];
    const opening_blunders = [
        `Nopietna atklātnes kļūda${diff}! ${move} rada tūlītējas problēmas.${alt}`,
    ];
    const endgame_inaccuracies = [
        `Galotnē ${move} ir neprecīzs.${alt}`,
        `${move} sarežģī galotnes realizāciju.${alt}`,
    ];
    const endgame_mistakes = [
        `Galotnes kļūda${diff} — ${move} zaudē izdevīgu pozīciju.${alt}`,
        `Ar ${move} galotne kļūst grūtāk uzvarama.${alt}`,
    ];
    const endgame_blunders = [
        `Rupja galotnes kļūda${diff}! ${move} pārvērš uzvaru zaudējumā.${alt}`,
    ];

    const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];

    const map = {
        tactical: { inaccuracy: tactical_inaccuracies, mistake: tactical_mistakes, blunder: tactical_blunders },
        positional: { inaccuracy: positional_inaccuracies, mistake: positional_mistakes, blunder: positional_blunders },
        opening: { inaccuracy: opening_inaccuracies, mistake: opening_mistakes, blunder: opening_blunders },
        endgame: { inaccuracy: endgame_inaccuracies, mistake: endgame_mistakes, blunder: endgame_blunders },
    };

    const pool = map[category]?.[classification];
    if (pool) return pick(pool);
    return bestMove ? `Labāks gājiens bija ${bestMove}.` : `Neprecīzs gājiens${diff}.`;
}

export { Chess };
