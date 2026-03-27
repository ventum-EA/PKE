<script setup>
const props = defineProps({ game: Object });
const emit = defineEmits(['analyze', 'delete']);

const isWin = (g) => (g.result === '1-0' && g.user_color === 'white') || (g.result === '0-1' && g.user_color === 'black');
const isDraw = (g) => g.result === '1/2-1/2';
</script>

<template>
    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 hover:border-amber-500/20 transition-all group">
        <div class="flex items-center justify-between mb-4">
            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase border',
                isWin(game) ? 'border-emerald-500/20 text-emerald-400 bg-emerald-500/10' :
                isDraw(game) ? 'border-blue-500/20 text-blue-400 bg-blue-500/10' :
                'border-red-500/20 text-red-400 bg-red-500/10']">
                {{ game.result }}
            </span>
            <span v-if="game.is_analyzed" class="text-emerald-400 text-[10px] font-bold">✓ Analizēta</span>
        </div>

        <div class="mb-3">
            <p class="text-sm font-bold text-zinc-300 truncate">{{ game.white_player || '?' }} vs {{ game.black_player || '?' }}</p>
            <p class="text-[10px] text-zinc-600 uppercase tracking-wider mt-1">{{ game.user_color === 'white' ? '♔ Baltais' : '♚ Melnais' }} · {{ game.total_moves }} gāj.</p>
        </div>

        <p v-if="game.opening_name" class="text-xs text-zinc-500 mb-4 truncate">
            <span class="text-amber-400/60 font-mono mr-1">{{ game.opening_eco }}</span> {{ game.opening_name }}
        </p>

        <div class="flex items-center gap-2 pt-3 border-t border-white/5">
            <button @click="emit('analyze', game.id)"
                class="flex-1 py-2 text-center text-xs font-bold rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-all">
                {{ game.is_analyzed ? '◉ Skatīt' : '⚡ Analizēt' }}
            </button>
            <button @click="emit('delete', game.id)"
                class="py-2 px-3 text-xs font-bold rounded-lg text-red-400/50 border border-white/5 hover:text-red-400 hover:border-red-500/20 transition-all">
                ✕
            </button>
        </div>

        <p class="text-[10px] text-zinc-700 text-right mt-2">{{ game.played_at || game.created_at?.split(' ')[0] }}</p>
    </div>
</template>
