<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';
import { useNotification } from '../composables/useNotification';
import { getLegalMoves } from '../services/chess';
import ChessBoard from '../components/ChessBoard.vue';
import OpeningDrill from '../components/OpeningDrill.vue';

const { notify } = useNotification();
const progress = ref(null);
const puzzles = ref([]);
const currentPuzzleIdx = ref(0);
const puzzleResult = ref(null); // null | 'correct' | 'wrong'
const attempts = ref(0);
const showSolution = ref(false);
const isLoading = ref(true);
const availableGames = ref([]);
const solvedCount = ref(0);
const totalAttempted = ref(0);

const currentPuzzle = computed(() => puzzles.value[currentPuzzleIdx.value] || null);

const categoryMeta = {
    tactical: { lv: 'Taktiskās', icon: '⚔', color: 'amber', desc: 'Kombinācijas, dubultuzbrukumi, tapas, šķēres' },
    positional: { lv: 'Pozicionālās', icon: '◈', color: 'blue', desc: 'Figūru izvietojums, bandinieku struktūra, kontrole' },
    opening: { lv: 'Atklātnes', icon: '♟', color: 'emerald', desc: 'Atklātnes principi, attīstība, centrs' },
    endgame: { lv: 'Galotnes', icon: '♔', color: 'purple', desc: 'Karalis un bandinieki, torņu galotnes, mattēšana' },
};

const getAccuracy = (cat) => {
    if (!cat?.total || cat.total === 0) return 0;
    return Math.round((Number(cat.correct) / Number(cat.total)) * 100);
};

const sessionAccuracy = computed(() => {
    if (totalAttempted.value === 0) return 0;
    return Math.round((solvedCount.value / totalAttempted.value) * 100);
});

// --- Personalized opening training (req 2.2.15) ------------------------
const weakOpenings = ref([]);
const isLoadingOpenings = ref(false);
const hasFetchedOpenings = ref(false);
const activeDrillOpening = ref(null);

function startDrill(opening) {
    activeDrillOpening.value = opening;
    // Smooth scroll so the drill is visible even if triggered from mid-page
    setTimeout(() => {
        const el = document.getElementById('opening-drill-anchor');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 60);
}

function closeDrill() {
    activeDrillOpening.value = null;
}

function onDrillComplete({ attempts }) {
    notify(`Atklātne apgūta! Mēģinājumi: ${attempts}`, 'success');
}

async function fetchOpeningTraining() {
    isLoadingOpenings.value = true;
    try {
        const { data } = await api.post('/training/openings');
        weakOpenings.value = data.weak_openings || [];
        hasFetchedOpenings.value = true;
        if (weakOpenings.value.length === 0) {
            notify('Nepietiek partiju, lai noteiktu vājākās atklātnes', 'info');
        }
    } catch {
        notify('Neizdevās ielādēt atklātņu ieteikumus', 'error');
    } finally {
        isLoadingOpenings.value = false;
    }
}

onMounted(async () => {
    try {
        const { data } = await api.get('/training/progress');
        progress.value = data;
    } catch {}

    try {
        const { data } = await api.get('/games', { 'filter[is_analyzed]': 1, perPage: 50 });
        const payload = data.games || data;
        availableGames.value = (payload.data || []).filter(g => g.is_analyzed);
    } catch {}

    isLoading.value = false;
});

async function generatePuzzles(gameId) {
    puzzleResult.value = null;
    currentPuzzleIdx.value = 0;
    attempts.value = 0;
    showSolution.value = false;
    solvedCount.value = 0;
    totalAttempted.value = 0;

    try {
        const { data } = await api.post(`/training/generate/${gameId}`);
        puzzles.value = data.puzzles || [];
        if (puzzles.value.length === 0) {
            notify('Nav kļūdu, no kurām ģenerēt uzdevumus', 'info');
        }
    } catch {
        notify('Neizdevās ģenerēt uzdevumus', 'error');
    }
}

async function handleMove(move) {
    if (!currentPuzzle.value || puzzleResult.value === 'correct') return;

    const moveUci = move.from + move.to;
    attempts.value++;

    try {
        const { data } = await api.post(`/training/submit/${currentPuzzle.value.id}`, { move: moveUci });

        if (data.is_correct) {
            puzzleResult.value = 'correct';
            solvedCount.value++;
            totalAttempted.value++;
        } else {
            puzzleResult.value = 'wrong';
            // Allow retry — don't advance
            setTimeout(() => {
                if (puzzleResult.value === 'wrong') puzzleResult.value = null;
            }, 1500);
        }
    } catch {
        notify('Kļūda iesniedzot atbildi', 'error');
    }
}

function revealSolution() {
    showSolution.value = true;
    if (puzzleResult.value !== 'correct') {
        totalAttempted.value++;
    }
}

function nextPuzzle() {
    if (currentPuzzleIdx.value < puzzles.value.length - 1) {
        currentPuzzleIdx.value++;
        puzzleResult.value = null;
        attempts.value = 0;
        showSolution.value = false;
    } else {
        notify(`Sesija pabeigta! ${solvedCount.value}/${totalAttempted.value} pareizi.`, 'success');
        puzzles.value = [];
        currentPuzzleIdx.value = 0;
        puzzleResult.value = null;
        // Refresh progress
        api.get('/training/progress').then(({ data }) => { progress.value = data; }).catch(() => {});
    }
}

function retryPuzzle() {
    puzzleResult.value = null;
    showSolution.value = false;
}
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-5xl mx-auto">
            <div class="mb-10">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">⚡</span> {{ $t('nav.training') }}</h1>
                <p class="text-zinc-500 text-sm mt-1">{{ $t('training.subtitle') }}</p>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else>
                <!-- Category Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                    <div v-for="(meta, key) in categoryMeta" :key="key"
                        class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xl">{{ meta.icon }}</span>
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ meta.lv }}</p>
                        </div>
                        <p class="text-xs text-zinc-600 mb-3">{{ meta.desc }}</p>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-lg font-black text-white">
                                {{ progress?.by_category?.find(c => c.category === key)?.total || 0 }}
                            </span>
                            <span class="text-xs font-bold" :class="{
                                'text-amber-400': meta.color === 'amber',
                                'text-blue-400': meta.color === 'blue',
                                'text-emerald-400': meta.color === 'emerald',
                                'text-purple-400': meta.color === 'purple',
                            }">
                                {{ getAccuracy(progress?.by_category?.find(c => c.category === key)) }}%
                            </span>
                        </div>
                        <div class="w-full bg-zinc-800 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-700"
                                :class="{
                                    'bg-amber-400': meta.color === 'amber',
                                    'bg-blue-400': meta.color === 'blue',
                                    'bg-emerald-400': meta.color === 'emerald',
                                    'bg-purple-400': meta.color === 'purple',
                                }"
                                :style="{ width: getAccuracy(progress?.by_category?.find(c => c.category === key)) + '%' }">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personalized opening training (req 2.2.15) -->
                <section class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 sm:p-6 mb-10"
                    aria-labelledby="opening-training-heading">
                    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
                        <div class="min-w-0">
                            <h3 id="opening-training-heading" class="text-sm font-black text-white">
                                <span class="text-amber-400">📖</span> {{ $t('training.opening_training') }}
                            </h3>
                            <p class="text-xs text-zinc-500 mt-1">
                                Sistēma analizē tavas partijas un piedāvā praktizēt atklātnes, kurās tev ir zemākais uzvaru rādītājs.
                            </p>
                        </div>
                        <button @click="fetchOpeningTraining" :disabled="isLoadingOpenings"
                            class="px-4 py-2 bg-amber-500/10 text-amber-400 font-bold rounded-xl border border-amber-500/20 hover:bg-amber-500/20 disabled:opacity-40 transition-all text-xs uppercase tracking-wider focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                            {{ isLoadingOpenings ? $t('common.loading') : $t('training.analyze_openings') }}
                        </button>
                    </div>

                    <div v-if="hasFetchedOpenings && weakOpenings.length === 0"
                        class="text-xs text-zinc-500 italic px-3 py-4 bg-black/30 rounded-lg" role="status">
                        Pagaidām nav pietiekami daudz partiju, lai ieteiktu atklātnes treniņam. Spēlē vismaz 2 partijas vienā un tajā pašā atklātnē.
                    </div>

                    <ul v-if="weakOpenings.length > 0" class="space-y-3" role="list">
                        <li v-for="(o, i) in weakOpenings" :key="o.opening_eco + '-' + i"
                            class="bg-black/30 border border-white/5 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="font-mono text-[10px] font-black bg-amber-500/10 text-amber-400 px-2 py-1 rounded shrink-0">
                                    {{ o.eco || o.opening_eco }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-white truncate">{{ o.name_lv || o.opening_name }}</p>
                                    <p class="text-[10px] text-zinc-500 font-mono truncate mt-0.5">{{ o.moves }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-wider text-zinc-600">{{ $t('training.win_rate') }}</p>
                                    <p class="text-sm font-black"
                                        :class="o.win_rate < 30 ? 'text-red-400' : o.win_rate < 50 ? 'text-amber-400' : 'text-emerald-400'">
                                        {{ o.win_rate }}%
                                    </p>
                                </div>
                                <button type="button" @click="startDrill(o)"
                                    :disabled="!o.moves"
                                    class="px-3 py-2 bg-amber-500/10 text-amber-400 font-bold rounded-lg border border-amber-500/20 hover:bg-amber-500/20 disabled:opacity-30 disabled:cursor-not-allowed text-[10px] uppercase tracking-wider transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60">
                                    ⚡ {{ $t('training.drill') }}
                                </button>
                            </div>
                        </li>
                    </ul>

                    <!-- Interactive drill (appears when user clicks Treniņš) -->
                    <div id="opening-drill-anchor"></div>
                    <div v-if="activeDrillOpening" class="mt-5 animate-fade-in-up">
                        <OpeningDrill :opening="activeDrillOpening"
                            @close="closeDrill"
                            @complete="onDrillComplete" />
                    </div>
                </section>

                <!-- Active Puzzle -->
                <div v-if="currentPuzzle" class="mb-10">
                    <!-- Session progress -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-white">
                            Uzdevums {{ currentPuzzleIdx + 1 }} / {{ puzzles.length }}
                        </h3>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="text-zinc-500">Mēģinājumi: <span class="text-white font-bold">{{ attempts }}</span></span>
                            <span class="text-zinc-500">Sesija: <span class="text-amber-400 font-bold">{{ sessionAccuracy }}%</span></span>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="w-full lg:w-[min(55vw,80vh,640px)]">
                            <ChessBoard
                                :fen="currentPuzzle.fen"
                                orientation="white"
                                :interactive="puzzleResult !== 'correct' && !showSolution"
                               
                                @move="handleMove"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                                <!-- Category badge -->
                                <div class="flex items-center justify-between mb-4">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase border',
                                        currentPuzzle.category === 'tactical' ? 'border-amber-500/20 text-amber-400 bg-amber-500/10' :
                                        currentPuzzle.category === 'opening' ? 'border-emerald-500/20 text-emerald-400 bg-emerald-500/10' :
                                        currentPuzzle.category === 'endgame' ? 'border-purple-500/20 text-purple-400 bg-purple-500/10' :
                                        'border-blue-500/20 text-blue-400 bg-blue-500/10'
                                    ]">
                                        {{ categoryMeta[currentPuzzle.category]?.lv || currentPuzzle.category }}
                                    </span>
                                    <span class="text-[10px] text-zinc-600 font-mono">Gājiens #{{ currentPuzzle.move_number }}</span>
                                </div>

                                <p class="text-sm text-zinc-400 mb-4">Atrodiet labāko gājienu šajā pozīcijā.</p>

                                <!-- Hint -->
                                <div v-if="currentPuzzle.hint && (attempts >= 2 || showSolution)" class="bg-black/30 rounded-xl p-3 mb-4 border border-white/5">
                                    <p class="text-xs text-zinc-500 font-bold uppercase mb-1">Padoms</p>
                                    <p class="text-sm text-zinc-400">{{ currentPuzzle.hint }}</p>
                                </div>
                                <p v-else-if="currentPuzzle.hint && attempts >= 1" class="text-xs text-zinc-600 mb-4">
                                    Padoms parādīsies pēc 2 mēģinājumiem
                                </p>

                                <!-- Result: Correct -->
                                <div v-if="puzzleResult === 'correct'" class="rounded-xl p-4 text-center mb-4 bg-emerald-500/10 border border-emerald-500/20">
                                    <p class="text-lg font-black text-emerald-400">✓ Pareizi!</p>
                                    <p class="text-xs text-zinc-500 mt-1">Atrisināts {{ attempts === 1 ? 'pirmajā mēģinājumā' : `${attempts} mēģinājumos` }}</p>
                                </div>

                                <!-- Result: Wrong (temporary, auto-clears for retry) -->
                                <div v-if="puzzleResult === 'wrong'" class="rounded-xl p-4 text-center mb-4 bg-red-500/10 border border-red-500/20">
                                    <p class="text-lg font-black text-red-400">✕ Nepareizi</p>
                                    <p class="text-xs text-zinc-500 mt-1">Mēģiniet vēlreiz...</p>
                                </div>

                                <!-- Solution revealed -->
                                <div v-if="showSolution" class="rounded-xl p-4 text-center mb-4 bg-amber-500/10 border border-amber-500/20">
                                    <p class="text-sm font-bold text-amber-400">Pareizais gājiens:</p>
                                    <p class="text-2xl font-black text-white mt-1 font-mono">{{ currentPuzzle.correct_san || '—' }}</p>
                                </div>

                                <!-- Action buttons -->
                                <div class="space-y-2">
                                    <button v-if="puzzleResult === 'correct' || showSolution" @click="nextPuzzle"
                                        class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-black font-black rounded-xl uppercase text-sm">
                                        {{ currentPuzzleIdx < puzzles.length - 1 ? 'Nākamais uzdevums →' : 'Pabeigt sesiju' }}
                                    </button>

                                    <button v-if="puzzleResult !== 'correct' && !showSolution && attempts >= 3" @click="revealSolution"
                                        class="w-full py-2.5 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-amber-400 transition-all">
                                        👁 Parādīt atbildi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game selector -->
                <div v-else class="bg-zinc-900/50 border border-white/5 rounded-3xl p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Izvēlieties analizētu partiju</h3>
                    <p class="text-sm text-zinc-500 mb-4">Treniņu uzdevumi tiks ģenerēti no pozīcijām, kurās jūs pieļāvāt kļūdas.</p>

                    <div v-if="availableGames.length > 0" class="space-y-2 max-h-60 overflow-y-auto">
                        <div v-for="game in availableGames" :key="game.id"
                            @click="generatePuzzles(game.id)"
                            class="flex items-center justify-between p-3 bg-black/20 rounded-xl cursor-pointer hover:bg-black/30 transition-all border border-white/5 hover:border-amber-500/20">
                            <div>
                                <p class="text-sm font-bold text-zinc-300">{{ game.white_player }} vs {{ game.black_player }}</p>
                                <p class="text-[10px] text-zinc-600">{{ game.opening_name || 'Nezināma atklātne' }} · {{ game.total_moves }} gāj.</p>
                            </div>
                            <span class="text-amber-400 text-xs font-bold">Ģenerēt →</span>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <p class="text-3xl mb-3">🎯</p>
                        <p class="text-zinc-500 text-sm">Nav analizētu partiju. Vispirms augšupielādējiet un analizējiet partiju.</p>
                        <router-link to="/games" class="inline-block mt-4 px-6 py-2.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold rounded-xl text-sm">
                            Doties uz partijām →
                        </router-link>
                    </div>

                    <!-- Also link to openings -->
                    <div class="mt-6 pt-6 border-t border-white/5 text-center">
                        <p class="text-xs text-zinc-600 mb-3">Vai vēlaties praktizēt atklātnes?</p>
                        <router-link to="/openings" class="inline-block px-6 py-2.5 bg-zinc-800 text-zinc-300 font-bold rounded-xl text-sm hover:bg-zinc-700 transition-all">
                            📖 Atklātņu pārlūks →
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
