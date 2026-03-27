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
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto" v-if="!gamesStore.isLoading && stats">
            <!-- Header -->
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tight">
                    <span class="text-amber-400">♔</span> Panelis
                </h1>
                <p class="text-zinc-500 text-sm mt-1 uppercase tracking-widest font-bold">
                    Sveicināts, {{ authStore.user?.name }} · ELO {{ authStore.user?.elo_rating }}
                </p>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-10">
                <StatCard title="Kopā partijas" :value="stats.summary?.total_games || 0" icon="♟" color="amber" />
                <StatCard title="Uzvaras" :value="stats.summary?.wins || 0" icon="♔" color="emerald" />
                <StatCard title="Zaudējumi" :value="stats.summary?.losses || 0" icon="✕" color="red" />
                <StatCard title="Neizšķirti" :value="stats.summary?.draws || 0" icon="½" color="blue" />
                <StatCard title="Uzvaru %" :value="(stats.summary?.win_rate || 0) + '%'" icon="◈" color="purple" />
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">Kļūdu sadalījums</h3>
                    <ErrorChart :data="stats.errors" />
                </div>
                <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">90 dienu progress</h3>
                    <ProgressChart :data="stats.progress_trend" />
                </div>
            </div>

            <!-- Openings Stats -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6 mb-10" v-if="stats.openings?.length">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">Atklātņu statistika</h3>
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
                            <tr v-for="o in stats.openings.slice(0, 8)" :key="o.opening_name" class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 pr-4 text-sm font-semibold text-zinc-300">{{ o.opening_name }}</td>
                                <td class="py-3 pr-4 text-center text-xs text-zinc-500 font-mono">{{ o.opening_eco }}</td>
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
                    <h3 class="font-black text-white">Pēdējās partijas</h3>
                    <router-link to="/games" class="text-xs font-bold text-amber-400 hover:text-amber-300 uppercase tracking-widest transition-colors">
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
                            <tr v-for="game in recentGames" :key="game.id" class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-zinc-300">{{ game.user_color === 'white' ? game.black_player : game.white_player }}</p>
                                    <p class="text-[10px] text-zinc-600 uppercase">{{ game.user_color === 'white' ? '♔ Baltais' : '♚ Melnais' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center text-xs text-zinc-500">{{ game.opening_name || '—' }}</td>
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
                                    <span v-if="game.is_analyzed" class="text-emerald-400 text-xs">✓ Analizēta</span>
                                    <span v-else class="text-zinc-600 text-xs">Nav analīzes</span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-zinc-500">{{ game.created_at?.split(' ')[0] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-else class="flex flex-col items-center justify-center min-h-[60vh]">
            <div class="w-16 h-16 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-zinc-500 font-bold text-xs uppercase tracking-widest">Ielādē datus...</p>
        </div>
    </div>
</template>
