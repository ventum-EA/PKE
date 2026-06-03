<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';

const props = defineProps({ game: Object });
const emit = defineEmits(['analyze', 'delete', 'download']);
const { t } = useI18n();
const auth = useAuthStore();

const isWin = computed(() =>
    (props.game.result === '1-0' && props.game.user_color === 'white') ||
    (props.game.result === '0-1' && props.game.user_color === 'black')
);
const isDraw = computed(() => props.game.result === '1/2-1/2');
const isLoss = computed(() => props.game.result !== '*' && !isWin.value && !isDraw.value);

const outcome = computed(() =>
    isWin.value ? 'win' : isDraw.value ? 'draw' : isLoss.value ? 'loss' : 'ongoing'
);

const outcomeLabel = computed(() => ({
    win: t('games.result_win'),
    loss: t('games.result_loss'),
    draw: t('games.result_draw'),
    ongoing: '...',
}[outcome.value]));

// Show opponent name, not "Player vs Player"
const opponent = computed(() => {
    const g = props.game;
    const opp = g.user_color === 'white' ? g.black_player : g.white_player;
    return opp || '?';
});

const mySide = computed(() =>
    props.game.user_color === 'white' ? '♔' : '♚'
);

// Relative time
function timeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    const now = Date.now();
    const diff = Math.floor((now - d.getTime()) / 1000);
    if (diff < 60) return t('time.just_now') || 'tikko';
    if (diff < 3600) return `${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d`;
    return d.toLocaleDateString('lv-LV', { day: 'numeric', month: 'short' });
}

const borderClass = computed(() => ({
    win: 'border-l-emerald-500',
    loss: 'border-l-red-500',
    draw: 'border-l-blue-500',
    ongoing: 'border-l-zinc-600',
}[outcome.value]));

const badgeClass = computed(() => ({
    win: 'border-emerald-500/20 text-emerald-400 bg-emerald-500/10',
    loss: 'border-red-500/20 text-red-400 bg-red-500/10',
    draw: 'border-blue-500/20 text-blue-400 bg-blue-500/10',
    ongoing: 'border-zinc-600 text-zinc-400 bg-zinc-800',
}[outcome.value]));
</script>

<template>
    <div :class="['h-full border-l-[3px] bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5 hover:border-amber-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/30 transition-all duration-300 group flex flex-col', borderClass]">
        <!-- Top: outcome + time -->
        <div class="flex items-center justify-between mb-3 gap-2">
            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase border shrink-0', badgeClass]">
                {{ outcomeLabel }}
            </span>
            <span class="text-[10px] text-zinc-600 tabular-nums shrink-0">{{ timeAgo(game.played_at || game.created_at) }}</span>
        </div>

        <!-- Matchup: "You vs Opponent" framing -->
        <div class="mb-3 min-w-0">
            <p class="text-sm font-bold text-zinc-300 truncate">
                <span class="text-amber-400/80">{{ mySide }}</span>
                <span class="text-zinc-600 mx-1">vs</span>
                {{ opponent }}
            </p>
            <p class="text-[10px] text-zinc-600 mt-1">
                {{ game.total_moves }} {{ $t('games.moves_short') }}
                <span v-if="game.skill_level != null" class="ml-1">· ELO ~{{ {0:400,1:500,2:600,3:700,4:850,5:1000,6:1150,7:1300,8:1400,9:1500,10:1600,11:1700,12:1800,13:1900,14:2000,15:2100,16:2200,17:2350,18:2500,19:2650,20:2800}[game.skill_level] || '' }}</span>
            </p>
        </div>

        <!-- Opening -->
        <p v-if="game.opening_name" class="text-xs text-zinc-500 mb-4 truncate">
            <span class="text-amber-400/50 font-mono mr-1">{{ game.opening_eco }}</span> {{ game.opening_name }}
        </p>
        <div v-else class="mb-4"></div>

        <!-- Actions -->
        <div class="mt-auto flex items-center gap-2 pt-3 border-t border-white/5">
            <button @click="emit('analyze', game.id)" type="button"
                class="flex-1 py-2 text-center text-xs font-bold rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 active:scale-95 transition-all">
                {{ game.is_analyzed ? '◉ ' + $t('games.view_analysis') : '⚡ ' + $t('games.run_analysis') }}
            </button>
            <button @click="emit('download', game.id)" type="button" :aria-label="$t('games.download_pgn')"
                class="py-2 px-3 text-xs font-bold rounded-lg text-zinc-400 border border-white/5 hover:text-amber-400 hover:border-amber-500/20 active:scale-95 transition-all">⬇</button>
            <button @click="emit('delete', game.id)" type="button" :aria-label="$t('games.delete_game')"
                class="py-2 px-3 text-xs font-bold rounded-lg text-red-400/50 border border-white/5 hover:text-red-400 hover:border-red-500/20 active:scale-95 transition-all">✕</button>
        </div>
    </div>
</template>
