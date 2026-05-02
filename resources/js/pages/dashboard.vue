<script setup>
import { onMounted, computed } from 'vue';
import { useGamesStore } from '../stores/games';
import { useAuthStore } from '../stores/auth';
import { useTutorial } from '../composables/useTutorial';
import StatCard from '../components/StatCard.vue';
import ErrorChart from '../components/ErrorChart.vue';
import ProgressChart from '../components/ProgressChart.vue';
import PatternInsights from '../components/PatternInsights.vue';
import RecommendationsPanel from '../components/RecommendationsPanel.vue';

const authStore = useAuthStore();
const gamesStore = useGamesStore();
const { start: startTutorial } = useTutorial();

onMounted(() => {
    gamesStore.fetchStats(true);
    gamesStore.fetchGames({ perPage: 5, sort: '-created_at' }, true);
});

const stats = computed(() => gamesStore.stats);
const recentGames = computed(() => gamesStore.games.slice(0, 5));

const isReady = computed(() => stats.value !== null || gamesStore.statsError !== null);
const hasError = computed(() => !!gamesStore.statsError && stats.value === null);

function retry() {
    gamesStore.fetchStats(true);
    gamesStore.fetchGames({ perPage: 5, sort: '-created_at' }, true);
}
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <!-- Error state -->
        <div v-if="hasError" class="max-w-2xl mx-auto mt-20">
            <div role="alert" class="bg-red-500/10 border border-red-500/20 rounded-3xl p-8 text-center">
                <p class="text-4xl mb-4" aria-hidden="true">⚠</p>
                <h2 class="text-xl font-black text-red-400 mb-2">{{ $t('dashboard.load_failed') }}</h2>
                <p class="text-sm text-zinc-400 mb-6">{{ gamesStore.statsError }}</p>
                <button type="button" @click="retry"
                    class="px-6 py-2.5 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 text-xs uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                    {{ $t('common.retry') }}
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto" v-else-if="isReady && stats">
            <div class="mb-6 sm:mb-10">
                <div class="flex items-start sm:items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-4xl font-black tracking-tight">
                            <span class="text-amber-400">♔</span> {{ $t('dashboard.title') }}
                        </h1>
                        <p class="text-zinc-500 text-xs sm:text-sm mt-1 uppercase tracking-widest font-bold">
                            {{ $t("dashboard.welcome", { name: authStore.user?.name }) }} · ELO {{ authStore.user?.elo_rating }}
                        </p>
                    </div>
                    <button @click="startTutorial"
                        class="px-3 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-500 hover:text-amber-400 hover:border-amber-500/20 transition-all shrink-0"
                        :title="$t('tutorial.restart')">
                        ❓ {{ $t('tutorial.help') }}
                    </button>
                </div>
            </div>

            <!-- Quick Actions — beginner-friendly order -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 sm:mb-10" data-tutorial="dashboard">
                <router-link to="/play" class="group bg-zinc-900/50 border border-white/5 hover:border-amber-500/20 rounded-2xl p-4 transition-all">
                    <span class="text-2xl block mb-2">♚</span>
                    <p class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors">{{ $t('quickstart.play') }}</p>
                    <p class="text-[10px] text-zinc-600 mt-0.5">{{ $t('quickstart.play_desc') }}</p>
                </router-link>
                <router-link to="/daily" class="group bg-zinc-900/50 border border-white/5 hover:border-purple-500/20 rounded-2xl p-4 transition-all">
                    <span class="text-2xl block mb-2">📅</span>
                    <p class="text-sm font-bold text-white group-hover:text-purple-400 transition-colors">{{ $t('nav.daily') }}</p>
                    <p class="text-[10px] text-zinc-600 mt-0.5">{{ $t('quickstart.daily_desc') }}</p>
                </router-link>
                <router-link to="/multiplayer" class="group bg-zinc-900/50 border border-white/5 hover:border-emerald-500/20 rounded-2xl p-4 transition-all">
                    <span class="text-2xl block mb-2">⚔</span>
                    <p class="text-sm font-bold text-white group-hover:text-emerald-400 transition-colors">{{ $t('nav.multiplayer') }}</p>
                    <p class="text-[10px] text-zinc-600 mt-0.5">{{ $t('quickstart.multiplayer_desc') }}</p>
                </router-link>
                <router-link to="/lessons" class="group bg-zinc-900/50 border border-white/5 hover:border-amber-500/20 rounded-2xl p-4 transition-all">
                    <span class="text-2xl block mb-2">🎓</span>
                    <p class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors">{{ $t('quickstart.lessons') }}</p>
                    <p class="text-[10px] text-zinc-600 mt-0.5">{{ $t('quickstart.lessons_desc') }}</p>
                </router-link>
            </div>

            <!-- Stat Cards (hidden for brand-new users — shown after first game) -->
            <div v-if="stats.summary?.total_games > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6 sm:mb-10">
                <StatCard :title="$t('dashboard.total_games')"  :value="stats.summary?.total_games || 0" icon="♟" color="amber" />
                <StatCard :title="$t('dashboard.wins')"  :value="stats.summary?.wins || 0" icon="♔" color="emerald" />
                <StatCard :title="$t('dashboard.losses')"  :value="stats.summary?.losses || 0" icon="✕" color="red" />
                <StatCard :title="$t('dashboard.draws')"  :value="stats.summary?.draws || 0" icon="½" color="blue" />
                <StatCard :title="$t('dashboard.win_rate')"  :value="(stats.summary?.win_rate || 0) + '%'" icon="◈" color="purple" />
            </div>

            <!-- Beginner guidance for new users with 0 games -->
            <div v-if="!stats.summary?.total_games" class="mb-8">
                <div class="bg-gradient-to-br from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/10 rounded-2xl sm:rounded-3xl p-5 sm:p-8 mb-6">
                    <h2 class="text-lg sm:text-xl font-black text-white mb-3">{{ $t('beginner.welcome_title') }}</h2>
                    <p class="text-sm text-zinc-400 leading-relaxed mb-6">{{ $t('beginner.welcome_desc') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <router-link to="/lessons"
                            class="p-5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/15 transition-all group">
                            <div class="flex items-start gap-4">
                                <span class="text-3xl shrink-0">♟</span>
                                <div>
                                    <p class="font-bold text-emerald-400 mb-1">{{ $t('beginner.path_learn') }}</p>
                                    <p class="text-xs text-zinc-500">{{ $t('beginner.path_learn_desc') }}</p>
                                    <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider text-emerald-400/60 bg-emerald-500/10 px-2 py-0.5 rounded-full">{{ $t('beginner.recommended') }}</span>
                                </div>
                            </div>
                        </router-link>
                        <router-link to="/play"
                            class="p-5 rounded-xl bg-blue-500/10 border border-blue-500/20 hover:bg-blue-500/15 transition-all group">
                            <div class="flex items-start gap-4">
                                <span class="text-3xl shrink-0">♚</span>
                                <div>
                                    <p class="font-bold text-blue-400 mb-1">{{ $t('beginner.path_play') }}</p>
                                    <p class="text-xs text-zinc-500">{{ $t('beginner.path_play_desc') }}</p>
                                </div>
                            </div>
                        </router-link>
                    </div>
                </div>

                <!-- Beginner learning path -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-5 sm:p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('beginner.roadmap_title') }}</h3>
                    <div class="space-y-3">
                        <div v-for="(step, i) in [
                            { icon: '♟', key: 'step_learn', route: '/lessons', color: 'amber' },
                            { icon: '♚', key: 'step_play', route: '/play', color: 'emerald' },
                            { icon: '◆', key: 'step_puzzles', route: '/puzzles', color: 'blue' },
                            { icon: '📖', key: 'step_openings', route: '/openings', color: 'purple' },
                            { icon: '🎓', key: 'step_lessons', route: '/lessons', color: 'rose' },
                        ]" :key="step.key" class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0"
                                :class="`bg-${step.color}-500/10 text-${step.color}-400 border border-${step.color}-500/20`">
                                {{ i + 1 }}
                            </div>
                            <router-link :to="step.route" class="flex-1 flex items-center justify-between p-3 rounded-xl hover:bg-white/[0.02] transition-colors group">
                                <div>
                                    <p class="text-sm font-bold text-zinc-300 group-hover:text-amber-400 transition-colors">{{ $t('beginner.' + step.key) }}</p>
                                    <p class="text-[10px] text-zinc-600">{{ $t('beginner.' + step.key + '_desc') }}</p>
                                </div>
                                <span class="text-zinc-700 group-hover:text-amber-400 transition-colors">→</span>
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-10">
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('dashboard.error_distribution') }}</h3>
                    <ErrorChart :data="stats.errors" />
                </div>
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-6">{{ $t('dashboard.progress_90d') }}</h3>
                    <ProgressChart :data="stats.progress_trend" />
                </div>
            </div>

            <!-- Pattern Insights (weakness detection) -->
            <div class="mb-6 sm:mb-10" v-if="stats.summary?.total_games >= 3">
                <PatternInsights />
            </div>

            <!-- Personalized Recommendations (§2.2.6) -->
            <div class="mb-6 sm:mb-10" v-if="stats.summary?.total_games >= 1">
                <RecommendationsPanel />
            </div>

            <!-- Openings Stats -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6 mb-6 sm:mb-10" v-if="stats.openings?.length">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4 sm:mb-6">{{ $t('dashboard.opening_stats') }}</h3>

                <!-- Mobile card layout -->
                <div class="sm:hidden space-y-2">
                    <div v-for="o in stats.openings.slice(0, 8)" :key="o.opening_name" class="bg-black/20 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-semibold text-zinc-300 truncate mr-2">{{ o.opening_name }}</span>
                            <span class="text-[10px] font-mono text-zinc-500 shrink-0">{{ o.opening_eco }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-zinc-400 font-bold">{{ o.total }}</span>
                            <span class="text-emerald-400">+{{ o.wins }}</span>
                            <span class="text-blue-400">={{ o.draws }}</span>
                            <span class="text-red-400">-{{ o.losses }}</span>
                        </div>
                    </div>
                </div>

                <!-- Desktop table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">
                                <th class="pb-3 pr-4">{{ $t('dashboard.opening_stats') }}</th>
                                <th class="pb-3 pr-4 text-center">ECO</th>
                                <th class="pb-3 pr-4 text-center">{{ $t('dashboard.total_games') }}</th>
                                <th class="pb-3 pr-4 text-center">{{ $t('dashboard.wins') }}</th>
                                <th class="pb-3 pr-4 text-center">{{ $t('dashboard.draws') }}</th>
                                <th class="pb-3 text-center">{{ $t('dashboard.losses') }}</th>
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
            <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl overflow-hidden">
                <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/5 flex justify-between items-center">
                    <h3 class="font-black text-white text-sm sm:text-base">{{ $t('dashboard.recent_games') }}</h3>
                    <router-link to="/games" class="text-[10px] sm:text-xs font-bold text-amber-400 hover:text-amber-300 uppercase tracking-widest transition-colors">
                        {{ $t('dashboard.all_games') }}
                    </router-link>
                </div>

                <!-- Mobile card layout -->
                <div class="sm:hidden divide-y divide-white/5">
                    <div v-for="game in recentGames" :key="game.id" class="p-4 hover:bg-white/[0.02] transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-bold text-zinc-300">{{ game.user_color === 'white' ? game.black_player : game.white_player }}</p>
                                <p class="text-[10px] text-zinc-600 uppercase">{{ game.user_color === 'white' ? '♔ ' + $t('common.white') : '♚ ' + $t('common.black') }}</p>
                            </div>
                            <span :class="[
                                'px-3 py-1 rounded-full text-[10px] font-black uppercase shrink-0',
                                game.result === '1-0' && game.user_color === 'white' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                game.result === '0-1' && game.user_color === 'black' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                game.result === '1/2-1/2' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' :
                                'bg-red-500/10 text-red-400 border border-red-500/20'
                            ]">{{ game.result }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-zinc-500 truncate mr-2">{{ game.opening_name || '—' }}</span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span v-if="game.is_analyzed" class="text-emerald-400">✓</span>
                                <span v-else class="text-zinc-700">○</span>
                                <span class="text-zinc-600">{{ game.created_at?.split(' ')[0] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop table layout -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                <th class="px-6 py-3">{{ $t('dashboard.recent_games') }}</th>
                                <th class="px-6 py-3 text-center">{{ $t('dashboard.opening_stats') }}</th>
                                <th class="px-6 py-3 text-center">{{ $t('games.filter_result') }}</th>
                                <th class="px-6 py-3 text-center">{{ $t('dashboard.analyzed') }}</th>
                                <th class="px-6 py-3 text-right">{{ $t('profile.member_since') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="game in recentGames" :key="game.id" class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-zinc-300">{{ game.user_color === 'white' ? game.black_player : game.white_player }}</p>
                                    <p class="text-[10px] text-zinc-600 uppercase">{{ game.user_color === 'white' ? '♔ ' + $t('common.white') : '♚ ' + $t('common.black') }}</p>
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
                                    <span v-if="game.is_analyzed" class="text-emerald-400 text-xs">✓ {{ $t('dashboard.analyzed') }}</span>
                                    <span v-else class="text-zinc-600 text-xs">{{ $t('dashboard.not_analyzed') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-zinc-500">{{ game.created_at?.split(' ')[0] }}</td>
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
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6 sm:mb-10">
                <div v-for="i in 5" :key="'sk-stat-' + i"
                    class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-6 h-6 bg-zinc-800/60 rounded animate-pulse"></div>
                        <div class="w-16 h-3 bg-zinc-800/40 rounded animate-pulse"></div>
                    </div>
                    <div class="w-20 h-7 bg-zinc-800/60 rounded animate-pulse"></div>
                </div>
            </div>
            <!-- Chart skeletons -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-10">
                <div v-for="i in 2" :key="'sk-chart-' + i"
                    class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6">
                    <div class="w-32 h-3 bg-zinc-800/40 rounded animate-pulse mb-6"></div>
                    <div class="h-48 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                </div>
            </div>
            <!-- Table skeleton -->
            <div class="bg-zinc-900/50 border border-white/5 rounded-2xl sm:rounded-3xl p-4 sm:p-6">
                <div class="w-40 h-4 bg-zinc-800/40 rounded animate-pulse mb-5"></div>
                <div class="space-y-3">
                    <div v-for="i in 5" :key="'sk-row-' + i"
                        class="h-12 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>
</template>
