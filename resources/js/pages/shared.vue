<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import ChessBoard from '../components/ChessBoard.vue';

const route = useRoute();
const game = ref(null);
const error = ref(null);
const currentMoveIndex = ref(-1);
const moves = ref([]);

const currentFen = ref('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/shared/${route.params.token}`);
        game.value = data.payload?.game || data.game || data;

        // Parse PGN into moves if available
        if (game.value.pgn) {
            const { parsePgn } = await import('../services/chess');
            const parsed = parsePgn(game.value.pgn);
            moves.value = parsed.moves || parsed || [];
        }
    } catch (e) {
        error.value = 'Partija nav atrasta vai saite ir nederīga.';
    }
});

function goToMove(idx) {
    if (idx < 0) {
        currentMoveIndex.value = -1;
        currentFen.value = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    } else if (idx < moves.value.length) {
        currentMoveIndex.value = idx;
        currentFen.value = moves.value[idx].fen_after;
    }
}

function prev() { goToMove(currentMoveIndex.value - 1); }
function next() { goToMove(currentMoveIndex.value + 1); }
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-4xl mx-auto">
            <!-- Error -->
            <div v-if="error" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-8 text-center">
                <p class="text-red-400 text-lg font-bold">{{ error }}</p>
                <router-link to="/login" class="text-amber-400 mt-4 inline-block hover:underline">← Pieslēgties</router-link>
            </div>

            <!-- Loading -->
            <div v-else-if="!game" class="text-center py-20">
                <div class="animate-spin w-8 h-8 border-2 border-amber-400 border-t-transparent rounded-full mx-auto"></div>
                <p class="text-zinc-500 mt-4">Ielādē partiju...</p>
            </div>

            <!-- Game -->
            <div v-else>
                <div class="mb-6">
                    <h1 class="text-2xl font-black tracking-tight">
                        <span class="text-amber-400">♟</span>
                        {{ game.white_player || '?' }} vs {{ game.black_player || '?' }}
                    </h1>
                    <p class="text-zinc-500 text-sm mt-1">
                        {{ game.opening_name || 'Nezināma atklātne' }}
                        <span v-if="game.opening_eco" class="text-zinc-600 font-mono ml-1">{{ game.opening_eco }}</span>
                        · {{ game.result }} · {{ game.total_moves }} gāj.
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Board -->
                    <div class="w-full lg:w-[min(55vw,80vh,640px)]">
                        <ChessBoard :fen="currentFen" :interactive="false" />

                        <!-- Navigation -->
                        <div class="flex items-center justify-center gap-3 mt-4">
                            <button @click="goToMove(-1)" class="px-3 py-1.5 bg-zinc-800 rounded-lg text-zinc-400 hover:text-white text-sm">⏮</button>
                            <button @click="prev" :disabled="currentMoveIndex < 0" class="px-3 py-1.5 bg-zinc-800 rounded-lg text-zinc-400 hover:text-white text-sm disabled:opacity-30">◀</button>
                            <span class="text-xs text-zinc-500 font-mono min-w-[4rem] text-center">
                                {{ currentMoveIndex < 0 ? 'Sākums' : `${Math.floor(currentMoveIndex/2)+1}. ${moves[currentMoveIndex]?.san}` }}
                            </span>
                            <button @click="next" :disabled="currentMoveIndex >= moves.length - 1" class="px-3 py-1.5 bg-zinc-800 rounded-lg text-zinc-400 hover:text-white text-sm disabled:opacity-30">▶</button>
                            <button @click="goToMove(moves.length - 1)" class="px-3 py-1.5 bg-zinc-800 rounded-lg text-zinc-400 hover:text-white text-sm">⏭</button>
                        </div>
                    </div>

                    <!-- Move list -->
                    <div v-if="moves.length" class="flex-1 bg-zinc-900/50 border border-white/5 rounded-2xl p-4 max-h-[600px] overflow-y-auto">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">Gājieni</h3>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(m, idx) in moves" :key="idx">
                                <span v-if="idx % 2 === 0" class="text-zinc-600 text-xs font-mono mr-0.5">{{ Math.floor(idx/2)+1 }}.</span>
                                <button @click="goToMove(idx)"
                                    :class="['text-sm px-1.5 py-0.5 rounded font-mono transition-colors',
                                        idx === currentMoveIndex ? 'bg-amber-500/20 text-amber-400' : 'text-zinc-300 hover:bg-white/5']">
                                    {{ m.san }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
