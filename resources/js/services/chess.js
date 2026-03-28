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
export function generateExplanation(classification, category, move, bestMove) {
    if (['best', 'excellent', 'good'].includes(classification)) return null;

    const explanations = {
        tactical: {
            inaccuracy: `Gājiens ${move} neizmanto taktisko iespēju. Labāk: ${bestMove}`,
            mistake: `Taktiska kļūda — ${move} zaudē materiālu vai pozīciju. Ieteicams: ${bestMove}`,
            blunder: `Nopietna taktiska kļūda! ${move} ļauj pretiniekam iegūt izšķirošu pārsvaru. Pareizi bija: ${bestMove}`,
        },
        positional: {
            inaccuracy: `Pozicionāla neprecizitāte — ${move} nedaudz pavājina pozīciju. Apsveriet: ${bestMove}`,
            mistake: `Pozicionāla kļūda — ${move} zaudē kontroli pār svarīgiem laukiem. Labāk: ${bestMove}`,
            blunder: `Rupja pozicionāla kļūda! ${move} pilnībā sagrauj pozīcijas struktūru. Pareizi: ${bestMove}`,
        },
        opening: {
            inaccuracy: `Atklātnē ${move} novirzās no labākās teorijas līnijas. Ieteicams: ${bestMove}`,
            mistake: `Kļūda atklātnē — ${move} zaudē tempu attīstībā. Labāk: ${bestMove}`,
            blunder: `Nopietna atklātnes kļūda! ${move} rada tūlītējas problēmas. Pareizi: ${bestMove}`,
        },
        endgame: {
            inaccuracy: `Galotnē ${move} ir neprecīzs. Precīzāk: ${bestMove}`,
            mistake: `Galotnes kļūda — ${move} zaudē izdevīgu pozīciju. Labāk: ${bestMove}`,
            blunder: `Rupja galotnes kļūda! ${move} pārvērš uzvaru zaudējumā. Pareizi: ${bestMove}`,
        },
    };

    return explanations[category]?.[classification] || `Labāks gājiens: ${bestMove}`;
}

export { Chess };
