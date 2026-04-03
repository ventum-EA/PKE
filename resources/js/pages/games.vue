<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useGamesStore } from '../stores/games';
import { useNotification } from '../composables/useNotification';
import { useConfirm } from '../composables/useConfirm';
import GameCard from '../components/GameCard.vue';
import GameUpload from '../components/GameUpload.vue';
import GameAnalysis from '../components/GameAnalysis.vue';

const gamesStore = useGamesStore();
const { notify } = useNotification();
const { confirm } = useConfirm();
const { t } = useI18n();

const isUploadOpen = ref(false);
const selectedGameId = ref(null);
const showAnalysis = ref(false);
const currentFilter = ref('');
const currentSort = ref('-created_at');

const sortOptions = computed(() => [
    { label: t('games.newest'), value: '-created_at' },
    { label: t('games.oldest'), value: 'created_at' },
    { label: t('games.moves_desc'), value: '-total_moves' },
    { label: t('games.moves_asc'), value: 'total_moves' },
]);

const fetchGames = (page = 1) => {
    gamesStore.fetchGames({
        filter: { result: currentFilter.value || undefined },
        sort: currentSort.value,
        page,
        perPage: 12,
    }, true);
};


const handleGameCreated = () => {
    isUploadOpen.value = false;
    fetchGames(1);
    notify(t('games.uploaded'), 'success');
};

const handleAnalyze = async (id) => {
    selectedGameId.value = id;
    showAnalysis.value = true;
};

const handleDelete = async (id) => {
    const ok = await confirm('Dzēst partiju?', 'Šī darbība ir neatgriezeniska.', 'danger');
    if (ok) {
        await gamesStore.deleteGame(id);
        notify(t('games.deleted'), 'success');
    }
};

const handleDownload = async (id) => {
    try {
        await gamesStore.downloadGame(id);
        notify('Partija lejupielādēta', 'success');
    } catch {
        notify('Kļūda lejupielādējot partiju', 'error');
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= gamesStore.pagination.last_page) {
        fetchGames(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

watch(currentFilter, () => fetchGames(1));
watch(currentSort, () => fetchGames(1));

onMounted(() => fetchGames());
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div>
                    <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">♟</span> {{ $t('nav.games') }}</h1>
                    <p class="text-zinc-500 text-sm mt-1">{{ gamesStore.pagination.total }} partija(s) sistēmā</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <select v-model="currentFilter"
                        :aria-label="$t('games.filter_result')"
                        class="bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40">
                        <option value="">{{ $t('games.all') }}</option>
                        <option value="1-0">Baltais uzvar (1-0)</option>
                        <option value="0-1">Melnais uzvar (0-1)</option>
                        <option value="1/2-1/2">Neizšķirts</option>
                    </select>

                    <select v-model="currentSort"
                        :aria-label="$t('games.sort')"
                        class="bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/40">
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>

                    <button @click="isUploadOpen = true"
                        type="button"
                        class="bg-gradient-to-r from-amber-500 to-amber-600 text-black px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-amber-500/20 hover:from-amber-400 hover:to-amber-500 transition-all flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                        ⬆ {{ $t('games.upload') }}
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="gamesStore.isLoading" class="flex items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <!-- Games Grid -->
            <div v-else-if="gamesStore.games.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-10">
                    <div v-for="(game, i) in gamesStore.games" :key="game.id"
                        class="animate-fade-in-up h-full"
                        :style="{ animationDelay: Math.min(i * 40, 400) + 'ms' }">
                        <GameCard :game="game"
                            @analyze="handleAnalyze" @download="handleDownload" @delete="handleDelete" />
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-center gap-2 pb-8">
                    <button @click="changePage(gamesStore.pagination.current_page - 1)"
                        :disabled="gamesStore.pagination.current_page === 1"
                        class="p-3 rounded-xl bg-zinc-900 border border-white/5 text-zinc-500 hover:text-amber-400 disabled:opacity-30 transition-all">
                        ←
                    </button>
                    <span class="px-4 py-2 text-sm text-zinc-400 font-bold">
                        {{ gamesStore.pagination.current_page }} / {{ gamesStore.pagination.last_page }}
                    </span>
                    <button @click="changePage(gamesStore.pagination.current_page + 1)"
                        :disabled="gamesStore.pagination.current_page === gamesStore.pagination.last_page"
                        class="p-3 rounded-xl bg-zinc-900 border border-white/5 text-zinc-500 hover:text-amber-400 disabled:opacity-30 transition-all">
                        →
                    </button>
                </div>
            </div>

            <div v-else class="text-center py-20 bg-zinc-900/30 rounded-3xl border border-dashed border-zinc-800">
                <p class="text-zinc-500 text-4xl mb-4">♟</p>
                <p class="text-zinc-500 font-medium">Nav nevienas partijas. Augšupielādējiet PGN failu.</p>
            </div>

            <!-- Upload Modal -->
            <GameUpload v-if="isUploadOpen" @close="isUploadOpen = false" @created="handleGameCreated" />

            <!-- Analysis Panel -->
            <GameAnalysis v-if="showAnalysis && selectedGameId" :gameId="selectedGameId"
                @close="showAnalysis = false; selectedGameId = null" />
        </div>
    </div>
</template>
