import { describe, test, expect } from 'vitest';
import {
    createGame, makeMove, getLegalMoves, isLegalMove,
    parsePgn, classifyEvalDiff, categorizeError,
    analyzePosition, generateExplanation, generateGameSummary,
    ERROR_CATEGORIES, Chess
} from '../../resources/js/services/chess';

const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
// A middlegame FEN (after 1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Ba4 Nf6 5.O-O Be7 6.Re1 b5 7.Bb3 d6 8.c3 O-O)
const MID_FEN = 'r1bq1rk1/2ppbppp/p1n2n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - - 1 9';
// An endgame FEN (K+R vs K)
const END_FEN = '8/8/8/4k3/8/8/8/4K2R w K - 0 1';

describe('createGame', () => {
    test('returns a default starting position', () => {
        const game = createGame();
        expect(game.fen()).toBe(START_FEN);
    });

    test('accepts a custom FEN', () => {
        const game = createGame(END_FEN);
        expect(game.fen()).toBe(END_FEN);
    });

    test('returns a Chess instance', () => {
        expect(createGame()).toBeInstanceOf(Chess);
    });
});

describe('makeMove', () => {
    test('makes a legal move and returns SAN + UCI', () => {
        const result = makeMove(START_FEN, 'e2', 'e4');
        expect(result).not.toBeNull();
        expect(result.san).toBe('e4');
        expect(result.uci).toBe('e2e4');
        // FEN after 1.e4 uses '4P3' for the pawn on e4
        expect(result.fen).toContain('4P3');
    });

    test('returns null for an illegal move', () => {
        expect(makeMove(START_FEN, 'e2', 'e5')).toBeNull();
    });

    test('returns null for nonsensical squares', () => {
        expect(makeMove(START_FEN, 'z9', 'z1')).toBeNull();
    });

    test('detects checkmate', () => {
        const scholarFen = 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5Q2/PPPP1PPP/RNB1K1NR w KQkq - 2 3';
        const result = makeMove(scholarFen, 'f3', 'f7');
        expect(result).not.toBeNull();
        expect(result.isCheckmate).toBe(true);
        expect(result.isGameOver).toBe(true);
    });

    test('handles pawn promotion', () => {
        const promoFen = '8/P7/8/8/8/8/8/4K2k w - - 0 1';
        const result = makeMove(promoFen, 'a7', 'a8', 'q');
        expect(result).not.toBeNull();
        expect(result.san).toContain('=Q');
    });

    test('detects captures', () => {
        const capFen = 'rnbqkbnr/pppp1ppp/8/4p3/3PP3/8/PPP2PPP/RNBQKBNR b KQkq d3 0 2';
        const result = makeMove(capFen, 'e5', 'd4');
        expect(result).not.toBeNull();
        expect(result.captured).toBeDefined();
    });
});

describe('getLegalMoves', () => {
    test('returns 20 legal moves from starting position', () => {
        expect(getLegalMoves(START_FEN).length).toBe(20);
    });

    test('each move has from, to, san, uci', () => {
        for (const m of getLegalMoves(START_FEN)) {
            expect(m).toHaveProperty('from');
            expect(m).toHaveProperty('to');
            expect(m).toHaveProperty('san');
            expect(m).toHaveProperty('uci');
        }
    });

    test('returns 0 moves in checkmate', () => {
        const mateFen = 'rnb1kbnr/pppp1ppp/8/4p3/6Pq/5P2/PPPPP2P/RNBQKBNR w KQkq - 1 3';
        expect(getLegalMoves(mateFen).length).toBe(0);
    });
});

describe('isLegalMove', () => {
    test('e2-e4 is legal', () => expect(isLegalMove(START_FEN, 'e2', 'e4')).toBe(true));
    test('e2-e5 is illegal', () => expect(isLegalMove(START_FEN, 'e2', 'e5')).toBe(false));
    test('opponent piece is illegal', () => expect(isLegalMove(START_FEN, 'e7', 'e5')).toBe(false));
});

describe('parsePgn', () => {
    test('parses simple PGN', () => {
        const { moves } = parsePgn('1. e4 e5 2. Nf3 Nc6 3. Bb5 a6');
        expect(moves.length).toBe(6);
        expect(moves[0].san).toBe('e4');
        expect(moves[0].color).toBe('white');
        expect(moves[1].san).toBe('e5');
        expect(moves[1].color).toBe('black');
    });

    test('each move has fen_before and fen_after', () => {
        const { moves } = parsePgn('1. d4 d5 2. c4');
        for (const m of moves) {
            expect(m.fen_before).toBeTruthy();
            expect(m.fen_after).toBeTruthy();
            expect(m.fen_before).not.toBe(m.fen_after);
        }
    });

    test('computes correct move numbers', () => {
        const { moves } = parsePgn('1. e4 e5 2. Nf3 Nc6');
        expect(moves[0].moveNumber).toBe(1);
        expect(moves[1].moveNumber).toBe(1);
        expect(moves[2].moveNumber).toBe(2);
    });

    test('strips annotations and comments', () => {
        const { moves } = parsePgn('1. e4 {best} e5 (1...c5) 2. Nf3 $1 Nc6');
        expect(moves.length).toBe(4);
    });

    test('handles result string', () => {
        const { moves } = parsePgn('1. e4 e5 2. Nf3 Nc6 1-0');
        expect(moves.length).toBe(4);
    });

    test('returns empty moves for empty PGN', () => {
        expect(parsePgn('').moves.length).toBe(0);
    });
});

describe('classifyEvalDiff', () => {
    test('tiny loss → best', () => expect(classifyEvalDiff(0.5, 0.48, 'white')).toBe('best'));
    test('small loss → excellent', () => expect(classifyEvalDiff(0.5, 0.40, 'white')).toBe('excellent'));
    test('moderate loss → good', () => expect(classifyEvalDiff(0.5, 0.20, 'white')).toBe('good'));
    test('notable loss → inaccuracy', () => expect(classifyEvalDiff(1.0, 0.30, 'white')).toBe('inaccuracy'));
    test('serious loss → mistake', () => expect(classifyEvalDiff(1.5, 0.0, 'white')).toBe('mistake'));
    test('catastrophic loss → blunder', () => expect(classifyEvalDiff(1.5, -2.0, 'white')).toBe('blunder'));
    test('black perspective inverts', () => expect(classifyEvalDiff(-1.0, 1.0, 'black')).toBe('mistake'));
    test('improvement → best', () => expect(classifyEvalDiff(0.0, 0.5, 'white')).toBe('best'));
});

describe('categorizeError', () => {
    test('early move with FEN → opening', () => {
        const result = categorizeError(2, 40, {
            san: 'Nf3',
            fen_before: 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1',
        });
        expect(result).toBe('opening');
    });

    test('capture in middlegame with FEN → tactical', () => {
        const result = categorizeError(26, 40, {
            san: 'Bxe5', captured: 'p',
            fen_before: MID_FEN,
        });
        expect(result).toBe('tactical');
    });

    test('check move with FEN → tactical', () => {
        const result = categorizeError(26, 40, {
            san: 'Qd8+',
            fen_before: MID_FEN,
        });
        expect(result).toBe('tactical');
    });

    test('quiet middlegame move with FEN → positional', () => {
        const result = categorizeError(26, 40, {
            san: 'Bd3',
            fen_before: MID_FEN,
        });
        expect(result).toBe('positional');
    });

    test('castling with FEN → tactical', () => {
        const result = categorizeError(26, 40, {
            san: 'O-O',
            fen_before: MID_FEN,
        });
        expect(result).toBe('tactical');
    });
});

describe('analyzePosition', () => {
    test('starting position has equal material', () => {
        const a = analyzePosition(START_FEN);
        expect(a.white.material).toBe(a.black.material);
    });

    test('detects king squares', () => {
        const a = analyzePosition(START_FEN);
        expect(a.white.kingSquare).toBe('e1');
        expect(a.black.kingSquare).toBe('e8');
    });

    test('counts center pawns', () => {
        const a = analyzePosition('rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq e6 0 2');
        expect(a.white.centerPawns).toBeGreaterThanOrEqual(1);
        expect(a.black.centerPawns).toBeGreaterThanOrEqual(1);
    });
});

describe('generateExplanation', () => {
    test('returns an object with isPositive=true for best/excellent/good', () => {
        const best = generateExplanation('best', 'tactical', 'Nf3', 'Nf3');
        expect(best).toBeDefined();
        expect(best.isPositive).toBe(true);
        expect(best.text).toBeTruthy();

        const excellent = generateExplanation('excellent', 'positional', 'e4', 'e4');
        expect(excellent.isPositive).toBe(true);

        const good = generateExplanation('good', 'opening', 'c4', 'c4');
        expect(good.isPositive).toBe(true);
    });

    test('returns error explanation for inaccuracy', () => {
        const result = generateExplanation('inaccuracy', 'tactical', 'Bc4', 'Nf3', 'lv');
        expect(result).toBeDefined();
        expect(result.isPositive).toBeFalsy();
        expect(result.text).toBeTruthy();
    });

    test('returns error explanation for blunder', () => {
        const result = generateExplanation('blunder', 'endgame', 'Ke2', 'Kd3', 'lv');
        expect(result).toBeDefined();
        expect(result.text).toBeTruthy();
    });

    test('produces English text when locale = en', () => {
        const result = generateExplanation('mistake', 'tactical', 'Bg5', 'Nf3', 'en');
        expect(result).toBeDefined();
        expect(result.text).toMatch(/[a-zA-Z]/);
    });

    test('includes best move in text or tip', () => {
        const result = generateExplanation('blunder', 'tactical', 'h4', 'Nf3', 'lv');
        const combined = (result.text || '') + (result.tip || '');
        expect(combined).toContain('Nf3');
    });

    test('handles missing best move', () => {
        const result = generateExplanation('mistake', 'tactical', 'h4', null, 'lv');
        expect(result).toBeDefined();
        expect(result.text).toBeTruthy();
    });
});

describe('generateGameSummary', () => {
    const mockMoves = [
        { classification: 'best', color: 'white', eval_diff: 0.01 },
        { classification: 'best', color: 'black', eval_diff: 0.02 },
        { classification: 'inaccuracy', color: 'white', eval_diff: 0.6, error_category: 'tactical' },
        { classification: 'mistake', color: 'black', eval_diff: 1.5, error_category: 'positional' },
        { classification: 'blunder', color: 'white', eval_diff: 3.0, error_category: 'endgame' },
        { classification: 'good', color: 'black', eval_diff: 0.2 },
    ];

    test('counts total moves', () => {
        const s = generateGameSummary(mockMoves);
        expect(s.total).toBe(6);
    });

    test('counts errors correctly', () => {
        const s = generateGameSummary(mockMoves);
        expect(s.inaccuracies).toBe(1);
        expect(s.mistakes).toBe(1);
        expect(s.blunders).toBe(1);
        expect(s.errorTotal).toBe(3);
    });

    test('computes accuracy percentage', () => {
        const s = generateGameSummary(mockMoves);
        expect(s.accuracy).toBeDefined();
        expect(typeof s.accuracy).toBe('number');
        expect(s.accuracy).toBeGreaterThanOrEqual(0);
        expect(s.accuracy).toBeLessThanOrEqual(100);
    });

    test('determines performance level', () => {
        const s = generateGameSummary(mockMoves);
        expect(s.level).toBeDefined();
        expect(s.levelText).toBeTruthy();
    });

    test('returns null for empty move list', () => {
        expect(generateGameSummary([])).toBeNull();
    });
});

describe('ERROR_CATEGORIES', () => {
    test('has all four categories with icon and key', () => {
        for (const cat of ['tactical', 'positional', 'opening', 'endgame']) {
            expect(ERROR_CATEGORIES[cat]).toHaveProperty('icon');
            expect(ERROR_CATEGORIES[cat]).toHaveProperty('key');
        }
    });
});
