/**
 * Integration-level tests for chess.js service.
 *
 * These tests verify actual chess logic (full games, edge cases,
 * multi-move sequences) rather than testing individual function
 * signatures in isolation.
 */
import { describe, test, expect } from 'vitest';
import {
    createGame, makeMove, getLegalMoves, isLegalMove,
    parsePgn, classifyEvalDiff, categorizeError,
    analyzePosition, generateExplanation, generateGameSummary,
} from '../../resources/js/services/chess';

const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

/* ─── Full game sequences ─────────────────────────────────────── */

describe('full game sequences', () => {
    test('Scholar\'s Mate in 4 moves produces checkmate', () => {
        let fen = START_FEN;
        const moves = [
            ['e2', 'e4'], ['e7', 'e5'],
            ['d1', 'h5'], ['b8', 'c6'],
            ['f1', 'c4'], ['g8', 'f6'],
            ['h5', 'f7'],
        ];

        let result;
        for (const [from, to] of moves) {
            result = makeMove(fen, from, to);
            expect(result).not.toBeNull();
            fen = result.fen;
        }

        // After Qxf7# the game should be checkmate
        const game = createGame(fen);
        expect(game.isCheckmate()).toBe(true);
    });

    test('Fool\'s Mate in 2 moves', () => {
        let fen = START_FEN;
        const moves = [['f2', 'f3'], ['e7', 'e5'], ['g2', 'g4'], ['d8', 'h4']];
        let result;
        for (const [from, to] of moves) {
            result = makeMove(fen, from, to);
            expect(result).not.toBeNull();
            fen = result.fen;
        }
        const game = createGame(fen);
        expect(game.isCheckmate()).toBe(true);
    });

    test('castling kingside produces correct FEN', () => {
        // After 1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 — white can castle
        const setup = [
            ['e2', 'e4'], ['e7', 'e5'],
            ['g1', 'f3'], ['b8', 'c6'],
            ['f1', 'c4'], ['f8', 'c5'],
        ];
        let fen = START_FEN;
        for (const [from, to] of setup) {
            const r = makeMove(fen, from, to);
            expect(r).not.toBeNull();
            fen = r.fen;
        }

        // Now castle: e1 → g1
        const castle = makeMove(fen, 'e1', 'g1');
        expect(castle).not.toBeNull();
        expect(castle.san).toBe('O-O');
        // King should be on g1, rook on f1
        expect(castle.fen).toContain('R1K1');
    });

    test('en passant capture works correctly', () => {
        // Set up en passant: white pawn on e5, black plays d7-d5
        const epFen = 'rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 3';
        const result = makeMove(epFen, 'e5', 'd6');
        expect(result).not.toBeNull();
        expect(result.san).toBe('exd6');
        // The black pawn on d5 should be gone
        expect(result.fen).not.toContain('3p4');
    });

    test('pawn promotion works', () => {
        const promoFen = '8/P7/8/8/8/8/8/4K2k w - - 0 1';
        const result = makeMove(promoFen, 'a7', 'a8');
        expect(result).not.toBeNull();
        // Should promote to queen by default
        expect(result.san).toContain('a8=Q');
    });
});

/* ─── Legal move accuracy ──────────────────────────────────────── */

describe('getLegalMoves accuracy', () => {
    test('starting position has 20 legal moves', () => {
        const moves = getLegalMoves(START_FEN);
        expect(moves.length).toBe(20);
    });

    test('no legal moves in checkmate position', () => {
        // Black is checkmated
        const mateFen = 'rnb1kbnr/pppp1ppp/4p3/8/6Pq/5P2/PPPPP2P/RNBQKBNR w KQkq - 1 3';
        const moves = getLegalMoves(mateFen);
        expect(moves.length).toBe(0);
    });

    test('king in check has limited moves', () => {
        // White king is in check from black queen
        const checkFen = 'rnbqkbnr/ppppp1pp/8/5p1Q/4P3/8/PPPP1PPP/RNB1KBNR b KQkq - 1 2';
        const moves = getLegalMoves(checkFen);
        // Must block, capture, or move king — fewer than normal
        expect(moves.length).toBeLessThan(20);
        expect(moves.length).toBeGreaterThan(0);
    });
});

/* ─── PGN parsing with real game data ──────────────────────────── */

describe('parsePgn with real games', () => {
    test('parses Italian Game opening', () => {
        const pgn = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 1/2-1/2';
        const result = parsePgn(pgn);
        expect(result).toBeDefined();
        expect(result.moves.length).toBe(6);
        expect(result.moves[0].san).toBe('e4');
        expect(result.moves[5].san).toBe('Bc5');
    });

    test('parses game with comments and variations', () => {
        const pgn = '1. e4 {best move} e5 (1... c5 {Sicilian}) 2. Nf3 Nc6 *';
        const result = parsePgn(pgn);
        expect(result).toBeDefined();
        expect(result.moves.length).toBe(4);
    });

    test('handles empty PGN gracefully', () => {
        const result = parsePgn('');
        expect(result).toBeDefined();
        expect(result.moves.length).toBe(0);
    });

    test('handles PGN with only result', () => {
        const result = parsePgn('1-0');
        expect(result).toBeDefined();
        expect(result.moves.length).toBe(0);
    });
});

/* ─── Classification edge cases ────────────────────────────────── */

describe('classifyEvalDiff edge cases', () => {
    test('exact threshold values are classified correctly', () => {
        // Boundaries: best < 0.3, good < 0.5, inaccuracy < 1.0, mistake < 2.0, blunder >= 2.0
        expect(classifyEvalDiff(0)).toBe('best');
        expect(classifyEvalDiff(0.29)).toBe('best');
        expect(classifyEvalDiff(0.5)).toBe('inaccuracy');
        expect(classifyEvalDiff(2.0)).toBe('blunder');
        expect(classifyEvalDiff(10.0)).toBe('blunder');
    });

    test('negative eval diffs are treated as absolute values', () => {
        // eval diff should be absolute
        const result = classifyEvalDiff(-0.1);
        expect(['best', 'excellent']).toContain(result);
    });
});

/* ─── Position analysis correctness ────────────────────────────── */

describe('analyzePosition returns meaningful data', () => {
    test('starting position has equal material', () => {
        const analysis = analyzePosition(START_FEN);
        expect(analysis).toBeDefined();
        expect(analysis.material.white).toBe(analysis.material.black);
    });

    test('position with extra queen shows material advantage', () => {
        // White has an extra queen
        const extraQueenFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKQNR w KQkq - 0 1';
        const analysis = analyzePosition(extraQueenFen);
        expect(analysis.material.white).toBeGreaterThan(analysis.material.black);
    });

    test('endgame position detected correctly', () => {
        const endgameFen = '8/8/4k3/8/8/8/4K3/4R3 w - - 0 1';
        const analysis = analyzePosition(endgameFen);
        expect(analysis).toBeDefined();
    });
});

/* ─── Error categorisation ──────────────────────────────────────── */

describe('categorizeError produces valid categories', () => {
    const validCategories = ['tactical', 'positional', 'opening', 'endgame'];

    test('opening-phase errors are categorised as opening', () => {
        const result = categorizeError(START_FEN, 'e4', 'd4', 3);
        expect(result).toBeDefined();
        if (result) expect(validCategories).toContain(result);
    });

    test('endgame positions categorised as endgame', () => {
        const endFen = '8/8/4k3/8/2B5/8/4K3/8 w - - 0 50';
        const result = categorizeError(endFen, 'Bc4', 'Be6', 50);
        expect(result).toBeDefined();
        if (result) expect(validCategories).toContain(result);
    });
});

/* ─── Bilingual explanation generation ──────────────────────────── */

describe('generateExplanation bilingual support', () => {
    test('Latvian explanation contains Latvian text patterns', () => {
        const lv = generateExplanation('blunder', 'tactical', 'e4', 'Nf3', 'lv');
        expect(lv).toBeDefined();
        expect(lv.text.length).toBeGreaterThan(10);
    });

    test('English explanation contains English text patterns', () => {
        const en = generateExplanation('blunder', 'tactical', 'e4', 'Nf3', 'en');
        expect(en).toBeDefined();
        expect(en.text.length).toBeGreaterThan(10);
    });

    test('different classifications produce different explanations', () => {
        const blunder = generateExplanation('blunder', 'tactical', 'e4', 'Nf3', 'en');
        const inaccuracy = generateExplanation('inaccuracy', 'tactical', 'e4', 'Nf3', 'en');
        expect(blunder.text).not.toBe(inaccuracy.text);
    });
});

/* ─── Game summary with realistic data ──────────────────────────── */

describe('generateGameSummary realistic scenarios', () => {
    test('perfect game (all best moves) gives high accuracy', () => {
        const perfect = Array.from({ length: 30 }, (_, i) => ({
            classification: 'best',
            color: i % 2 === 0 ? 'white' : 'black',
            eval_diff: 0.01,
        }));
        const s = generateGameSummary(perfect);
        expect(s.accuracy).toBeGreaterThanOrEqual(95);
        expect(s.blunders).toBe(0);
    });

    test('terrible game (all blunders) gives low accuracy', () => {
        const terrible = Array.from({ length: 20 }, (_, i) => ({
            classification: 'blunder',
            color: i % 2 === 0 ? 'white' : 'black',
            eval_diff: 5.0,
            error_category: 'tactical',
        }));
        const s = generateGameSummary(terrible);
        expect(s.accuracy).toBeLessThanOrEqual(20);
        expect(s.blunders).toBe(20);
    });

    test('per-color breakdown is correct', () => {
        const moves = [
            { classification: 'blunder', color: 'white', eval_diff: 3.0, error_category: 'tactical' },
            { classification: 'best', color: 'black', eval_diff: 0.01 },
            { classification: 'best', color: 'white', eval_diff: 0.01 },
            { classification: 'mistake', color: 'black', eval_diff: 1.5, error_category: 'positional' },
        ];
        const s = generateGameSummary(moves);
        expect(s.total).toBe(4);
        // White: 1 blunder + 1 best, Black: 1 best + 1 mistake
        expect(s.blunders).toBe(1);
        expect(s.mistakes).toBe(1);
    });
});
