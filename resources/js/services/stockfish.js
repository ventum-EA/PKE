/**
 * Stockfish WASM wrapper — communicates with the engine via Web Worker.
 *
 * The local `/stockfish.js` (v16) requires SharedArrayBuffer + COOP/COEP
 * headers + a companion worker file. If any of those are missing the
 * Worker crashes immediately. The CDN fallback (v10, single-threaded)
 * works everywhere without special headers.
 *
 * Strategy: try local → catch → CDN → catch → give up.
 */

const STOCKFISH_LOCAL = '/stockfish.js';
const STOCKFISH_CDN   = 'https://cdnjs.cloudflare.com/ajax/libs/stockfish.js/10.0.2/stockfish.js';

/**
 * Create a Worker that actually responds to UCI.
 * Rejects if the Worker errors out before sending `uciok`.
 */
function tryWorker(url, isCDN) {
    return new Promise(async (resolve, reject) => {
        let worker;
        const timeout = setTimeout(() => {
            if (worker) worker.terminate();
            reject(new Error('Worker init timed out'));
        }, 5000);

        try {
            if (isCDN) {
                const resp = await fetch(url);
                if (!resp.ok) throw new Error(`CDN fetch failed: ${resp.status}`);
                const text = await resp.text();
                const blob = new Blob([text], { type: 'application/javascript' });
                worker = new Worker(URL.createObjectURL(blob));
            } else {
                worker = new Worker(url);
            }
        } catch (err) {
            clearTimeout(timeout);
            return reject(err);
        }

        worker.onerror = (err) => {
            clearTimeout(timeout);
            worker.terminate();
            reject(err);
        };
        worker.onmessage = (e) => {
            if (typeof e.data === 'string' && e.data.includes('uciok')) {
                clearTimeout(timeout);
                resolve(worker);
            }
        };
        worker.postMessage('uci');
    });
}

async function createReadyWorker() {
    // 1. Try local engine (Stockfish 16 — needs SharedArrayBuffer)
    if (typeof SharedArrayBuffer !== 'undefined') {
        try {
            const r = await fetch(STOCKFISH_LOCAL, { method: 'HEAD' });
            if (r.ok) {
                const w = await tryWorker(STOCKFISH_LOCAL, false);
                console.info('[stockfish] local v16 engine ready');
                return w;
            }
        } catch {
            console.warn('[stockfish] local engine failed, trying CDN…');
        }
    } else {
        console.warn('[stockfish] SharedArrayBuffer unavailable, skipping local v16');
    }

    // 2. CDN fallback (Stockfish 10 — single-threaded, always works)
    try {
        const w = await tryWorker(STOCKFISH_CDN, true);
        console.info('[stockfish] CDN v10 engine ready');
        return w;
    } catch (err) {
        console.error('[stockfish] all engines failed:', err);
        throw new Error('Neviena Stockfish versija nav pieejama.');
    }
}

class StockfishEngine {
    constructor() {
        this.worker = null;
        this.ready = false;
        this.analyzing = false;
        this.listeners = new Map();
        this.evalHistory = [];
        this._resolveReady = null;
        this._readyPromise = null;
    }

    async init() {
        // Already fully ready — fast path for any subsequent caller
        if (this.ready) return true;
        // Init is in progress — share the same promise
        if (this._readyPromise) return this._readyPromise;

        this._readyPromise = (async () => {
            this.worker = await createReadyWorker();
            // Worker already responded to 'uci' in tryWorker, so
            // it will send 'uciok' (or already did). Wire up handler.
            this.ready = true;
            this.worker.onmessage = (e) => this._handleMessage(e.data);
            this.worker.onerror = (err) => {
                console.error('Stockfish worker error:', err);
            };
            // Engine is already past 'uciok', send isready
            this.send('isready');
            return true;
        })();

        return this._readyPromise;
    }

    send(cmd) {
        if (this.worker) {
            this.worker.postMessage(cmd);
        }
    }

    _handleMessage(line) {
        if (typeof line !== 'string') return;

        // Engine ready
        if (line === 'uciok') {
            this.ready = true;
            this.send('isready');
        }

        if (line === 'readyok') {
            if (this._resolveReady) {
                this._resolveReady(true);
                this._resolveReady = null;
            }
        }

        // Parse evaluation info
        if (line.startsWith('info') && line.includes('score')) {
            const parsed = this._parseInfo(line);
            if (parsed) {
                this._emit('info', parsed);
            }
        }

        // Best move found
        if (line.startsWith('bestmove')) {
            const parts = line.split(' ');
            const bestMove = parts[1];
            const ponder = parts[3] || null;
            this.analyzing = false;
            this._emit('bestmove', { bestMove, ponder });
        }
    }

    _parseInfo(line) {
        const info = {};

        const depthMatch = line.match(/depth (\d+)/);
        if (depthMatch) info.depth = parseInt(depthMatch[1]);

        const scoreMatch = line.match(/score (cp|mate) (-?\d+)/);
        if (scoreMatch) {
            info.scoreType = scoreMatch[1];
            info.scoreValue = parseInt(scoreMatch[2]);
            // Convert centipawns to pawns
            if (info.scoreType === 'cp') {
                info.eval = info.scoreValue / 100;
            } else {
                // Mate score: use large value with sign
                info.eval = info.scoreValue > 0 ? 100 : -100;
                info.mateIn = Math.abs(info.scoreValue);
            }
        }

        const pvMatch = line.match(/pv (.+)/);
        if (pvMatch) {
            info.pv = pvMatch[1].trim().split(' ');
        }

        const nodesMatch = line.match(/nodes (\d+)/);
        if (nodesMatch) info.nodes = parseInt(nodesMatch[1]);

        const npsMatch = line.match(/nps (\d+)/);
        if (npsMatch) info.nps = parseInt(npsMatch[1]);

        return Object.keys(info).length > 0 ? info : null;
    }

    /**
     * Analyze a position (FEN) to a given depth.
     * Returns a promise that resolves with { bestMove, eval, depth, pv }
     */
    async analyze(fen, depth = 18) {
        await this.init();
        this.analyzing = true;

        return new Promise((resolve) => {
            let lastInfo = {};

            const infoHandler = (info) => {
                lastInfo = { ...lastInfo, ...info };
            };
            const bestHandler = (result) => {
                this._off('info', infoHandler);
                this._off('bestmove', bestHandler);
                resolve({
                    bestMove: result.bestMove,
                    ponder: result.ponder,
                    eval: lastInfo.eval ?? 0,
                    depth: lastInfo.depth ?? depth,
                    pv: lastInfo.pv || [result.bestMove],
                    mateIn: lastInfo.mateIn || null,
                    scoreType: lastInfo.scoreType || 'cp',
                });
            };

            this._on('info', infoHandler);
            this._on('bestmove', bestHandler);

            this.send('stop');
            this.send('ucinewgame');
            this.send(`position fen ${fen}`);
            this.send(`go depth ${depth}`);
        });
    }

    /**
     * Analyze a full game given an array of FENs.
     * Calls onProgress(moveIndex, totalMoves, result) for each position.
     * Returns array of analysis results per move.
     */
    async analyzeGame(fens, depth = 15, onProgress = null) {
        await this.init();
        const results = [];

        for (let i = 0; i < fens.length; i++) {
            const result = await this.analyze(fens[i], depth);
            results.push(result);
            if (onProgress) onProgress(i, fens.length, result);
        }

        return results;
    }

    /**
     * Get engine's move for a position (for play-vs-engine).
     * skillLevel: 0-20 (maps to Stockfish Skill Level option)
     */
    async getMove(fen, skillLevel = 10, moveTime = 1000) {
        await this.init();

        return new Promise((resolve) => {
            const bestHandler = (result) => {
                this._off('bestmove', bestHandler);
                resolve(result.bestMove);
            };

            this._on('bestmove', bestHandler);

            this.send('stop');
            this.send(`setoption name Skill Level value ${Math.min(20, Math.max(0, skillLevel))}`);
            this.send(`position fen ${fen}`);
            this.send(`go movetime ${moveTime}`);
        });
    }

    stop() {
        if (this.worker && this.analyzing) {
            this.send('stop');
            this.analyzing = false;
        }
    }

    destroy() {
        this.stop();
        if (this.worker) {
            this.worker.terminate();
            this.worker = null;
        }
        this.ready = false;
        this.listeners.clear();
    }

    // Simple event system
    _on(event, fn) {
        if (!this.listeners.has(event)) this.listeners.set(event, []);
        this.listeners.get(event).push(fn);
    }

    _off(event, fn) {
        const arr = this.listeners.get(event);
        if (arr) {
            const idx = arr.indexOf(fn);
            if (idx >= 0) arr.splice(idx, 1);
        }
    }

    _emit(event, data) {
        const arr = this.listeners.get(event);
        if (arr) arr.forEach(fn => fn(data));
    }
}

// Singleton — share across all components
let instance = null;

export function useStockfish() {
    if (!instance) instance = new StockfishEngine();
    return instance;
}

export default StockfishEngine;
