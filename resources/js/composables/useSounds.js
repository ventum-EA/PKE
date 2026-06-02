/**
 * Sound effects composable using Web Audio API.
 *
 * Generates all sounds programmatically — no external audio files needed.
 * Respects user's sound_enabled preference from the auth store.
 *
 * Usage:
 *   const { playMove, playCapture, playCheck, playGameStart, playGameEnd, playAchievement, playNotify, playLowTime } = useSounds();
 */

import { useAuthStore } from '../stores/auth';

let audioCtx = null;
let unlocked = false;

function getCtx() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
    }
    return audioCtx;
}

// Unlock audio on first user interaction (browsers block audio until gesture)
function unlockAudio() {
    if (unlocked) return;
    try {
        const ctx = getCtx();
        // Create a silent buffer to kick-start the context
        const buf = ctx.createBuffer(1, 1, ctx.sampleRate);
        const src = ctx.createBufferSource();
        src.buffer = buf;
        src.connect(ctx.destination);
        src.start(0);
        unlocked = true;
    } catch { /* intentionally silenced */ }
}

// Attach unlock to common user gestures
if (typeof document !== 'undefined') {
    const events = ['click', 'touchstart', 'keydown'];
    const handler = () => {
        unlockAudio();
        events.forEach(e => document.removeEventListener(e, handler, true));
    };
    events.forEach(e => document.addEventListener(e, handler, { once: true, capture: true }));
}

function isEnabled() {
    try {
        const auth = useAuthStore();
        return auth.user?.sound_enabled !== false;
    } catch {
        return true;
    }
}

// ── Tone generators ──────────────────────────────────

function playTone(freq, duration = 0.08, type = 'sine', volume = 0.15) {
    if (!isEnabled()) return;
    try {
        const ctx = getCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, ctx.currentTime);
        gain.gain.setValueAtTime(volume, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
        osc.connect(gain).connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + duration);
    } catch { /* intentionally silenced */ }
}

function playNoise(duration = 0.03, volume = 0.08) {
    if (!isEnabled()) return;
    try {
        const ctx = getCtx();
        const bufferSize = ctx.sampleRate * duration;
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) data[i] = (Math.random() * 2 - 1) * 0.5;
        const source = ctx.createBufferSource();
        source.buffer = buffer;
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(volume, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
        source.connect(gain).connect(ctx.destination);
        source.start();
    } catch { /* intentionally silenced */ }
}

// ── Sound effects ────────────────────────────────────

export function useSounds() {
    /** Standard piece placement */
    function playMove() {
        playNoise(0.04, 0.12);
        playTone(300, 0.06, 'triangle', 0.06);
    }

    /** Piece capture — slightly more aggressive */
    function playCapture() {
        playNoise(0.06, 0.18);
        playTone(220, 0.08, 'sawtooth', 0.08);
    }

    /** Check — sharp ping */
    function playCheck() {
        playTone(880, 0.12, 'sine', 0.15);
        setTimeout(() => playTone(1100, 0.08, 'sine', 0.1), 60);
    }

    /** Castling — double thump */
    function playCastle() {
        playNoise(0.04, 0.1);
        setTimeout(() => playNoise(0.04, 0.1), 80);
    }

    /** Game start — ascending arpeggio */
    function playGameStart() {
        playTone(440, 0.1, 'triangle', 0.1);
        setTimeout(() => playTone(554, 0.1, 'triangle', 0.1), 100);
        setTimeout(() => playTone(659, 0.15, 'triangle', 0.12), 200);
    }

    /** Game end — descending chord */
    function playGameEnd() {
        playTone(659, 0.2, 'sine', 0.1);
        setTimeout(() => playTone(554, 0.2, 'sine', 0.1), 150);
        setTimeout(() => playTone(440, 0.3, 'sine', 0.12), 300);
    }

    /** Win — triumphant */
    function playWin() {
        playTone(523, 0.12, 'triangle', 0.12);
        setTimeout(() => playTone(659, 0.12, 'triangle', 0.12), 120);
        setTimeout(() => playTone(784, 0.2, 'triangle', 0.14), 240);
        setTimeout(() => playTone(1047, 0.3, 'sine', 0.1), 400);
    }

    /** Achievement unlock — bright chime */
    function playAchievement() {
        playTone(1047, 0.15, 'sine', 0.1);
        setTimeout(() => playTone(1319, 0.15, 'sine', 0.1), 100);
        setTimeout(() => playTone(1568, 0.25, 'sine', 0.12), 200);
    }

    /** Notification — subtle ping */
    function playNotify() {
        playTone(800, 0.1, 'sine', 0.08);
        setTimeout(() => playTone(1000, 0.08, 'sine', 0.06), 80);
    }

    /** Low time warning — urgent tick */
    function playLowTime() {
        playTone(600, 0.05, 'square', 0.08);
    }

    /** Illegal move / error — low buzz */
    function playError() {
        playTone(150, 0.15, 'sawtooth', 0.08);
    }

    /**
     * Auto-detect and play the right sound for a chess move.
     * @param {object} moveResult - { san, captured, isCheck, isCheckmate, isCastle }
     */
    function playMoveSound(moveResult) {
        if (!moveResult) return;
        if (moveResult.isCheckmate || moveResult.isCheck) {
            playCheck();
        } else if (moveResult.san?.startsWith('O-')) {
            playCastle();
        } else if (moveResult.captured) {
            playCapture();
        } else {
            playMove();
        }
    }

    return {
        playMove, playCapture, playCheck, playCastle,
        playGameStart, playGameEnd, playWin, playAchievement,
        playNotify, playLowTime, playError, playMoveSound,
    };
}
