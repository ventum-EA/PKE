/**
 * Tests for the Stockfish WASM wrapper service.
 *
 * Since we can't run the actual Stockfish WASM engine in Vitest,
 * these tests verify the StockfishEngine class structure, UCI
 * protocol handling, and the event system by injecting a mock Worker.
 */
import { describe, test, expect, vi, beforeEach } from 'vitest';

// We import the default export (StockfishEngine class) to test it
// directly, rather than the singleton useStockfish() which has
// global state that persists across tests.
import StockfishEngine from '../../resources/js/services/stockfish';

/* ─── StockfishEngine class structure ─────────────────────────── */

describe('StockfishEngine class', () => {
    let engine;

    beforeEach(() => {
        engine = new StockfishEngine();
    });

    test('starts uninitialized', () => {
        expect(engine.ready).toBe(false);
        expect(engine.analyzing).toBe(false);
        expect(engine.worker).toBeNull();
    });

    test('has all expected public methods', () => {
        expect(typeof engine.init).toBe('function');
        expect(typeof engine.send).toBe('function');
        expect(typeof engine.analyze).toBe('function');
        expect(typeof engine.analyzeGame).toBe('function');
        expect(typeof engine.getMove).toBe('function');
        expect(typeof engine.stop).toBe('function');
        expect(typeof engine.destroy).toBe('function');
    });

    test('send does nothing without a worker', () => {
        // Should not throw
        expect(() => engine.send('uci')).not.toThrow();
    });

    test('stop does nothing when not analyzing', () => {
        expect(() => engine.stop()).not.toThrow();
        expect(engine.analyzing).toBe(false);
    });

    test('destroy clears internal state', () => {
        engine.ready = true;
        engine.analyzing = true;
        engine.destroy();
        expect(engine.ready).toBe(false);
        expect(engine.worker).toBeNull();
    });
});

/* ─── UCI info line parsing ──────────────────────────────────────── */

describe('UCI info parsing', () => {
    let engine;

    beforeEach(() => {
        engine = new StockfishEngine();
    });

    test('parses centipawn score', () => {
        const parsed = engine._parseInfo('info depth 20 score cp 35 nodes 1234');
        expect(parsed).toBeDefined();
        expect(parsed.depth).toBe(20);
        expect(parsed.scoreType).toBe('cp');
        expect(parsed.scoreValue).toBe(35);
        expect(parsed.eval).toBeCloseTo(0.35);
        expect(parsed.nodes).toBe(1234);
    });

    test('parses mate score', () => {
        const parsed = engine._parseInfo('info depth 15 score mate 3');
        expect(parsed).toBeDefined();
        expect(parsed.scoreType).toBe('mate');
        expect(parsed.mateIn).toBe(3);
        expect(parsed.eval).toBe(100); // Large positive for white mating
    });

    test('parses negative mate score', () => {
        const parsed = engine._parseInfo('info depth 15 score mate -2');
        expect(parsed).toBeDefined();
        expect(parsed.eval).toBe(-100); // Large negative for black mating
        expect(parsed.mateIn).toBe(2);
    });

    test('parses principal variation', () => {
        const parsed = engine._parseInfo('info depth 18 score cp 25 pv e2e4 e7e5 g1f3');
        expect(parsed).toBeDefined();
        expect(parsed.pv).toEqual(['e2e4', 'e7e5', 'g1f3']);
    });

    test('parses NPS', () => {
        const parsed = engine._parseInfo('info depth 10 score cp 0 nps 2500000');
        expect(parsed.nps).toBe(2500000);
    });

    test('returns null for info without useful data', () => {
        const parsed = engine._parseInfo('info string hello');
        expect(parsed).toBeNull();
    });

    test('handles line without score keyword', () => {
        const parsed = engine._parseInfo('info depth 5 nodes 100');
        expect(parsed).toBeDefined();
        expect(parsed.depth).toBe(5);
        expect(parsed.eval).toBeUndefined();
    });
});

/* ─── Event system ──────────────────────────────────────────────── */

describe('event system', () => {
    let engine;

    beforeEach(() => {
        engine = new StockfishEngine();
    });

    test('on/emit/off cycle works', () => {
        const handler = vi.fn();
        engine._on('test', handler);
        engine._emit('test', { data: 42 });
        expect(handler).toHaveBeenCalledWith({ data: 42 });

        engine._off('test', handler);
        engine._emit('test', { data: 99 });
        expect(handler).toHaveBeenCalledTimes(1); // Not called again
    });

    test('multiple listeners on same event', () => {
        const a = vi.fn();
        const b = vi.fn();
        engine._on('info', a);
        engine._on('info', b);
        engine._emit('info', {});
        expect(a).toHaveBeenCalledTimes(1);
        expect(b).toHaveBeenCalledTimes(1);
    });

    test('removing one listener does not affect others', () => {
        const a = vi.fn();
        const b = vi.fn();
        engine._on('bestmove', a);
        engine._on('bestmove', b);
        engine._off('bestmove', a);
        engine._emit('bestmove', {});
        expect(a).not.toHaveBeenCalled();
        expect(b).toHaveBeenCalledTimes(1);
    });
});

/* ─── Message handler routing ────────────────────────────────────── */

describe('_handleMessage routing', () => {
    let engine;

    beforeEach(() => {
        engine = new StockfishEngine();
    });

    test('bestmove line emits bestmove event with move and ponder', () => {
        const handler = vi.fn();
        engine._on('bestmove', handler);
        engine._handleMessage('bestmove e2e4 ponder e7e5');
        expect(handler).toHaveBeenCalledWith({
            bestMove: 'e2e4',
            ponder: 'e7e5',
        });
        expect(engine.analyzing).toBe(false);
    });

    test('bestmove without ponder sets ponder to null', () => {
        const handler = vi.fn();
        engine._on('bestmove', handler);
        engine._handleMessage('bestmove e2e4');
        expect(handler).toHaveBeenCalledWith({
            bestMove: 'e2e4',
            ponder: null,
        });
    });

    test('info line with score emits info event', () => {
        const handler = vi.fn();
        engine._on('info', handler);
        engine._handleMessage('info depth 12 score cp 45 nodes 50000');
        expect(handler).toHaveBeenCalledTimes(1);
        expect(handler.mock.calls[0][0].depth).toBe(12);
        expect(handler.mock.calls[0][0].eval).toBeCloseTo(0.45);
    });

    test('uciok sets ready flag', () => {
        engine.ready = false;
        engine._handleMessage('uciok');
        expect(engine.ready).toBe(true);
    });

    test('non-string messages are ignored', () => {
        expect(() => engine._handleMessage(null)).not.toThrow();
        expect(() => engine._handleMessage(42)).not.toThrow();
        expect(() => engine._handleMessage(undefined)).not.toThrow();
    });
});
