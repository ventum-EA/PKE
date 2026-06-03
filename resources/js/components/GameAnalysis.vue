<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useGamesStore } from '../stores/games';
import { useNotification } from '../composables/useNotification';
import { useStockfish } from '../services/stockfish';
import { useFocusTrap } from '../composables/useFocusTrap';
import { parsePgn, classifyEvalDiff, categorizeError, generateExplanation, isMoveInBook, generateGameSummary, uciToSan } from '../services/chess';
import api from '../services/api';
import ChessBoard from './ChessBoard.vue';
import SandboxBoard from './SandboxBoard.vue';
import AnnotationPanel from './AnnotationPanel.vue';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';

const { t, locale } = useI18n();

const props = defineProps({ gameId: Number });
const emit = defineEmits(['close']);
const gamesStore = useGamesStore();
const { notify } = useNotification();
const engine = useStockfish();

const dialogRef = ref(null);
const isDialogOpen = computed(() => true);
useFocusTrap(dialogRef, { active: isDialogOpen, onEscape: () => emit('close') });

const game = ref(null);
const parsedMoves = ref([]);
const analyzedMoves = ref([]);
const isAnalyzing = ref(false);
const analysisProgress = ref(0);
const currentMoveIndex = ref(-1);
const boardFen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
const analysisDepth = ref(15);
const engineReady = ref(false);
const { boardSize } = useResponsiveBoard({ maxSize: 420, padding: 80 });
const gameSummary = ref(null);
const sandboxes = ref([]);
const annotationRef = ref(null);
const annotationMarks = ref({ arrows: [], highlights: [] });

function onAnnotationsLoaded(map) {
    // annotations loaded from server
}
function onAnnotationHighlightChange(marks) {
    annotationMarks.value = marks;
}

function spawnSandbox() {
    sandboxes.value.push({
        id: `sb-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        fen: boardFen.value,
    });
}
function removeSandbox(sbId) {
    sandboxes.value = sandboxes.value.filter(s => s.id !== sbId);
}
const showTip = ref(false);

const classColors = {
    best: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
    excellent: 'text-teal-400 bg-teal-500/10 border-teal-500/20',
    good: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
    inaccuracy: 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20',
    mistake: 'text-orange-400 bg-orange-500/10 border-orange-500/20',
    blunder: 'text-red-400 bg-red-500/10 border-red-500/20',
    book: 'text-violet-400 bg-violet-500/10 border-violet-500/20',
};
const classLabelKeys = {
    best: 'best', excellent: 'excellent', good: 'good',
    inaccuracy: 'inaccuracy', mistake: 'mistake', blunder: 'blunder',
    book: 'book_move',
};
const categoryLabelKeys = {
    tactical: 'cat_tactical', positional: 'cat_positional',
    opening: 'cat_opening', endgame: 'cat_endgame',
};

const currentMove = computed(() => {
    if (currentMoveIndex.value < 0 || currentMoveIndex.value >= analyzedMoves.value.length) return null;
    return analyzedMoves.value[currentMoveIndex.value];
});
const lastMove = computed(() => {
    if (currentMoveIndex.value < 0) return null;
    const m = parsedMoves.value[currentMoveIndex.value];
    return m ? { from: m.from, to: m.to } : null;
});
const errorSquares = computed(() => {
    const m = currentMove.value;
    if (!m || !m.classification || ['best', 'excellent', 'good', 'book'].includes(m.classification)) return [];
    const pm = parsedMoves.value[currentMoveIndex.value];
    return pm ? [pm.from, pm.to] : [];
});
const combinedHighlights = computed(() => {
    const squares = [...errorSquares.value];
    const annH = annotationMarks.value?.highlights || [];
    for (const h of annH) {
        if (h.square && !squares.includes(h.square)) squares.push(h.square);
    }
    return squares;
});
const errors = computed(() => analyzedMoves.value.filter(m => ['inaccuracy', 'mistake', 'blunder'].includes(m.classification)));
const evalBar = computed(() => {
    const m = currentMove.value;
    if (!m) return 50;
    const ev = m.evalAfter ?? 0;
    return Math.min(95, Math.max(5, 50 + (ev * 10)));
});

function goToMove(index) {
    currentMoveIndex.value = index;
    showTip.value = false;
    if (index < 0) { boardFen.value = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'; }
    else if (parsedMoves.value[index]) { boardFen.value = parsedMoves.value[index].fen_after; }
}
function goToStart() { goToMove(-1); }
function goBack() { goToMove(Math.max(-1, currentMoveIndex.value - 1)); }
function goForward() { goToMove(Math.min(parsedMoves.value.length - 1, currentMoveIndex.value + 1)); }
function goToEnd() { goToMove(parsedMoves.value.length - 1); }

function handleKeydown(e) {
    if (e.key === 'ArrowLeft') goBack();
    if (e.key === 'ArrowRight') goForward();
    if (e.key === 'Home') goToStart();
    if (e.key === 'End') goToEnd();
}
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

onMounted(async () => {
    game.value = await gamesStore.fetchGame(props.gameId);
    if (game.value?.pgn) {
        const parsed = parsePgn(game.value.pgn);
        parsedMoves.value = parsed.moves;
    }
    if (game.value?.is_analyzed) {
        try {
            const serverMoves = await gamesStore.fetchMoves(props.gameId);
            if (serverMoves.length > 0) {
                analyzedMoves.value = serverMoves.map(m => {
                    // Validate best_move — old broken analyses saved garbage like "1"
                    let bm = m.best_move ?? m.bestMove ?? null;
                    if (bm && (bm.length < 2 || !/^[a-hKQRBNOo]/.test(bm))) bm = null;
                    return {
                        ...m,
                        evalBefore: m.eval_before ?? m.evalBefore,
                        evalAfter: m.eval_after ?? m.evalAfter,
                        evalDiff: m.eval_diff ?? m.evalDiff,
                        bestMove: bm,
                        move_san: m.move_san ?? m.san,
                        error_category: m.error_category ?? null,
                        isPositiveFeedback: ['best', 'excellent', 'good'].includes(m.classification),
                    };
                });
                // Regenerate explanation text for error moves to fix
                // stale "Labāk: 1" from old analysis runs
                analyzedMoves.value.forEach(m => {
                    if (['inaccuracy', 'mistake', 'blunder'].includes(m.classification)) {
                        const obj = generateExplanation(
                            m.classification, m.error_category, m.move_san,
                            m.bestMove || m.move_san, locale.value,
                            { fenBefore: m.fen_before, fenAfter: m.fen_after,
                              color: m.color, evalBefore: m.evalBefore, evalAfter: m.evalAfter }
                        );
                        if (obj) {
                            m.explanation = obj.text;
                            m.explanationDetail = obj.detail;
                            m.explanationTip = obj.tip;
                            m.explanationEvalSwing = obj.evalSwing;
                        }
                    } else if (['best', 'excellent', 'good'].includes(m.classification)) {
                        const obj = generateExplanation(
                            m.classification, null, m.move_san, m.move_san, locale.value,
                            { fenBefore: m.fen_before, fenAfter: m.fen_after, color: m.color }
                        );
                        if (obj) {
                            m.explanation = obj.text;
                            m.explanationDetail = obj.detail;
                        }
                    }
                });
                gameSummary.value = generateGameSummary(analyzedMoves.value, locale.value);
            }
        } catch (err) { console.error('[analysis] Failed to load server moves:', err); }
    }
    try { await engine.init(); engineReady.value = true; } catch { console.warn('Stockfish WASM could not load'); }
    // Load user annotations
    if (annotationRef.value) annotationRef.value.loadAnnotations();
});
onUnmounted(() => { engine.stop(); });

async function runAnalysis() {
    if (!parsedMoves.value.length) return;
    isAnalyzing.value = true;
    analysisProgress.value = 0;
    analyzedMoves.value = [];

    const fens = parsedMoves.value.map(m => m.fen_before);
    fens.push(parsedMoves.value[parsedMoves.value.length - 1].fen_after);

    const evals = [];
    const depth = analysisDepth.value;

    for (let i = 0; i < fens.length; i++) {
        try { const result = await engine.analyze(fens[i], depth); evals.push(result.eval); }
        catch { evals.push(0); }
        analysisProgress.value = Math.round(((i + 1) / fens.length) * 100);
    }

    for (let i = 0; i < parsedMoves.value.length; i++) {
        const m = parsedMoves.value[i];
        const evalBefore = evals[i] ?? 0;
        const evalAfter = evals[i + 1] ?? 0;
        const inBook = isMoveInBook(parsedMoves.value, i);

        let classification, category = null, explanationObj = null;

        if (inBook) {
            const rawClass = classifyEvalDiff(evalBefore, evalAfter, m.color);
            if (rawClass === 'blunder') {
                classification = rawClass;
                category = categorizeError(i, parsedMoves.value.length, m);
            } else {
                classification = 'book';
            }
        } else {
            classification = classifyEvalDiff(evalBefore, evalAfter, m.color);
            category = ['inaccuracy', 'mistake', 'blunder'].includes(classification)
                ? categorizeError(i, parsedMoves.value.length, m)
                : null;
        }

        let bestMove = m.san;
        if (['inaccuracy', 'mistake', 'blunder'].includes(classification)) {
            try {
                const best = await engine.analyze(m.fen_before, Math.min(depth, 12));
                if (best.pv && best.pv.length > 0) {
                    // PV is in UCI notation (e.g. "e2e4"); convert to SAN (e.g. "e4")
                    bestMove = uciToSan(m.fen_before, best.pv[0]);
                }
            } catch { /* intentionally silenced */ }
        }

        explanationObj = generateExplanation(classification, category, m.san, bestMove, locale.value, {
            fenBefore: m.fen_before, fenAfter: m.fen_after,
            color: m.color, evalBefore, evalAfter,
        });

        analyzedMoves.value.push({
            ...m,
            evalBefore: Math.round(evalBefore * 100) / 100,
            evalAfter: Math.round(evalAfter * 100) / 100,
            evalDiff: Math.round(Math.abs(evalAfter - evalBefore) * 100) / 100,
            classification, error_category: category,
            explanation: explanationObj?.text || null,
            explanationDetail: explanationObj?.detail || null,
            explanationTip: explanationObj?.tip || null,
            explanationEvalSwing: explanationObj?.evalSwing || null,
            isPositiveFeedback: explanationObj?.isPositive || false,
            bestMove, move_san: m.san, move_number: m.moveNumber, color: m.color,
        });
    }

    isAnalyzing.value = false;
    gameSummary.value = generateGameSummary(analyzedMoves.value, locale.value);

    try {
        const movesToSave = analyzedMoves.value.map(m => ({
            move_number: m.move_number, color: m.color, move_san: m.move_san,
            fen_before: m.fen_before, fen_after: m.fen_after,
            eval_before: m.evalBefore, eval_after: m.evalAfter, eval_diff: m.evalDiff,
            best_move: m.bestMove,
            classification: m.classification === 'book' ? 'good' : m.classification,
            error_category: m.error_category, explanation: m.explanation,
        }));
        await gamesStore.saveMoves(props.gameId, movesToSave);
    } catch (e) { console.warn('Could not save analysis to server:', e); }

    // Auto-generate training puzzles from detected errors
    const errorCount = analyzedMoves.value.filter(m => ['inaccuracy', 'mistake', 'blunder'].includes(m.classification)).length;
    if (errorCount > 0) {
        try {
            const { data } = await api.post(`/training/generate/${props.gameId}`);
            const pCount = data.puzzles?.length || 0;
            if (pCount > 0) notify(t('analysis.training_generated', { count: pCount }), 'success');
        } catch { /* training auto-gen is best-effort */ }
    }

    // Check achievements
    try { await api.post('/achievements/check'); } catch { /* best-effort */ }

    notify(t('analysis.analysis_complete'), 'success');
}

const shareCopied = ref(false);
async function handleShare() {
    try {
        const url = await gamesStore.shareGame(props.gameId);
        await navigator.clipboard?.writeText(url);
        shareCopied.value = true; setTimeout(() => { shareCopied.value = false; }, 1600);
        notify(t('analysis.link_copied'), 'success');
    } catch { notify(t('analysis.link_failed'), 'error'); }
}
async function requestServerAnalysis() {
    try { await api.post(`/game/${props.gameId}/analyze`, { depth: 20, server: true }); notify(t('analysis.server_analysis_scheduled'), 'info'); }
    catch { notify(t('analysis.server_analysis_failed'), 'error'); }
}
async function generateTraining() {
    try {
        const { data } = await api.post(`/training/generate/${props.gameId}`);
        const count = data.puzzles?.length || 0;
        if (count > 0) notify(t('analysis.training_generated', { count }), 'success');
        else notify(t('analysis.no_errors_for_training'), 'info');
    } catch { notify(t('analysis.training_failed'), 'error'); }
}

const isExporting = ref(false);

/**
 * html2canvas 1.x parses CSS from the real DOM BEFORE onclone runs.
 * The ONLY fix: temporarily replace all <link> stylesheets in the real
 * document with a cleaned <style> block, run html2canvas, then restore.
 */
function swapStylesheetsForExport() {
    // Read all CSS rules from loaded stylesheets
    let css = '';
    for (const sheet of document.styleSheets) {
        try { for (const r of sheet.cssRules) css += r.cssText + '\n'; }
        catch { /* CORS-blocked */ }
    }
    // Replace ALL modern color functions html2canvas can't parse.
    // Use a function to handle nested parentheses in color-mix().
    function replaceColorFn(text, fn) {
        let result = '';
        let i = 0;
        while (i < text.length) {
            const idx = text.indexOf(fn + '(', i);
            if (idx === -1) { result += text.slice(i); break; }
            result += text.slice(i, idx);
            // Find the matching closing paren
            let depth = 0, j = idx + fn.length;
            for (; j < text.length; j++) {
                if (text[j] === '(') depth++;
                else if (text[j] === ')') { depth--; if (depth === 0) { j++; break; } }
            }
            result += '#888';
            i = j;
        }
        return result;
    }
    for (const fn of ['color-mix', 'oklch', 'oklab', 'lch', 'lab']) {
        css = replaceColorFn(css, fn);
    }

    // Hide all <link rel="stylesheet"> and <style> tags
    const originals = [];
    document.querySelectorAll('link[rel="stylesheet"], style').forEach(el => {
        originals.push({ el, display: el.style.display });
        el.style.display = 'none';
        el.disabled = true;
    });

    // Inject the cleaned CSS
    const cleanStyle = document.createElement('style');
    cleanStyle.id = '__export_clean_css';
    cleanStyle.textContent = css;
    document.head.appendChild(cleanStyle);

    // Return a restore function
    return () => {
        cleanStyle.remove();
        originals.forEach(({ el, display }) => {
            el.style.display = display;
            el.disabled = false;
        });
    };
}

async function exportToPdf() {
    if (isExporting.value) return; isExporting.value = true;
    const restore = swapStylesheetsForExport();
    try {
        const [{ default: html2canvas }, { jsPDF }] = await Promise.all([import('html2canvas'), import('jspdf')]);
        const target = document.getElementById('analysis-export-root');
        if (!target) { notify(t('analysis.export_content_missing'), 'error'); return; }
        const canvas = await html2canvas(target, { backgroundColor: '#18181b', scale: 2, useCORS: true, logging: false });
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const pw = pdf.internal.pageSize.getWidth(), ph = pdf.internal.pageSize.getHeight(), m = 24;
        const iw = pw - m * 2, ih = (canvas.height * iw) / canvas.width;
        if (ih <= ph - m * 2) { pdf.addImage(imgData, 'PNG', m, m, iw, ih); }
        else { let rp = 0, first = true; const sh = ((ph - m * 2) * canvas.width) / iw; while (rp < canvas.height) { const sc = document.createElement('canvas'); sc.width = canvas.width; sc.height = Math.min(sh, canvas.height - rp); sc.getContext('2d').drawImage(canvas, 0, -rp); if (!first) pdf.addPage(); first = false; pdf.addImage(sc.toDataURL('image/png'), 'PNG', m, m, iw, (sc.height * iw) / canvas.width); rp += sh; } }
        pdf.save(`analysis-game-${props.gameId}-${Date.now()}.pdf`);
        notify(t('analysis.pdf_exported'), 'success');
    } catch (err) { console.error('PDF export failed:', err); notify(t('analysis.pdf_failed'), 'error'); }
    finally { restore(); isExporting.value = false; }
}
async function exportToPng() {
    if (isExporting.value) return; isExporting.value = true;
    const restore = swapStylesheetsForExport();
    try {
        const { default: html2canvas } = await import('html2canvas');
        const target = document.getElementById('analysis-export-root');
        if (!target) { notify(t('analysis.export_content_missing'), 'error'); return; }
        const canvas = await html2canvas(target, { backgroundColor: '#18181b', scale: 2, useCORS: true, logging: false });
        const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
        if (!blob) throw new Error('toBlob returned null');
        const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `analysis-game-${props.gameId}-${Date.now()}.png`;
        document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
        notify(t('analysis.png_exported'), 'success');
    } catch (err) { console.error('PNG export failed:', err); notify(t('analysis.png_failed'), 'error'); }
    finally { restore(); isExporting.value = false; }
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm overflow-y-auto p-2 sm:p-4" @click.self="emit('close')">
        <div ref="dialogRef" role="dialog" aria-modal="true" aria-labelledby="game-analysis-title"
            class="max-w-6xl mx-auto my-2 sm:my-4 bg-zinc-900 border border-white/10 rounded-2xl sm:rounded-3xl shadow-2xl focus:outline-none">
            <div class="p-3 sm:p-5 border-b border-white/5 flex items-center justify-between flex-wrap gap-2 sm:gap-3">
                <div>
                    <h2 id="game-analysis-title" class="text-lg font-black text-white">
                        {{ game?.white_player || '?' }} vs {{ game?.black_player || '?' }}
                    </h2>
                    <p class="text-xs text-zinc-500 mt-1">
                        {{ game?.opening_name }} · {{ game?.result }} · {{ parsedMoves.length }} {{ $t('analysis.moves_label') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="exportToPdf" :disabled="isExporting" type="button" :aria-label="$t('analysis.export_pdf_label')" :title="$t('analysis.export_pdf_label')" class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 disabled:opacity-40 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">{{ isExporting ? '…' : '⬇ PDF' }}</button>
                    <button @click="exportToPng" :disabled="isExporting" type="button" :aria-label="$t('analysis.export_png_label')" :title="$t('analysis.export_png_label')" class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 disabled:opacity-40 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">{{ isExporting ? '…' : '🖼 PNG' }}</button>
                    <button @click="handleShare" type="button" :aria-label="shareCopied ? $t('analysis.link_copied_label') : $t('analysis.share_link_label')" :class="['px-3 py-2 text-xs font-bold rounded-xl border transition-all focus-visible:outline-none focus-visible:ring-2', shareCopied ? 'border-emerald-500/30 text-emerald-400 bg-emerald-500/10 focus-visible:ring-emerald-400/60' : 'border-white/10 text-zinc-400 hover:text-amber-400 focus-visible:ring-amber-400/60']">{{ shareCopied ? '✓' : '🔗' }}</button>
                    <button @click="emit('close')" type="button" :aria-label="$t('analysis.close_label')" class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">✕</button>
                </div>
            </div>

            <div id="analysis-export-root" class="p-3 sm:p-5 flex flex-col lg:flex-row gap-4 sm:gap-6">
                <!-- Left: Board + Controls -->
                <div class="flex-shrink-0 mx-auto lg:mx-0">
                    <ChessBoard :fen="boardFen" :orientation="game?.user_color || 'white'" :interactive="false" :lastMove="lastMove" :highlightSquares="combinedHighlights"
                        :highlightColor="currentMove?.classification === 'blunder' ? 'rgba(235,68,68,0.45)' : currentMove?.classification === 'mistake' ? 'rgba(245,158,11,0.4)' : 'rgba(250,204,21,0.3)'"
                        :size="boardSize" :resizable="false" />

                    <!-- Eval bar -->
                    <div class="mt-3 h-4 bg-zinc-800 rounded-full overflow-hidden relative">
                        <div class="h-full bg-white transition-all duration-300 rounded-full" :style="{ width: evalBar + '%' }"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold" :class="evalBar > 50 ? 'text-zinc-800' : 'text-zinc-300'">
                            {{ currentMove ? (currentMove.evalAfter > 0 ? '+' : '') + currentMove.evalAfter : '0.00' }}
                        </span>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <button @click="goToStart" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏮</button>
                        <button @click="goBack" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">◀</button>
                        <span class="text-xs text-zinc-500 min-w-[60px] text-center">{{ currentMoveIndex + 1 }} / {{ parsedMoves.length }}</span>
                        <button @click="goForward" class="px-4 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">▶</button>
                        <button @click="goToEnd" class="px-3 py-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white text-sm font-bold">⏭</button>
                        <button @click="spawnSandbox" class="px-3 py-2 rounded-lg bg-violet-500/10 border border-violet-500/20 text-violet-400 hover:bg-violet-500/20 text-sm font-bold transition-all" :title="$t('sandbox.spawn')">🧪</button>
                    </div>

                    
                    <!-- Positive feedback for good moves -->
                    <div v-if="currentMove?.isPositiveFeedback && currentMove?.explanation" class="mt-3 p-3 bg-emerald-500/5 rounded-xl border border-emerald-500/10">
                        <div class="flex items-center gap-2 mb-1">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase border', classColors[currentMove.classification]]">
                                {{ $t('analysis.' + classLabelKeys[currentMove.classification]) }}
                            </span>
                        </div>
                        <p class="text-sm text-emerald-300 mt-1">{{ currentMove.explanation }}</p>
                        <p v-if="currentMove.explanationDetail" class="text-xs text-emerald-400/70 mt-1.5">{{ currentMove.explanationDetail }}</p>
                    </div>

                    <!-- Error feedback with rich insights -->
                    <div v-else-if="currentMove?.explanation && !currentMove?.isPositiveFeedback" class="mt-3 p-3 bg-black/30 rounded-xl border border-white/5 space-y-2">
                        <!-- Classification badge + category -->
                        <div class="flex items-center gap-2">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase border', classColors[currentMove.classification]]">
                                {{ $t('analysis.' + classLabelKeys[currentMove.classification]) }}
                            </span>
                            <span v-if="currentMove.error_category" class="text-[10px] text-zinc-600">
                                {{ $t('analysis.' + categoryLabelKeys[currentMove.error_category]) }}
                            </span>
                        </div>

                        <!-- Main explanation text -->
                        <p class="text-sm text-zinc-300 leading-relaxed">{{ currentMove.explanation }}</p>

                        <!-- Eval swing indicator -->
                        <div v-if="currentMove.explanationEvalSwing" class="flex items-center gap-2 pt-1">
                            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-zinc-800/80 border border-white/5">
                                <span class="text-[10px] font-mono" :class="currentMove.explanationEvalSwing.before >= 0 ? 'text-white' : 'text-zinc-400'">
                                    {{ currentMove.explanationEvalSwing.before >= 0 ? '+' : '' }}{{ currentMove.explanationEvalSwing.before }}
                                </span>
                                <span class="text-[10px] text-zinc-600">→</span>
                                <span class="text-[10px] font-mono" :class="currentMove.explanationEvalSwing.after >= 0 ? 'text-white' : 'text-zinc-400'">
                                    {{ currentMove.explanationEvalSwing.after >= 0 ? '+' : '' }}{{ currentMove.explanationEvalSwing.after }}
                                </span>
                                <span class="text-[10px] font-bold ml-1" :class="currentMove.classification === 'blunder' ? 'text-red-400' : currentMove.classification === 'mistake' ? 'text-orange-400' : 'text-yellow-400'">
                                    ({{ currentMove.explanationEvalSwing.swing >= 0 ? '-' : '+' }}{{ currentMove.explanationEvalSwing.swing }})
                                </span>
                            </div>
                        </div>

                        <!-- Teaching tip (collapsible) -->
                        <div v-if="currentMove.explanationTip">
                            <button @click="showTip = !showTip" class="text-[10px] font-bold text-amber-400/60 hover:text-amber-400 transition-colors mt-1">
                                {{ showTip ? '▾' : '▸' }} {{ $t('analysis.learning_tip') }}
                            </button>
                            <p v-if="showTip" class="text-xs text-amber-400/80 mt-1.5 pl-3 border-l-2 border-amber-500/20 leading-relaxed">
                                {{ currentMove.explanationTip }}
                            </p>
                        </div>
                    </div>

                    <!-- Book move indicator -->
                    <div v-else-if="currentMove?.classification === 'book'" class="mt-3 p-3 bg-violet-500/5 rounded-xl border border-violet-500/10">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase border text-violet-400 bg-violet-500/10 border-violet-500/20">
                            {{ $t('analysis.book_move') }}
                        </span>
                        <p class="text-xs text-violet-400/60 mt-1.5">{{ $t('analysis.book_move_desc') }}</p>
                    </div>
                </div>

                <!-- Right: Analysis panel -->
                <div class="flex-1 min-w-0">
                    <!-- Not analyzed yet -->
                    <div v-if="analyzedMoves.length === 0 && !isAnalyzing" class="text-center py-8">
                        <p class="text-3xl mb-3">🔍</p>
                        <h3 class="text-base font-bold text-white mb-2">{{ $t('analysis.start_analysis') }}</h3>
                        <p class="text-zinc-500 text-sm mb-4">{{ $t('analysis.engine_desc') }}</p>
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <label class="text-xs text-zinc-500">{{ $t('analysis.depth_label') }}</label>
                            <input :aria-label="$t('analysis.depth_label')" type="range" v-model.number="analysisDepth" min="8" max="22" class="w-32">
                            <span class="text-sm text-amber-400 font-bold w-6">{{ analysisDepth }}</span>
                        </div>
                        <button @click="runAnalysis" :disabled="!engineReady"
                            class="px-8 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl shadow-lg shadow-amber-500/20 uppercase tracking-wider text-sm disabled:opacity-40">
                            {{ engineReady ? $t('analysis.analyze_btn') : $t('analysis.loading_engine') }}
                        </button>
                    </div>

                    <!-- Analyzing progress -->
                    <div v-else-if="isAnalyzing" class="text-center py-8">
                        <div class="w-14 h-14 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-zinc-400 font-bold mb-2">{{ $t('analysis.analyzing') }}</p>
                        <div class="w-64 mx-auto bg-zinc-800 rounded-full h-2 mb-1">
                            <div class="bg-amber-400 h-2 rounded-full transition-all" :style="{ width: analysisProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-zinc-600">{{ analysisProgress }}% · {{ $t('analysis.depth_label') }} {{ analysisDepth }}</p>
                    </div>

                    <!-- Analysis results -->
                    <div v-else>
                        
                        <div v-if="gameSummary" class="bg-zinc-800/50 border border-white/5 rounded-2xl p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-lg"
                                        :class="gameSummary.accuracy >= 85 ? 'bg-emerald-500/10 text-emerald-400' : gameSummary.accuracy >= 70 ? 'bg-amber-500/10 text-amber-400' : 'bg-red-500/10 text-red-400'">
                                        {{ gameSummary.accuracy }}%
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-white">{{ gameSummary.levelText }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ $t('analysis.accuracy_label') }}</p>
                                    </div>
                                </div>
                                <div v-if="gameSummary.bestMoves > 0" class="text-right">
                                    <p class="text-xs text-emerald-400 font-bold">{{ gameSummary.bestMoves }} {{ $t('analysis.best_moves') }}</p>
                                    <p v-if="gameSummary.bookMoves > 0" class="text-[10px] text-violet-400">{{ gameSummary.bookMoves }} {{ $t('analysis.book_moves_count') }}</p>
                                </div>
                            </div>
                            <!-- Error breakdown -->
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="bg-red-500/5 border border-red-500/10 rounded-lg p-2 text-center">
                                    <p class="text-lg font-black text-red-400">{{ gameSummary.blunders }}</p>
                                    <p class="text-[9px] font-bold uppercase text-zinc-600">{{ $t('analysis.blunders') }}</p>
                                </div>
                                <div class="bg-orange-500/5 border border-orange-500/10 rounded-lg p-2 text-center">
                                    <p class="text-lg font-black text-orange-400">{{ gameSummary.mistakes }}</p>
                                    <p class="text-[9px] font-bold uppercase text-zinc-600">{{ $t('analysis.mistakes') }}</p>
                                </div>
                                <div class="bg-yellow-500/5 border border-yellow-500/10 rounded-lg p-2 text-center">
                                    <p class="text-lg font-black text-yellow-400">{{ gameSummary.inaccuracies }}</p>
                                    <p class="text-[9px] font-bold uppercase text-zinc-600">{{ $t('analysis.inaccuracies') }}</p>
                                </div>
                            </div>
                            <!-- Advice -->
                            <p v-if="gameSummary.advice" class="text-xs text-zinc-400 bg-black/20 rounded-lg p-2.5">
                                💡 {{ gameSummary.advice }}
                            </p>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2 mb-4">
                            <button @click="requestServerAnalysis" class="flex-1 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-blue-400 hover:border-blue-500/20 transition-all text-center">{{ $t('analysis.server_analysis') }}</button>
                            <button @click="generateTraining" class="flex-1 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-emerald-400 hover:border-emerald-500/20 transition-all text-center">{{ $t('analysis.generate_training') }}</button>
                        </div>

                        <!-- Annotation Panel -->
                        <AnnotationPanel
                            ref="annotationRef"
                            :gameId="props.gameId"
                            :currentMoveIndex="currentMoveIndex"
                            @annotations-loaded="onAnnotationsLoaded"
                            @highlight-change="onAnnotationHighlightChange"
                            @go-to-move="goToMove"
                            class="mb-4"
                        />

                        <!-- Move list -->
                        <div class="max-h-[420px] overflow-y-auto pr-1 space-y-0.5">
                            <div v-for="(move, i) in analyzedMoves" :key="i" @click="goToMove(i)"
                                :class="['flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-all text-xs',
                                    currentMoveIndex === i ? 'bg-amber-500/10 border border-amber-500/20' :
                                    move.classification === 'book' ? 'text-violet-400 hover:bg-violet-500/5 border border-transparent' :
                                    ['inaccuracy','mistake','blunder'].includes(move.classification)
                                        ? 'border border-transparent hover:border-white/5 ' + classColors[move.classification].split(' ')[0]
                                        : 'text-zinc-500 hover:bg-white/[0.02] border border-transparent']">
                                <span class="font-mono text-zinc-600 w-8 text-right">{{ move.move_number }}.{{ move.color === 'black' ? '..' : '' }}</span>
                                <span class="font-bold text-white w-14">{{ move.move_san }}</span>
                                <span v-if="move.classification === 'book'" :class="['px-1.5 py-0.5 rounded text-[9px] font-black uppercase border', classColors.book]">📖</span>
                                <span v-else-if="move.classification === 'best'" class="text-emerald-500 text-[10px] font-bold">✦ best</span>
                                <span v-else-if="move.classification === 'excellent'" class="text-teal-500 text-[10px]">✓</span>
                                <span v-else-if="move.classification === 'good'" class="text-blue-500/50 text-[10px]">·</span>
                                <span v-else-if="move.classification && ['inaccuracy','mistake','blunder'].includes(move.classification)"
                                    :class="['px-1.5 py-0.5 rounded text-[9px] font-black uppercase border', classColors[move.classification]]">
                                    {{ move.classification === 'blunder' ? '‼' : move.classification === 'mistake' ? '?' : '⚠' }}
                                    {{ $t('analysis.' + classLabelKeys[move.classification])?.substring(0, 5) }}
                                </span>
                                <span class="ml-auto font-mono text-zinc-600">{{ move.evalAfter > 0 ? '+' : '' }}{{ move.evalAfter }}</span>
                                <span v-if="annotationRef?.annotations?.[i]?.comment || annotationRef?.annotations?.[i]?.arrows?.length || annotationRef?.annotations?.[i]?.highlights?.length"
                                    class="text-amber-400/70 text-[9px] ml-1 shrink-0" :title="$t('annotations.title')">✏</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sandbox boards -->
                <div v-if="sandboxes.length" class="px-3 sm:px-5 pb-5">
                    <h4 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3 flex items-center gap-2">
                        🧪 {{ $t('sandbox.title') }}
                    </h4>
                    <div class="space-y-4">
                        <SandboxBoard
                            v-for="sb in sandboxes" :key="sb.id"
                            :initialFen="sb.fen"
                            :depth="0"
                            @close="removeSandbox(sb.id)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
