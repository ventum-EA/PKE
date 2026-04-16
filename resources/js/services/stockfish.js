/**
 * Stockfish WASM wrapper — communicates with the engine via Web Worker.
 *
 * Uses stockfish.js 10.0.2 (single-threaded, no SharedArrayBuffer needed).
 * The script is fetched as text and loaded via a Blob URL to avoid
 * cross-origin Worker restrictions.
 */

const STOCKFISH_URL = 'https://cdnjs.cloudflare.com/ajax/libs/stockfish.js/10.0.2/stockfish.js';

async function createWorker() {
    // Fetch the script as text and create a Blob URL worker
    // This bypasses cross-origin Worker restrictions
    const response = await fetch(STOCKFISH_URL);
    const scriptText = await response.text();
    const blob = new Blob([scriptText], { type: 'application/javascript' });
    const blobUrl = URL.createObjectURL(blob);
    const worker = new Worker(blobUrl);
    return worker;
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
        if (this.ready) return true;
        if (this._readyPromise) return this._readyPromise;

        this._readyPromise = new Promise(async (resolve, reject) => {
            try {
                this.worker = await createWorker();
                this.worker.onmessage = (e) => this._handleMessage(e.data);
                this.worker.onerror = (err) => {
                    console.error('Stockfish worker error:', err);
                    this._readyPromise = null;
                    this.worker = null;
                    reject(err);
                };
                this._resolveReady = resolve;
                this.send('uci');
            } catch (err) {
                console.error('Failed to init Stockfish:', err);
                this._readyPromise = null;
                reject(err);
            }
        });

        return this._readyPromise;
    }

    send(cmd) {
        if (this.worker) {
            this.worker.postMessage(cmd);
        }
    }

    _handleMessage(line) {
        if (typeof line !== 'string') return;

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

        if (line.startsWith('info') && line.includes('score')) {
            const parsed = this._parseInfo(line);
            if (parsed) {
                this._emit('info', parsed);
            }
        }

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
            if (info.scoreType === 'cp') {
                info.eval = info.scoreValue / 100;
            } else {
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

    async analyze(fen, depth = 18) {
        await this.init();
        this.analyzing = true;

        // Determine side to move — Stockfish returns eval from side-to-move's POV
        const isBlackToMove = fen.split(' ')[1] === 'b';

        return new Promise((resolve) => {
            let lastInfo = {};
            let timer = null;

            const finish = (bestMove, ponder) => {
                if (timer) clearTimeout(timer);
                this._off('info', infoHandler);
                this._off('bestmove', bestHandler);
                this.analyzing = false;

                // Normalize eval to WHITE's perspective (negate if black to move)
                let evalValue = lastInfo.eval ?? 0;
                if (isBlackToMove) evalValue = -evalValue;

                resolve({
                    bestMove: bestMove || '(none)',
                    ponder: ponder || null,
                    eval: evalValue,
                    depth: lastInfo.depth ?? depth,
                    pv: lastInfo.pv || [bestMove || '(none)'],
                    mateIn: lastInfo.mateIn || null,
                    scoreType: lastInfo.scoreType || 'cp',
                });
            };

            const infoHandler = (info) => {
                lastInfo = { ...lastInfo, ...info };
            };
            const bestHandler = (result) => {
                finish(result.bestMove, result.ponder);
            };

            // Timeout: if Stockfish doesn't respond in 15s (terminal position), resolve anyway
            timer = setTimeout(() => finish('(none)', null), 15000);

            this._on('info', infoHandler);
            this._on('bestmove', bestHandler);

            this.send('stop');
            this.send('ucinewgame');
            this.send(`position fen ${fen}`);
            this.send(`go depth ${depth}`);
        });
    }

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

let instance = null;

export function useStockfish() {
    if (!instance) instance = new StockfishEngine();
    return instance;
}

export default StockfishEngine;
