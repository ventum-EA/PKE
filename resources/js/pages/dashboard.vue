<script setup>
import { onMounted, computed } from 'vue';
import { useGamesStore } from '../stores/games';
import { useAuthStore } from '../stores/auth';
import StatCard from '../components/StatCard.vue';
import ErrorChart from '../components/ErrorChart.vue';
import ProgressChart from '../components/ProgressChart.vue';

const authStore = useAuthStore();
const gamesStore = useGamesStore();

onMounted(() => {
    gamesStore.fetchStats(true);
    gamesStore.fetchGames({ perPage: 5, sort: '-created_at' }, true);
});

const stats = computed(() => gamesStore.stats);
const recentGames = computed(() => gamesStore.games.slice(0, 5));

// Wait for stats to either load or error; don't hang forever on the spinner.
const isReady = computed(() => stats.value !== null || gamesStore.statsError !== null);
const hasError = computed(() => !!gamesStore.statsError && stats.value === null);

function retry() {
    gamesStore.fetchStats(true);
    gamesStore.fetchGames({ perPage: 5, sort: '-created_at' }, true);
}
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <!-- Error state -->
        <div v-if="hasError" class="max-w-2xl mx-auto mt-20">
            <div role="alert" class="bg-red-500/10 border border-red-500/20 rounded-3xl p-8 text-center">
                <p class="text-4xl mb-4" aria-hidden="true">⚠</p>
                <h2 class="text-xl font-black text-red-400 mb-2">{{ $t('dashboard.load_failed') }}</h2>
                <p class="text-sm text-zinc-400 mb-6">{{ gamesStore.statsError }}</p>
                <button type="button" @click="retry"
                    class="px-6 py-2.5 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                    Mēģināt vēlreiz
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto" v-else-if="isReady && stats">
            <!-- Header -->
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tight">
                    <span class="text-amber-400">♔</span> {{ $t('dashboard.title') }}
                </h1>
                <p class="text-zinc-500 text-sm mt-1 uppercase tracking-widest font-bold">
                    {{ $t("dashboard.welcome", { name: authStore.user?.name }) }} · ELO {{ authStore.user?.elo_rating }}
                </p>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-10">
                <StatCard :title="$t('dashboard.total_games')" :value="stats.summary?.total_games || 0" icon="♟"
                    color="amber" />
                <StatCard :title="$t('dashboard.wins')" :value="stats.summary?.wins || 0" icon="♔" color="emerald" />
                <StatCard :title="$t('dashboard.losses')" :value="stats.summary?.losses || 0" icon="✕" color="red" />
                <StatCard :title="$t('dashboard.draws')" :value="stats.summary?.draws || 0" icon="½" color="blue" />
                <StatCard :title="$t('dashboard.win_rate')" :value="(stats.summary?.win_rate || 0) + '%'" icon="◈"
                    color="purple" />
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{
                        $t('dashboard.error_distribution') }}</h3>
                    <ErrorChart :data="stats.errors" />
                </div>
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{
                        $t('dashboard.progress_90d') }}</h3>
                    <ProgressChart :data="stats.progress_trend" />
                </div>
            </div>

            <!-- Openings Stats -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 mb-10" v-if="stats.openings?.length">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{
                    $t('dashboard.opening_stats') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">
                                <th class="pb-3 pr-4">Atklātne</th>
                                <th class="pb-3 pr-4 text-center">ECO</th>
                                <th class="pb-3 pr-4 text-center">Partijas</th>
                                <th class="pb-3 pr-4 text-center">Uzvaras</th>
                                <th class="pb-3 pr-4 text-center">Neizšķ.</th>
                                <th class="pb-3 text-center">Zaud.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="o in stats.openings.slice(0, 8)" :key="o.opening_name"
                                class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 pr-4 text-sm font-semibold text-zinc-300">{{ o.opening_name }}</td>
                                <td class="py-3 pr-4 text-center text-xs text-zinc-500 font-mono">{{ o.opening_eco }}
                                </td>
                                <td class="py-3 pr-4 text-center text-sm text-zinc-400 font-bold">{{ o.total }}</td>
                                <td class="py-3 pr-4 text-center text-sm text-emerald-400 font-bold">{{ o.wins }}</td>
                                <td class="py-3 pr-4 text-center text-sm text-blue-400 font-bold">{{ o.draws }}</td>
                                <td class="py-3 text-center text-sm text-red-400 font-bold">{{ o.losses }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Games -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl overflow-hidden">
                <div class="px-6 py-5 border-b border-white/5 flex justify-between items-center">
                    <h3 class="font-black text-white">{{ $t('dashboard.recent_games') }}</h3>
                    <router-link to="/games"
                        class="text-xs font-bold text-amber-400 hover:text-amber-300 uppercase tracking-widest transition-colors">
                        Visas partijas →
                    </router-link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                <th class="px-6 py-3">Pretinieks</th>
                                <th class="px-6 py-3 text-center">Atklātne</th>
                                <th class="px-6 py-3 text-center">Rezultāts</th>
                                <th class="px-6 py-3 text-center">Analīze</th>
                                <th class="px-6 py-3 text-right">Datums</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="game in recentGames" :key="game.id"
                                class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-zinc-300">{{ game.user_color === 'white' ?
                                        game.black_player : game.white_player }}</p>
                                    <p class="text-[10px] text-zinc-600 uppercase">{{ game.user_color === 'white' ?
                                        '♔ Baltais' : '♚ Melnais' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center text-xs text-zinc-500">{{ game.opening_name || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-[10px] font-black uppercase',
                                        game.result === '1-0' && game.user_color === 'white' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                            game.result === '0-1' && game.user_color === 'black' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                                game.result === '1/2-1/2' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' :
                                                    'bg-red-500/10 text-red-400 border border-red-500/20'
                                    ]">{{ game.result }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="game.is_analyzed" class="text-emerald-400 text-xs">✓ {{
                                        $t('dashboard.analyzed') }}</span>
                                    <span v-else class="text-zinc-600 text-xs">{{ $t('dashboard.not_analyzed') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-zinc-500">{{ game.created_at?.split(' ')[0]
                                }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-else class="max-w-7xl mx-auto" aria-busy="true" aria-live="polite">
            <span class="sr-only">{{ $t('dashboard.loading') }}</span>
            <!-- Header skeleton -->
            <div class="mb-10">
                <div class="h-10 w-48 bg-zinc-800/60 rounded-lg animate-pulse mb-3"></div>
                <div class="h-4 w-72 bg-zinc-800/40 rounded animate-pulse"></div>
            </div>
            <!-- Stat card skeletons -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-10">
                <div v-for="i in 5" :key="'sk-stat-' + i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-6 h-6 bg-zinc-800/60 rounded animate-pulse"></div>
                        <div class="w-16 h-3 bg-zinc-800/40 rounded animate-pulse"></div>
                    </div>
                    <div class="w-20 h-7 bg-zinc-800/60 rounded animate-pulse"></div>
                </div>
            </div>
            <!-- Chart skeletons -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                <div v-for="i in 2" :key="'sk-chart-' + i" class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <div class="w-32 h-3 bg-zinc-800/40 rounded animate-pulse mb-6"></div>
                    <div class="h-48 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                </div>
            </div>
            <!-- Table skeleton -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                <div class="w-40 h-4 bg-zinc-800/40 rounded animate-pulse mb-5"></div>
                <div class="space-y-3">
                    <div v-for="i in 5" :key="'sk-row-' + i" class="h-12 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>
</template>
