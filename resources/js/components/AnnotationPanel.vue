<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotification } from '../composables/useNotification';
import api from '../services/api';

const { t } = useI18n();
const { notify } = useNotification();

const props = defineProps({
    gameId: { type: Number, required: true },
    currentMoveIndex: { type: Number, default: -1 },
});
const emit = defineEmits(['annotations-loaded', 'highlight-change', 'go-to-move']);

const annotations = ref({});
const commentText = ref('');
const isSaving = ref(false);
const isLoaded = ref(false);
const collapsed = ref(false);

const COLORS = [
    { key: 'green',  cls: 'bg-emerald-400', ring: 'ring-emerald-400' },
    { key: 'red',    cls: 'bg-red-400',     ring: 'ring-red-400'     },
    { key: 'blue',   cls: 'bg-blue-400',    ring: 'ring-blue-400'    },
    { key: 'yellow', cls: 'bg-yellow-400',  ring: 'ring-yellow-400'  },
];
const selectedColor = ref('green');
const MAX_COMMENT = 1000;

const currentAnn = computed(() => annotations.value[props.currentMoveIndex] || null);
const hasContent = computed(() => {
    const a = currentAnn.value;
    return !!(a?.comment || a?.arrows?.length || a?.highlights?.length);
});
const annotatedMoves = computed(() =>
    Object.keys(annotations.value).map(Number).filter(k => {
        const a = annotations.value[k];
        return a?.comment || a?.arrows?.length || a?.highlights?.length;
    }).sort((a, b) => a - b)
);
const charCount = computed(() => commentText.value.length);

// Sync comment text when move changes
watch(() => props.currentMoveIndex, (idx) => {
    const ann = annotations.value[idx];
    commentText.value = ann?.comment || '';
    emitHighlights(ann);
});

function emitHighlights(ann) {
    emit('highlight-change', {
        arrows: ann?.arrows || [],
        highlights: ann?.highlights || [],
    });
}

function ensureEntry() {
    if (!annotations.value[props.currentMoveIndex]) {
        annotations.value[props.currentMoveIndex] = { comment: null, arrows: [], highlights: [] };
    }
    return annotations.value[props.currentMoveIndex];
}

async function loadAnnotations() {
    try {
        const { data } = await api.get(`/game/${props.gameId}/annotations`);
        const map = {};
        (data.annotations || []).forEach(a => {
            map[a.move_index] = { comment: a.comment, arrows: a.arrows || [], highlights: a.highlights || [] };
        });
        annotations.value = map;
        isLoaded.value = true;
        emit('annotations-loaded', map);
    } catch { /* intentionally silenced */ }
}

async function saveAnnotation() {
    if (props.currentMoveIndex < 0) return;
    isSaving.value = true;
    const entry = ensureEntry();
    entry.comment = commentText.value || null;

    try {
        await api.post(`/game/${props.gameId}/annotations`, {
            move_index: props.currentMoveIndex,
            comment: entry.comment,
            arrows: entry.arrows || [],
            highlights: entry.highlights || [],
        });
        notify(t('annotations.saved'), 'success');
    } catch {
        notify(t('annotations.save_failed'), 'error');
    } finally {
        isSaving.value = false;
    }
}

async function deleteAnnotation() {
    if (props.currentMoveIndex < 0) return;
    try {
        await api.delete(`/game/${props.gameId}/annotations/${props.currentMoveIndex}`);
        delete annotations.value[props.currentMoveIndex];
        commentText.value = '';
        emitHighlights(null);
        notify(t('annotations.deleted'), 'success');
    } catch {
        notify(t('annotations.delete_failed'), 'error');
    }
}

function addArrow(from, to) {
    if (props.currentMoveIndex < 0) return;
    const entry = ensureEntry();
    if (entry.arrows.some(a => a.from === from && a.to === to)) return;
    entry.arrows.push({ from, to, color: selectedColor.value });
    emitHighlights(entry);
}
function removeArrow(idx) {
    const entry = currentAnn.value;
    if (!entry) return;
    entry.arrows.splice(idx, 1);
    emitHighlights(entry);
}

function addHighlight(square) {
    if (props.currentMoveIndex < 0) return;
    const entry = ensureEntry();
    const idx = entry.highlights.findIndex(h => h.square === square);
    if (idx >= 0) entry.highlights.splice(idx, 1);
    else entry.highlights.push({ square, color: selectedColor.value });
    emitHighlights(entry);
}

function clearMarks() {
    const entry = currentAnn.value;
    if (!entry) return;
    entry.arrows = [];
    entry.highlights = [];
    emitHighlights(entry);
}

function moveLabel(idx) {
    const n = Math.floor(idx / 2) + 1;
    return idx % 2 === 0 ? `${n}.` : `${n}…`;
}

defineExpose({ loadAnnotations, addArrow, addHighlight, annotations, annotatedMoves });
</script>

<template>
    <div class="bg-zinc-800/40 border border-white/5 rounded-2xl overflow-hidden transition-all">
        <!-- Header (always visible, acts as toggle) -->
        <button @click="collapsed = !collapsed" type="button"
            class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-white/[0.02] transition-colors group">
            <h4 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 flex items-center gap-1.5">
                <span class="text-sm">✏</span>
                {{ $t('annotations.title') }}
                <span v-if="annotatedMoves.length" class="text-[9px] font-mono text-amber-400/60 tabular-nums">
                    ({{ annotatedMoves.length }})
                </span>
            </h4>
            <span class="text-zinc-600 text-xs transition-transform duration-200 group-hover:text-zinc-400" :class="collapsed ? '' : 'rotate-180'">▾</span>
        </button>

        <!-- Collapsible body -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[600px]"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 max-h-[600px]"
            leave-to-class="opacity-0 max-h-0">
            <div v-if="!collapsed" class="overflow-hidden">
                <div class="px-4 pb-4 space-y-3">
                    <!-- Color picker row -->
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] text-zinc-600 shrink-0">{{ $t('annotations.color') }}</span>
                        <div class="flex items-center gap-1.5">
                            <button v-for="c in COLORS" :key="c.key" @click="selectedColor = c.key" type="button"
                                :class="['w-5 h-5 rounded-full transition-all duration-150', c.cls,
                                    selectedColor === c.key ? 'ring-2 ring-offset-2 ring-offset-zinc-900 scale-110 ' + c.ring : 'opacity-50 hover:opacity-80']"
                                :aria-label="c.key">
                            </button>
                        </div>
                    </div>

                    <!-- Current arrows -->
                    <div v-if="currentAnn?.arrows?.length" class="space-y-1">
                        <div v-for="(arrow, i) in currentAnn.arrows" :key="i"
                            class="flex items-center gap-2 py-1 px-2 bg-black/20 rounded-lg group/arrow">
                            <span class="w-2 h-2 rounded-full shrink-0"
                                :class="arrow.color === 'green' ? 'bg-emerald-400' : arrow.color === 'red' ? 'bg-red-400' : arrow.color === 'blue' ? 'bg-blue-400' : 'bg-yellow-400'"></span>
                            <span class="text-[10px] text-zinc-400 font-mono flex-1">{{ arrow.from }} → {{ arrow.to }}</span>
                            <button @click="removeArrow(i)" type="button"
                                class="text-zinc-700 hover:text-red-400 text-[10px] opacity-0 group-hover/arrow:opacity-100 transition-opacity">✕</button>
                        </div>
                    </div>

                    <!-- Current highlights -->
                    <div v-if="currentAnn?.highlights?.length" class="flex flex-wrap gap-1">
                        <span v-for="(h, i) in currentAnn.highlights" :key="i"
                            class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/20 text-zinc-400">
                            ■ {{ h.square }}
                        </span>
                    </div>

                    <!-- Comment area -->
                    <div v-if="currentMoveIndex >= 0">
                        <div class="relative">
                            <textarea v-model="commentText"
                                :placeholder="$t('annotations.comment_placeholder')"
                                :maxlength="MAX_COMMENT"
                                rows="3"
                                class="w-full bg-black/20 border border-white/5 rounded-xl p-3 text-sm text-zinc-300 placeholder-zinc-700 resize-none focus:outline-none focus:border-amber-500/20 focus:bg-black/30 transition-all leading-relaxed">
                            </textarea>
                            <span v-if="commentText.length > 0" class="absolute bottom-2 right-3 text-[9px] tabular-nums"
                                :class="charCount > MAX_COMMENT * 0.9 ? 'text-red-400' : 'text-zinc-700'">
                                {{ charCount }}/{{ MAX_COMMENT }}
                            </span>
                        </div>

                        <!-- Actions row -->
                        <div class="flex items-center gap-2 mt-2">
                            <button @click="saveAnnotation" :disabled="isSaving" type="button"
                                class="flex-1 py-2 text-[10px] font-bold uppercase tracking-wider rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/15 disabled:opacity-40 transition-all text-center">
                                {{ isSaving ? '···' : $t('annotations.save') }}
                            </button>
                            <button v-if="currentAnn?.arrows?.length || currentAnn?.highlights?.length" @click="clearMarks" type="button"
                                class="py-2 px-3 text-[10px] font-bold rounded-xl text-zinc-500 border border-white/5 hover:text-zinc-300 hover:border-white/10 transition-all"
                                :title="$t('annotations.clear_marks')">
                                ⌫
                            </button>
                            <button v-if="hasContent" @click="deleteAnnotation" type="button"
                                class="py-2 px-3 text-[10px] font-bold rounded-xl text-red-400/50 border border-white/5 hover:text-red-400 hover:border-red-500/15 transition-all"
                                :title="$t('annotations.delete')">
                                🗑
                            </button>
                        </div>
                    </div>

                    <!-- No move selected -->
                    <p v-else class="text-[11px] text-zinc-600 italic text-center py-3">{{ $t('annotations.select_move') }}</p>

                    <!-- Annotated moves index -->
                    <div v-if="annotatedMoves.length > 0" class="pt-2 border-t border-white/5">
                        <p class="text-[9px] text-zinc-600 font-bold uppercase tracking-wider mb-2">{{ $t('annotations.annotated_moves') }}</p>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="idx in annotatedMoves" :key="idx" type="button"
                                @click="$emit('go-to-move', idx)"
                                :class="['px-2 py-1 rounded-lg text-[10px] font-mono border transition-all',
                                    idx === currentMoveIndex
                                        ? 'text-amber-400 bg-amber-500/10 border-amber-500/20'
                                        : 'text-zinc-500 border-white/5 hover:text-zinc-300 hover:border-white/10']">
                                {{ moveLabel(idx) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
