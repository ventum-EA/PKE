<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';

const users = ref([]);
const allGames = ref([]);
const auditLogs = ref([]);
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

    // Fetch audit logs in background (non-blocking)
    try {
        const { data } = await api.get('/audit-logs', { perPage: 100 });
        auditLogs.value = data.audit_logs?.data || [];
    } catch {}
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

// ── Analytics ───────────────────────────────────────────────────────
const gamesPerDay = computed(() => {
    const map = {};
    allGames.value.forEach(g => {
        const day = (g.played_at || g.created_at || '').split(/[T ]/)[0];
        if (day) map[day] = (map[day] || 0) + 1;
    });
    return Object.entries(map)
        .sort(([a], [b]) => a.localeCompare(b))
        .slice(-14) // last 14 days
        .map(([date, count]) => ({ date: date.slice(5), count }));
});

const gamesPerUser = computed(() => {
    const map = {};
    allGames.value.forEach(g => {
        const uid = g.user_id;
        map[uid] = (map[uid] || 0) + 1;
    });
    return users.value
        .map(u => ({ name: u.name, count: map[u.id] || 0 }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 8);
});

const analyzeRate = computed(() => {
    if (!allGames.value.length) return 0;
    return Math.round((analyzedGames.value / allGames.value.length) * 100);
});

// Audit log action labels in Latvian
const actionLabels = {
    'auth.login': '🔑 Pieslēgšanās',
    'game.create': '♟ Partija izveidota',
    'game.delete': '🗑 Partija dzēsta',
    'user.delete_self': '⚠ Konts dzēsts (GDPR)',
};

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
                <h1 class="text-4xl font-black tracking-tight"><span class="text-amber-400">⚙</span> {{ $t('nav.admin') }}</h1>
                <p class="text-zinc-500 text-sm mt-1">Platformas pārvaldības panelis</p>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else>
                <!-- Tabs -->
                <div class="flex items-center gap-1 mb-8 border-b border-white/5 pb-1 overflow-x-auto">
                    <button v-for="tab in ['overview', 'analytics', 'users', 'games', 'audit']" :key="tab"
                        @click="activeTab = tab"
                        :class="['px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all shrink-0',
                            activeTab === tab ? 'text-amber-400 bg-amber-500/10 border-b-2 border-amber-400' : 'text-zinc-500 hover:text-zinc-300']">
                        {{ { overview: '◈ Pārskats', analytics: '📊 Analītika', users: '◉ Lietotāji', games: '♟ Partijas', audit: '📋 Audits' }[tab] }}
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

                <!-- Analytics tab -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                    <!-- KPI row -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Analīžu rādītājs</p>
                            <p class="text-3xl font-black text-amber-400 mt-1">{{ analyzeRate }}%</p>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ analyzedGames }} no {{ totalGames }} partijām</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Partijas / lietotājs</p>
                            <p class="text-3xl font-black text-emerald-400 mt-1">{{ totalUsers ? (totalGames / totalUsers).toFixed(1) : 0 }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">vidēji</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Atklātņu daudzveidība</p>
                            <p class="text-3xl font-black text-blue-400 mt-1">{{ topOpenings.length }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">unikālas atklātnes</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">Audita ieraksti</p>
                            <p class="text-3xl font-black text-purple-400 mt-1">{{ auditLogs.length }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">pēdējie 100</p>
                        </div>
                    </div>

                    <!-- Games per day bar chart (CSS-only) -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-5">Partijas pa dienām (pēdējās 14 dienas)</h3>
                        <div v-if="gamesPerDay.length > 0" class="flex items-end gap-1.5 h-32">
                            <div v-for="d in gamesPerDay" :key="d.date"
                                class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-bold text-amber-400">{{ d.count }}</span>
                                <div class="w-full bg-gradient-to-t from-amber-500 to-amber-400 rounded-t transition-all duration-500"
                                    :style="{ height: Math.max(4, (d.count / Math.max(...gamesPerDay.map(x => x.count))) * 100) + '%' }">
                                </div>
                                <span class="text-[9px] text-zinc-600 font-mono">{{ d.date }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-zinc-600 italic">Nav datu par šo periodu</p>
                    </div>

                    <!-- Games per user -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">Aktīvākie spēlētāji</h3>
                        <div v-if="gamesPerUser.length > 0" class="space-y-2">
                            <div v-for="u in gamesPerUser" :key="u.name"
                                class="flex items-center gap-3 text-sm">
                                <span class="text-zinc-300 font-bold w-28 truncate">{{ u.name }}</span>
                                <div class="flex-1 bg-zinc-800 rounded-full h-2 overflow-hidden">
                                    <div class="h-full bg-emerald-400 rounded-full transition-all duration-500"
                                        :style="{ width: Math.max(4, (u.count / Math.max(...gamesPerUser.map(x => x.count))) * 100) + '%' }">
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-zinc-400 w-8 text-right">{{ u.count }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-zinc-600 italic">Nav datu</p>
                    </div>
                </div>

                <!-- Audit log tab -->
                <div v-if="activeTab === 'audit'">
                    <div v-if="auditLogs.length === 0"
                        class="text-center py-16 text-zinc-600 text-sm italic">
                        Audita žurnāls ir tukšs. Darbības tiks reģistrētas automātiski.
                    </div>
                    <div v-else class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                    <th class="px-5 py-3">Laiks</th>
                                    <th class="px-5 py-3">Lietotājs</th>
                                    <th class="px-5 py-3">Darbība</th>
                                    <th class="px-5 py-3">Entitāte</th>
                                    <th class="px-5 py-3 text-right">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-xs text-zinc-500 font-mono whitespace-nowrap">
                                        {{ log.created_at?.replace('T', ' ').slice(0, 19) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-zinc-300 font-bold">
                                        {{ log.user?.name || '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-xs text-zinc-400">
                                        {{ actionLabels[log.action] || log.action }}
                                    </td>
                                    <td class="px-5 py-3 text-xs text-zinc-500 font-mono">
                                        <span v-if="log.entity_type">{{ log.entity_type }}#{{ log.entity_id }}</span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-[10px] text-zinc-600 font-mono">
                                        {{ log.ip_address || '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
