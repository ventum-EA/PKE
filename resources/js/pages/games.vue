<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useGamesStore } from '../stores/games';
import { useNotification } from '../composables/useNotification';
import { useConfirm } from '../composables/useConfirm';
import GameCard from '../components/GameCard.vue';
import GameUpload from '../components/GameUpload.vue';
import GameAnalysis from '../components/GameAnalysis.vue';
import GameImportModal from '../components/GameImportModal.vue';

const gamesStore = useGamesStore();
const { notify } = useNotification();
const { confirm } = useConfirm();
const { t } = useI18n();

const isUploadOpen = ref(false);
const isImportOpen = ref(false);
const selectedGameId = ref(null);
const showAnalysis = ref(false);
const showFilters = ref(false);

const filterResult = ref('');
const filterAnalyzed = ref('');
const filterOpening = ref('');
const filterPlayer = ref('');
const filterDateFrom = ref('');
const filterDateTo = ref('');
const currentSort = ref('-created_at');

const sortOptions = computed(() => [
    { label: t('games.newest'), value: '-created_at' },
    { label: t('games.oldest'), value: 'created_at' },
    { label: t('games.moves_desc'), value: '-total_moves' },
    { label: t('games.moves_asc'), value: 'total_moves' },
    { label: t('games.by_opening'), value: 'opening_name' },
]);

const activeFilterCount = computed(() => {
    let c = 0;
    if (filterResult.value) c++;
    if (filterAnalyzed.value) c++;
    if (filterOpening.value) c++;
    if (filterPlayer.value) c++;
    if (filterDateFrom.value) c++;
    if (filterDateTo.value) c++;
    return c;
});

function buildFilterParams() {
    const f = {};
    if (filterResult.value) f.result = filterResult.value;
    if (filterAnalyzed.value) f.is_analyzed = filterAnalyzed.value === 'yes' ? '1' : '0';
    if (filterOpening.value) f.opening_name = filterOpening.value;
    if (filterPlayer.value) f.player = filterPlayer.value;
    if (filterDateFrom.value) f.played_from = filterDateFrom.value;
    if (filterDateTo.value) f.played_to = filterDateTo.value;
    return f;
}

const fetchGames = (page = 1) => {
    gamesStore.fetchGames({
        filter: buildFilterParams(),
        sort: currentSort.value,
        page,
        perPage: 12,
    }, true);
};

function clearFilters() {
    filterResult.value = '';
    filterAnalyzed.value = '';
    filterOpening.value = '';
    filterPlayer.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    fetchGames(1);
}

const handleGameCreated = () => { isUploadOpen.value = false; fetchGames(1); notify(t('games.uploaded'), 'success'); };
const handleAnalyze = (id) => { selectedGameId.value = id; showAnalysis.value = true; };
const handleDelete = async (id) => {
    const ok = await confirm(t('games.delete_title'), t('games.delete_confirm'), 'danger');
    if (ok) { await gamesStore.deleteGame(id); notify(t('games.deleted'), 'success'); }
};
const handleDownload = async (id) => {
    try { await gamesStore.downloadGame(id); notify(t('games.downloaded'), 'success'); }
    catch { notify(t('games.download_error'), 'error'); }
};
const changePage = (page) => {
    if (page >= 1 && page <= gamesStore.pagination.last_page) {
        fetchGames(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

watch([filterResult, filterAnalyzed, currentSort], () => fetchGames(1));

let openingDebounce = null;
watch(filterOpening, () => { clearTimeout(openingDebounce); openingDebounce = setTimeout(() => fetchGames(1), 400); });
let playerDebounce = null;
watch(filterPlayer, () => { clearTimeout(playerDebounce); playerDebounce = setTimeout(() => fetchGames(1), 400); });
watch([filterDateFrom, filterDateTo], () => fetchGames(1));

onMounted(() => fetchGames());
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white" data-tutorial="games">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-4xl font-black tracking-tight"><span class="text-amber-400">♟</span> {{ $t('nav.games') }}</h1>
                    <p class="text-zinc-500 text-xs sm:text-sm mt-1">{{ gamesStore.pagination.total }} {{ $t('games.total_count') }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="showFilters = !showFilters"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl border transition-all flex items-center gap-2"
                        :class="showFilters || activeFilterCount > 0 ? 'border-amber-500/30 text-amber-400 bg-amber-500/10' : 'border-white/10 text-zinc-400 hover:text-white'">
                        🔍 {{ $t('games.filters') }}
                        <span v-if="activeFilterCount > 0" class="w-5 h-5 flex items-center justify-center bg-amber-500 text-black text-[10px] font-black rounded-full">{{ activeFilterCount }}</span>
                    </button>
                    <button @click="isUploadOpen = true" type="button"
                        class="bg-gradient-to-r from-amber-500 to-amber-600 text-black px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-amber-500/20 hover:from-amber-400 hover:to-amber-500 transition-all text-sm">
                        ⬆ {{ $t('games.upload') }}
                    </button>
                    <button @click="isImportOpen = true" type="button"
                        class="px-5 py-2.5 rounded-xl font-bold text-sm bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/15 transition-all">
                        📥 {{ $t('import.button') }}
                    </button>
                </div>
            </div>

            <!-- Filter panel -->
            <transition name="slide">
                <div v-if="showFilters" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5 mb-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <!-- Result -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_result') }}</label>
                            <select v-model="filterResult" class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:border-amber-500/30">
                                <option value="">{{ $t('games.all') }}</option>
                                <option value="1-0">{{ $t('games.white_wins') }}</option>
                                <option value="0-1">{{ $t('games.black_wins') }}</option>
                                <option value="1/2-1/2">{{ $t('games.draw') }}</option>
                            </select>
                        </div>
                        <!-- Analyzed -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_analyzed') }}</label>
                            <select v-model="filterAnalyzed" class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:border-amber-500/30">
                                <option value="">{{ $t('games.all') }}</option>
                                <option value="yes">{{ $t('games.analyzed_yes') }}</option>
                                <option value="no">{{ $t('games.analyzed_no') }}</option>
                            </select>
                        </div>
                        <!-- Opening search -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_opening') }}</label>
                            <input :aria-label="$t('games.filter_opening')" v-model="filterOpening" type="text" :placeholder="$t('games.filter_opening_ph')"
                                class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/30" />
                        </div>
                        <!-- Player search -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_player') }}</label>
                            <input :aria-label="$t('games.filter_player')" v-model="filterPlayer" type="text" :placeholder="$t('games.filter_player_ph')"
                                class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/30" />
                        </div>
                        <!-- Date from -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_from') }}</label>
                            <input :aria-label="$t('games.filter_from')" v-model="filterDateFrom" type="date"
                                class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:border-amber-500/30" />
                        </div>
                        <!-- Date to -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">{{ $t('games.filter_to') }}</label>
                            <input :aria-label="$t('games.filter_to')" v-model="filterDateTo" type="date"
                                class="w-full bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:border-amber-500/30" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <select v-model="currentSort" class="bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:border-amber-500/30">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <button v-if="activeFilterCount > 0" @click="clearFilters" class="text-xs font-bold text-zinc-500 hover:text-amber-400 transition-colors">
                            ✕ {{ $t('games.clear_filters') }}
                        </button>
                    </div>
                </div>
            </transition>

            <div v-if="gamesStore.isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5" aria-busy="true">
                <div v-for="i in 8" :key="i" class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="h-4 w-12 bg-zinc-800/40 rounded animate-pulse"></div>
                        <div class="h-4 w-24 bg-zinc-800/30 rounded animate-pulse"></div>
                    </div>
                    <div class="h-3 w-full bg-zinc-800/30 rounded animate-pulse"></div>
                    <div class="h-3 w-2/3 bg-zinc-800/20 rounded animate-pulse"></div>
                    <div class="flex gap-2 pt-1">
                        <div class="h-6 w-16 bg-zinc-800/30 rounded-lg animate-pulse"></div>
                        <div class="h-6 w-16 bg-zinc-800/30 rounded-lg animate-pulse"></div>
                    </div>
                </div>
            </div>

            <div v-else-if="gamesStore.games.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 mb-10">
                    <div v-for="(game, i) in gamesStore.games" :key="game.id" class="animate-fade-in-up h-full" :style="{ animationDelay: Math.min(i * 40, 400) + 'ms' }">
                        <GameCard :game="game" @analyze="handleAnalyze" @download="handleDownload" @delete="handleDelete" />
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 pb-8">
                    <button @click="changePage(gamesStore.pagination.current_page - 1)" :disabled="gamesStore.pagination.current_page === 1"
                        class="p-3 rounded-xl bg-zinc-900 border border-white/5 text-zinc-500 hover:text-amber-400 disabled:opacity-30 transition-all">←</button>
                    <span class="px-4 py-2 text-sm text-zinc-400 font-bold">{{ gamesStore.pagination.current_page }} / {{ gamesStore.pagination.last_page }}</span>
                    <button @click="changePage(gamesStore.pagination.current_page + 1)" :disabled="gamesStore.pagination.current_page === gamesStore.pagination.last_page"
                        class="p-3 rounded-xl bg-zinc-900 border border-white/5 text-zinc-500 hover:text-amber-400 disabled:opacity-30 transition-all">→</button>
                </div>
            </div>

            <div v-else class="text-center py-12 sm:py-20 bg-zinc-900/30 rounded-3xl border border-dashed border-zinc-800">
                <p class="text-4xl mb-4">♟</p>
                <h3 class="text-lg font-bold text-zinc-300 mb-2">{{ activeFilterCount > 0 ? $t('games.no_matches') : $t('games.empty_title') }}</h3>
                <p class="text-sm text-zinc-500 max-w-md mx-auto mb-6">{{ activeFilterCount > 0 ? $t('games.no_matches_desc') : $t('games.empty_desc') }}</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button v-if="activeFilterCount > 0" @click="clearFilters" class="px-6 py-3 text-sm font-bold rounded-xl border border-amber-500/20 text-amber-400 hover:bg-amber-500/10 transition-all">
                        {{ $t('games.clear_filters') }}
                    </button>
                    <button v-else @click="isUploadOpen = true" class="px-6 py-3 text-sm font-black rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-black hover:from-amber-400 hover:to-amber-500 active:scale-95 transition-all shadow-lg shadow-amber-500/20">
                        📤 {{ $t('games.upload_pgn') }}
                    </button>
                    <router-link v-if="!activeFilterCount" to="/play" class="px-6 py-3 text-sm font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-white transition-all text-center">
                        ♚ {{ $t('games.play_first') }}
                    </router-link>
                </div>
            </div>

            <GameUpload v-if="isUploadOpen" @close="isUploadOpen = false" @created="handleGameCreated" />
            <GameImportModal v-if="isImportOpen" @close="isImportOpen = false" @imported="() => { isImportOpen = false; fetchGames(1); }" />
            <GameAnalysis v-if="showAnalysis && selectedGameId" :gameId="selectedGameId" @close="showAnalysis = false; selectedGameId = null" />
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active { transition: all 250ms ease; }
.slide-leave-active { transition: all 150ms ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; }
.slide-enter-to { max-height: 300px; }
</style>
