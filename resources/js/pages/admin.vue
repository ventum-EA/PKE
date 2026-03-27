<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';

const users = ref([]);
const allGames = ref([]);
const isLoading = ref(true);
const activeTab = ref('overview');

onMounted(async () => {
    try {
        const [usersRes, gamesRes] = await Promise.all([
            api.get('/users', { perPage: 100 }),
            api.get('/games', { perPage: 100 }),
        ]);
        const uPayload = usersRes.data.users || usersRes.data;
        users.value = uPayload.data || [];
        const gPayload = gamesRes.data.games || gamesRes.data;
        allGames.value = gPayload.data || [];
    } catch {}
    isLoading.value = false;
});

const totalUsers = computed(() => users.value.length);
const totalGames = computed(() => allGames.value.length);
const analyzedGames = computed(() => allGames.value.filter(g => g.is_analyzed).length);
const avgMoves = computed(() => {
    if (!allGames.value.length) return 0;
    return Math.round(allGames.value.reduce((s, g) => s + (g.total_moves || 0), 0) / allGames.value.length);
});
const resultBreakdown = computed(() => {
    const counts = { '1-0': 0, '0-1': 0, '1/2-1/2': 0, '*': 0 };
    allGames.value.forEach(g => { counts[g.result] = (counts[g.result] || 0) + 1; });
    return counts;
});
const topOpenings = computed(() => {
    const map = {};
    allGames.value.forEach(g => {
        if (g.opening_name) {
            map[g.opening_name] = (map[g.opening_name] || 0) + 1;
        }
    });
    return Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, 10);
});

async function deleteUser(id) {
    if (!confirm('Dzēst šo lietotāju?')) return;
    try {
        await api.delete(`/user/${id}`);
        users.value = users.value.filter(u => u.id !== id);
    } catch {}
}
</script>

<template>
    <div class="min-h-screen p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">⚙</span> Administrācija</h1>
                <p class="text-zinc-500 text-sm mt-1">Platformas pārvaldības panelis</p>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else>
                <!-- Tabs -->
                <div class="flex items-center gap-1 mb-8 border-b border-white/5 pb-1">
                    <button v-for="tab in ['overview', 'users', 'games']" :key="tab"
                        @click="activeTab = tab"
                        :class="['px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all',
                            activeTab === tab ? 'text-amber-400 bg-amber-500/10 border-b-2 border-amber-400' : 'text-zinc-500 hover:text-zinc-300']">
                        {{ tab === 'overview' ? '◈ Pārskats' : tab === 'users' ? '◉ Lietotāji' : '♟ Partijas' }}
                    </button>
                </div>

                <!-- Overview tab -->
                <div v-if="activeTab === 'overview'">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Lietotāji</p>
                            <p class="text-3xl font-black text-amber-400 mt-1">{{ totalUsers }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Partijas</p>
                            <p class="text-3xl font-black text-emerald-400 mt-1">{{ totalGames }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Analizētas</p>
                            <p class="text-3xl font-black text-blue-400 mt-1">{{ analyzedGames }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Vid. gājieni</p>
                            <p class="text-3xl font-black text-purple-400 mt-1">{{ avgMoves }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Results breakdown -->
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Rezultātu sadalījums</h3>
                            <div class="space-y-3">
                                <div v-for="(count, result) in resultBreakdown" :key="result" class="flex items-center gap-3">
                                    <span class="text-sm font-mono text-zinc-400 w-16">{{ result }}</span>
                                    <div class="flex-1 bg-zinc-800 rounded-full h-3">
                                        <div class="h-3 rounded-full transition-all bg-amber-400"
                                            :style="{ width: (totalGames > 0 ? (count / totalGames) * 100 : 0) + '%' }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-zinc-400 w-8 text-right">{{ count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Top openings -->
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Populārākās atklātnes</h3>
                            <div class="space-y-2">
                                <div v-for="[name, count] in topOpenings" :key="name" class="flex items-center justify-between">
                                    <span class="text-sm text-zinc-300">{{ name }}</span>
                                    <span class="text-sm font-bold text-amber-400">{{ count }}</span>
                                </div>
                                <p v-if="topOpenings.length === 0" class="text-zinc-600 text-sm">Nav datu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users tab -->
                <div v-if="activeTab === 'users'">
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                    <th class="px-5 py-3">ID</th>
                                    <th class="px-5 py-3">Lietotājs</th>
                                    <th class="px-5 py-3 text-center">Loma</th>
                                    <th class="px-5 py-3 text-center">ELO</th>
                                    <th class="px-5 py-3 text-center">Datums</th>
                                    <th class="px-5 py-3 text-right">Darbības</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-xs text-zinc-600 font-mono">{{ user.id }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-bold text-zinc-300">{{ user.name }}</p>
                                        <p class="text-xs text-zinc-600">{{ user.email }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase border',
                                            user.role === 'admin' ? 'border-amber-500/20 text-amber-400 bg-amber-500/10' : 'border-zinc-700 text-zinc-500']">
                                            {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-sm font-bold text-zinc-400">{{ user.elo_rating }}</td>
                                    <td class="px-5 py-3 text-center text-xs text-zinc-500">{{ user.created_at?.split(' ')[0] }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <button @click="deleteUser(user.id)" class="text-xs text-red-400/60 hover:text-red-400 font-bold">Dzēst</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Games tab -->
                <div v-if="activeTab === 'games'">
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                    <th class="px-5 py-3">ID</th>
                                    <th class="px-5 py-3">Spēlētāji</th>
                                    <th class="px-5 py-3 text-center">Atklātne</th>
                                    <th class="px-5 py-3 text-center">Rezultāts</th>
                                    <th class="px-5 py-3 text-center">Gāj.</th>
                                    <th class="px-5 py-3 text-center">Analīze</th>
                                    <th class="px-5 py-3 text-right">Datums</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="game in allGames" :key="game.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-xs text-zinc-600 font-mono">{{ game.id }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-bold text-zinc-300">{{ game.white_player }} vs {{ game.black_player }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-center text-xs text-zinc-500">{{ game.opening_name || '—' }}</td>
                                    <td class="px-5 py-3 text-center text-sm font-bold text-zinc-400">{{ game.result }}</td>
                                    <td class="px-5 py-3 text-center text-sm text-zinc-500">{{ game.total_moves }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <span :class="game.is_analyzed ? 'text-emerald-400' : 'text-zinc-600'" class="text-xs">
                                            {{ game.is_analyzed ? '✓' : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs text-zinc-500">{{ game.played_at || game.created_at?.split(' ')[0] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
