<script setup>
import { ref, computed, watch } from 'vue';
import { Chess } from 'chess.js';
import { useI18n } from 'vue-i18n';
import { useResponsiveBoard } from '../composables/useResponsiveBoard';
import ChessBoard from './ChessBoard.vue';

const { t } = useI18n();

const props = defineProps({
    initialFen: { type: String, required: true },
    depth: { type: Number, default: 0 },
    parentId: { type: String, default: null },
});

const emit = defineEmits(['close']);

const id = `sandbox-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
const { boardSize } = useResponsiveBoard({ maxSize: 340 - props.depth * 20, padding: 64 });

const game = ref(new Chess(props.initialFen));
const fen = ref(game.value.fen());
const orientation = ref('white');
const moveHistory = ref([]);
const children = ref([]);

const DEPTH_COLORS = [
    { border: 'border-violet-500/40', bg: 'bg-violet-500/5', text: 'text-violet-400', ring: 'ring-violet-500/30', badge: 'bg-violet-500/20 text-violet-300' },
    { border: 'border-blue-500/40', bg: 'bg-blue-500/5', text: 'text-blue-400', ring: 'ring-blue-500/30', badge: 'bg-blue-500/20 text-blue-300' },
    { border: 'border-emerald-500/40', bg: 'bg-emerald-500/5', text: 'text-emerald-400', ring: 'ring-emerald-500/30', badge: 'bg-emerald-500/20 text-emerald-300' },
    { border: 'border-rose-500/40', bg: 'bg-rose-500/5', text: 'text-rose-400', ring: 'ring-rose-500/30', badge: 'bg-rose-500/20 text-rose-300' },
    { border: 'border-amber-500/40', bg: 'bg-amber-500/5', text: 'text-amber-400', ring: 'ring-amber-500/30', badge: 'bg-amber-500/20 text-amber-300' },
];

const colorSet = computed(() => DEPTH_COLORS[props.depth % DEPTH_COLORS.length]);

const turnLabel = computed(() => fen.value.split(' ')[1] === 'w' ? t('common.white') : t('common.black'));
const moveCount = computed(() => moveHistory.value.length);

const status = computed(() => {
    const g = game.value;
    if (g.isCheckmate()) return t('sandbox.checkmate');
    if (g.isStalemate()) return t('sandbox.stalemate');
    if (g.isDraw()) return t('sandbox.draw');
    if (g.isCheck()) return t('sandbox.check');
    return null;
});

function handleMove({ from, to, promotion }) {
    try {
        const result = game.value.move({ from, to, promotion: promotion || 'q' });
        if (result) {
            moveHistory.value.push({ san: result.san, fen: game.value.fen(), prevFen: fen.value });
            fen.value = game.value.fen();
        }
    } catch {}
}

function undo() {
    if (!moveHistory.value.length) return;
    game.value.undo();
    moveHistory.value.pop();
    fen.value = game.value.fen();
}

function reset() {
    game.value = new Chess(props.initialFen);
    fen.value = props.initialFen;
    moveHistory.value = [];
}

function flip() {
    orientation.value = orientation.value === 'white' ? 'black' : 'white';
}

function spawnChild() {
    children.value.push({
        id: `child-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        fen: fen.value,
    });
}

function removeChild(childId) {
    children.value = children.value.filter(c => c.id !== childId);
}

const maxDepth = 4;
const canSpawn = computed(() => props.depth < maxDepth);
</script>

<template>
    <div class="rounded-2xl border-2 p-3 sm:p-4 transition-all"
        :class="[colorSet.border, colorSet.bg]"
        :style="{ marginLeft: props.depth > 0 ? '0' : '0' }">

        <!-- Header -->
        <div class="flex items-center justify-between mb-3 gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider', colorSet.badge]">
                    {{ t('sandbox.label') }} {{ props.depth > 0 ? '#' + (props.depth + 1) : '' }}
                </span>
                <span class="text-[10px] text-zinc-500 truncate">
                    {{ moveCount }} {{ t('sandbox.moves_made') }} · {{ turnLabel }}
                </span>
            </div>
            <button @click="emit('close')" class="p-2.5 rounded-lg hover:bg-white/5 transition-colors shrink-0" :title="t('sandbox.close')" :aria-label="t('sandbox.close')">
                <svg class="w-4 h-4 text-zinc-500 hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Status -->
        <div v-if="status" class="mb-3 px-3 py-2 rounded-xl bg-black/20 text-center">
            <span :class="['text-xs font-bold', colorSet.text]">{{ status }}</span>
        </div>

        <!-- Board -->
        <div class="flex flex-col items-center">
            <ChessBoard
                :fen="fen"
                :orientation="orientation"
                :interactive="true"
                :size="boardSize"
                :ghost="true"
                @move="handleMove"
            />
        </div>

        <!-- Controls -->
        <div class="flex items-center justify-center gap-1.5 mt-3 flex-wrap">
            <button @click="undo" :disabled="!moveHistory.length"
                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border border-white/10 text-zinc-400 hover:text-white disabled:opacity-30 transition-all"
                :title="t('sandbox.undo')">
                ↶ {{ t('sandbox.undo') }}
            </button>
            <button @click="reset"
                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border border-white/10 text-zinc-400 hover:text-white transition-all"
                :title="t('sandbox.reset')">
                ↻ {{ t('sandbox.reset') }}
            </button>
            <button @click="flip"
                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border border-white/10 text-zinc-400 hover:text-white transition-all"
                :title="t('sandbox.flip')">
                ⇅ {{ t('sandbox.flip') }}
            </button>
            <button v-if="canSpawn" @click="spawnChild"
                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border transition-all"
                :class="[colorSet.border, colorSet.text, 'hover:bg-white/5']"
                :title="t('sandbox.spawn')">
                ＋ {{ t('sandbox.spawn') }}
            </button>
        </div>

        <!-- Move history -->
        <div v-if="moveHistory.length" class="mt-3 px-2 py-1.5 rounded-lg bg-black/20 max-h-16 overflow-y-auto">
            <div class="flex flex-wrap gap-x-1 gap-y-0.5 text-[10px] font-mono text-zinc-500">
                <template v-for="(m, i) in moveHistory" :key="i">
                    <span v-if="i % 2 === 0" class="text-zinc-600">{{ Math.floor(i / 2) + 1 }}.</span>
                    <span :class="colorSet.text">{{ m.san }}</span>
                </template>
            </div>
        </div>

        <!-- Child sandboxes -->
        <div v-if="children.length" class="mt-4 space-y-4">
            <SandboxBoard
                v-for="child in children" :key="child.id"
                :initialFen="child.fen"
                :depth="props.depth + 1"
                :parentId="id"
                @close="removeChild(child.id)"
            />
        </div>
    </div>
</template>
