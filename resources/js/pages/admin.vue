<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';
import { useNotification } from '../composables/useNotification';
import { useConfirm } from '../composables/useConfirm';

const { t } = useI18n();
const { notify } = useNotification();
const { confirm } = useConfirm();

const users = ref([]);
const allGames = ref([]);
const auditLogs = ref([]);
const adminStats = ref(null);
const isLoading = ref(true);
const activeTab = ref('overview');
const userSearch = ref('');
const gameSearch = ref('');
const gameFilterResult = ref('');
const gameFilterAnalyzed = ref('');
const auditFilterAction = ref('');
const auditFilterUser = ref('');
const auditSearch = ref('');
const expandedLog = ref(null);

onMounted(async () => {
    try {
        const [usersRes, gamesRes, statsRes] = await Promise.all([
            api.get('/users', { perPage: 200 }),
            api.get('/admin/games', { perPage: 200 }),
            api.get('/admin/stats').catch(() => ({ data: null })),
        ]);
        users.value = (usersRes.data?.users ?? usersRes.data)?.data || [];
        allGames.value = (gamesRes.data?.games ?? gamesRes.data)?.data || [];
        adminStats.value = statsRes.data || null;
    } catch { /* intentionally silenced */ }
    isLoading.value = false;
    try {
        const { data } = await api.get('/audit-logs', { perPage: 100 });
        auditLogs.value = (data?.audit_logs ?? data)?.data || [];
    } catch { /* intentionally silenced */ }
});

const totalUsers = computed(() => adminStats.value?.total_users ?? users.value.length);
const totalGames = computed(() => adminStats.value?.total_games ?? allGames.value.length);
const analyzedGames = computed(() => adminStats.value?.analyzed_games ?? allGames.value.filter(g => g.is_analyzed).length);
const totalAdmins = computed(() => adminStats.value?.total_admins ?? users.value.filter(u => u.role === 'admin').length);
const analyzeRate = computed(() => allGames.value.length ? Math.round((analyzedGames.value / allGames.value.length) * 100) : 0);

const filteredUsers = computed(() => {
    if (!userSearch.value) return users.value;
    const q = userSearch.value.toLowerCase();
    return users.value.filter(u => u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q) || u.role?.toLowerCase().includes(q));
});

const filteredGames = computed(() => {
    let list = allGames.value;
    if (gameFilterResult.value) list = list.filter(g => g.result === gameFilterResult.value);
    if (gameFilterAnalyzed.value) list = list.filter(g => gameFilterAnalyzed.value === 'yes' ? g.is_analyzed : !g.is_analyzed);
    if (gameSearch.value) {
        const q = gameSearch.value.toLowerCase();
        list = list.filter(g => g.white_player?.toLowerCase().includes(q) || g.black_player?.toLowerCase().includes(q) || g.opening_name?.toLowerCase().includes(q));
    }
    return list;
});

const uniqueAuditActions = computed(() => [...new Set(auditLogs.value.map(l => l.action))].sort());
const uniqueAuditUsers = computed(() => [...new Map(auditLogs.value.filter(l => l.user?.name).map(l => [l.user.name, l.user])).values()]);

const filteredAudit = computed(() => {
    let list = auditLogs.value;
    if (auditFilterAction.value) list = list.filter(l => l.action === auditFilterAction.value);
    if (auditFilterUser.value) list = list.filter(l => l.user?.name === auditFilterUser.value);
    if (auditSearch.value) {
        const q = auditSearch.value.toLowerCase();
        list = list.filter(l =>
            l.action?.toLowerCase().includes(q) ||
            l.user?.name?.toLowerCase().includes(q) ||
            l.ip_address?.includes(q) ||
            JSON.stringify(l.meta || {}).toLowerCase().includes(q)
        );
    }
    return list;
});

function formatMeta(meta) {
    if (!meta || typeof meta !== 'object') return null;
    return Object.entries(meta).map(([k, v]) => `${k}: ${v}`);
}

const resultBreakdown = computed(() => {
    const c = { '1-0': 0, '0-1': 0, '1/2-1/2': 0, '*': 0 };
    allGames.value.forEach(g => { c[g.result] = (c[g.result] || 0) + 1; });
    return c;
});

const topOpenings = computed(() => {
    const m = {};
    allGames.value.forEach(g => { if (g.opening_name) m[g.opening_name] = (m[g.opening_name] || 0) + 1; });
    return Object.entries(m).sort((a, b) => b[1] - a[1]).slice(0, 10);
});

const gamesPerDay = computed(() => {
    const m = {};
    allGames.value.forEach(g => { const d = (g.played_at || g.created_at || '').split(/[T ]/)[0]; if (d) m[d] = (m[d] || 0) + 1; });
    return Object.entries(m).sort(([a], [b]) => a.localeCompare(b)).slice(-14).map(([date, count]) => ({ date: date.slice(5), count }));
});

const gamesPerUser = computed(() => {
    const m = {};
    allGames.value.forEach(g => { m[g.user_id] = (m[g.user_id] || 0) + 1; });
    return users.value.map(u => ({ name: u.name, count: m[u.id] || 0 })).sort((a, b) => b.count - a.count).slice(0, 8);
});

function userGameCount(uid) { return allGames.value.filter(g => g.user_id === uid).length; }

const actionLabels = {
    'auth.login': '🔑 Login', 'game.create': '♟ Game created', 'game.delete': '🗑 Game deleted',
    'user.delete_self': '⚠ Account deleted (GDPR)', 'admin.role_change': '👑 Role changed',
    'admin.elo_reset': '📊 ELO reset', 'admin.game_delete': '🗑 Game deleted (admin)',
};

async function toggleRole(user) {
    const newRole = user.role === 'admin' ? 'user' : 'admin';
    const ok = await confirm(t('admin.role_change'), newRole === 'admin' ? t('admin.confirm_promote', { name: user.name }) : t('admin.confirm_demote', { name: user.name }));
    if (!ok) return;
    try {
        const { data } = await api.put(`/admin/user/${user.id}/role`, { role: newRole });
        const u = data?.user ?? data?.payload?.user;
        if (u) { const i = users.value.findIndex(x => x.id === user.id); if (i >= 0) users.value[i] = { ...users.value[i], ...u }; }
        notify(t('admin.role_updated'), 'success');
    } catch (e) { notify(e.response?.data?.message || e.message || t('admin.role_error'), 'error'); }
}

async function resetElo(user) {
    const ok = await confirm(t('admin.reset_elo'), t('admin.confirm_reset_elo', { name: user.name, elo: user.elo_rating }));
    if (!ok) return;
    try {
        const { data } = await api.post(`/admin/user/${user.id}/reset-elo`);
        const u = data?.user ?? data?.payload?.user;
        if (u) { const i = users.value.findIndex(x => x.id === user.id); if (i >= 0) users.value[i] = { ...users.value[i], ...u }; }
        notify(t('admin.elo_reset_done'), 'success');
    } catch (e) { notify(e.response?.data?.message || e.message || t('admin.elo_reset_error'), 'error'); }
}

async function deleteUser(user) {
    const ok = await confirm(t('admin.delete_user'), t('admin.confirm_delete_user', { name: user.name, email: user.email }), 'danger');
    if (!ok) return;
    try {
        await api.delete(`/user/${user.id}`);
        users.value = users.value.filter(u => u.id !== user.id);
        notify(t('admin.user_deleted'), 'success');
    } catch (e) { notify(e.response?.data?.message || t('admin.delete_error'), 'error'); }
}

async function deleteGame(game) {
    const ok = await confirm(t('admin.delete_game'), t('admin.confirm_delete_game', { id: game.id, white: game.white_player, black: game.black_player }), 'danger');
    if (!ok) return;
    try {
        await api.delete(`/admin/game/${game.id}`);
        allGames.value = allGames.value.filter(g => g.id !== game.id);
        notify(t('admin.game_deleted'), 'success');
    } catch (e) { notify(e.response?.data?.message || t('admin.delete_error'), 'error'); }
}
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center text-black font-black text-lg shrink-0">⚙</div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-black tracking-tight">{{ $t('admin.title') }}</h1>
                    <p class="text-zinc-500 text-xs sm:text-sm">{{ $t('admin.subtitle') }}</p>
                </div>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else>
                <div class="flex items-center gap-1 mb-8 border-b border-white/5 pb-1 overflow-x-auto">
                    <button v-for="tab in ['overview', 'users', 'games', 'analytics', 'audit']" :key="tab" @click="activeTab = tab"
                        :class="['px-4 sm:px-5 py-2.5 text-xs sm:text-sm font-bold rounded-t-xl transition-all shrink-0',
                            activeTab === tab ? 'text-amber-400 bg-amber-500/10 border-b-2 border-amber-400' : 'text-zinc-500 hover:text-zinc-300']">
                        {{ $t('admin.tab_' + tab) }}
                    </button>
                </div>

                <!-- OVERVIEW -->
                <div v-if="activeTab === 'overview'">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.stat_users') }}</p>
                            <p class="text-2xl sm:text-3xl font-black text-amber-400 mt-1">{{ totalUsers }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ totalAdmins }} {{ $t('admin.admins') }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.stat_games') }}</p>
                            <p class="text-2xl sm:text-3xl font-black text-emerald-400 mt-1">{{ totalGames }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ adminStats?.recent_games || 0 }} {{ $t('admin.last_30d') }}</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.stat_analyzed') }}</p>
                            <p class="text-2xl sm:text-3xl font-black text-blue-400 mt-1">{{ analyzedGames }}</p>
                            <p class="text-[10px] text-zinc-600 mt-1">{{ analyzeRate }}%</p>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.stat_audit') }}</p>
                            <p class="text-2xl sm:text-3xl font-black text-purple-400 mt-1">{{ auditLogs.length }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('admin.result_breakdown') }}</h3>
                            <div class="space-y-3">
                                <div v-for="(count, result) in resultBreakdown" :key="result" class="flex items-center gap-3">
                                    <span class="text-sm font-mono text-zinc-400 w-16">{{ result }}</span>
                                    <div class="flex-1 bg-zinc-800 rounded-full h-3">
                                        <div class="h-3 rounded-full bg-amber-400" :style="{ width: (totalGames > 0 ? (count / totalGames) * 100 : 0) + '%' }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-zinc-400 w-8 text-right">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('admin.top_openings') }}</h3>
                            <div class="space-y-2">
                                <div v-for="[name, count] in topOpenings" :key="name" class="flex items-center justify-between">
                                    <span class="text-sm text-zinc-300 truncate mr-2">{{ name }}</span>
                                    <span class="text-sm font-bold text-amber-400 shrink-0">{{ count }}</span>
                                </div>
                                <p v-if="!topOpenings.length" class="text-zinc-600 text-sm italic">{{ $t('admin.no_data') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USERS -->
                <div v-if="activeTab === 'users'">
                    <div class="mb-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                        <input v-model="userSearch" type="text" :placeholder="$t('admin.search_users')" :aria-label="$t('admin.search_users')"
                            class="flex-1 px-4 py-2.5 bg-black/30 border border-white/5 rounded-xl text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/30" />
                        <span class="text-xs text-zinc-600 shrink-0">{{ filteredUsers.length }} / {{ users.length }}</span>
                    </div>
                    <div class="sm:hidden space-y-3">
                        <div v-for="user in filteredUsers" :key="user.id" class="bg-zinc-900/50 border border-white/5 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0"
                                        :class="user.role === 'admin' ? 'bg-amber-500/20 text-amber-400' : 'bg-zinc-800 text-zinc-400'">
                                        {{ user.name?.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0"><p class="text-sm font-bold text-zinc-300 truncate">{{ user.name }}</p><p class="text-[10px] text-zinc-600 truncate">{{ user.email }}</p></div>
                                </div>
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase border shrink-0', user.role === 'admin' ? 'border-amber-500/20 text-amber-400 bg-amber-500/10' : 'border-zinc-700 text-zinc-500']">{{ user.role }}</span>
                            </div>
                            <p class="text-xs text-zinc-500 mb-3">ELO {{ user.elo_rating }} · {{ userGameCount(user.id) }} {{ $t('admin.games_count') }}</p>
                            <div class="flex gap-2">
                                <button @click="toggleRole(user)" class="flex-1 px-3 py-2 text-[10px] font-bold rounded-lg border transition-all" :class="user.role === 'admin' ? 'border-zinc-700 text-zinc-400' : 'border-amber-500/20 text-amber-400'">{{ user.role === 'admin' ? $t('admin.demote') : $t('admin.promote') }}</button>
                                <button @click="resetElo(user)" class="px-3 py-2 text-[10px] font-bold rounded-lg border border-blue-500/20 text-blue-400 hover:bg-blue-500/10 active:scale-95 transition-all">↻ ELO</button>
                                <button @click="deleteUser(user)" class="px-3 py-2 text-[10px] font-bold rounded-lg border border-red-500/20 text-red-400 hover:bg-red-500/10 active:scale-95 transition-all">{{ $t('admin.delete') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:block bg-zinc-900/50 border border-white/5 rounded-2xl overflow-x-auto">
                        <table class="w-full text-left">
                            <thead><tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                <th class="px-5 py-3">{{ $t('admin.col_user') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_role') }}</th><th class="px-5 py-3 text-center">ELO</th><th class="px-5 py-3 text-center">{{ $t('admin.col_games') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_joined') }}</th><th class="px-5 py-3 text-right">{{ $t('admin.col_actions') }}</th>
                            </tr></thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0" :class="user.role === 'admin' ? 'bg-amber-500/20 text-amber-400' : 'bg-zinc-800 text-zinc-400'">{{ user.name?.charAt(0).toUpperCase() }}</div><div class="min-w-0"><p class="text-sm font-bold text-zinc-300 truncate">{{ user.name }}</p><p class="text-[10px] text-zinc-600">{{ user.email }}</p></div></div></td>
                                    <td class="px-5 py-3 text-center"><span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase border', user.role === 'admin' ? 'border-amber-500/20 text-amber-400 bg-amber-500/10' : 'border-zinc-700 text-zinc-500']">{{ user.role }}</span></td>
                                    <td class="px-5 py-3 text-center text-sm font-bold text-zinc-400">{{ user.elo_rating }}</td>
                                    <td class="px-5 py-3 text-center text-sm text-zinc-500">{{ userGameCount(user.id) }}</td>
                                    <td class="px-5 py-3 text-center text-xs text-zinc-500">{{ user.created_at?.split(' ')[0] }}</td>
                                    <td class="px-5 py-3 text-right"><div class="flex items-center justify-end gap-2">
                                        <button @click="toggleRole(user)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border transition-all" :class="user.role === 'admin' ? 'border-zinc-700 text-zinc-400 hover:text-white' : 'border-amber-500/20 text-amber-400 hover:bg-amber-500/10'">{{ user.role === 'admin' ? '↓ ' + $t('admin.demote') : '↑ ' + $t('admin.promote') }}</button>
                                        <button @click="resetElo(user)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-blue-500/20 text-blue-400 hover:bg-blue-500/10 transition-all">↻ ELO</button>
                                        <button @click="deleteUser(user)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-red-500/20 text-red-400/60 hover:text-red-400 hover:bg-red-500/10 transition-all">{{ $t('admin.delete') }}</button>
                                    </div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GAMES -->
                <div v-if="activeTab === 'games'">
                    <div class="mb-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center flex-wrap">
                        <input v-model="gameSearch" type="text" :placeholder="$t('admin.search_games')" :aria-label="$t('admin.search_games')" class="flex-1 min-w-[200px] px-4 py-2.5 bg-black/30 border border-white/5 rounded-xl text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/30" />
                        <select v-model="gameFilterResult" class="bg-black/30 border border-white/5 rounded-xl px-3 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/30">
                            <option value="">{{ $t('games.all') }} {{ $t('admin.col_result').toLowerCase() }}</option>
                            <option value="1-0">1-0</option>
                            <option value="0-1">0-1</option>
                            <option value="1/2-1/2">½-½</option>
                        </select>
                        <select v-model="gameFilterAnalyzed" class="bg-black/30 border border-white/5 rounded-xl px-3 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/30">
                            <option value="">{{ $t('games.all') }}</option>
                            <option value="yes">{{ $t('games.analyzed_yes') }}</option>
                            <option value="no">{{ $t('games.analyzed_no') }}</option>
                        </select>
                        <span class="text-xs text-zinc-600 shrink-0">{{ filteredGames.length }} / {{ allGames.length }}</span>
                    </div>
                    <div class="sm:hidden space-y-3">
                        <div v-for="game in filteredGames.slice(0, 50)" :key="game.id" class="bg-zinc-900/50 border border-white/5 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2"><p class="text-sm font-bold text-zinc-300 truncate mr-2">{{ game.white_player }} vs {{ game.black_player }}</p><span class="text-sm font-bold text-zinc-400 shrink-0">{{ game.result }}</span></div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-500 mb-3"><span class="truncate mr-2">{{ game.opening_name || '—' }}</span><span>{{ game.total_moves }} {{ $t('admin.moves') }} · {{ game.is_analyzed ? '✓' : '—' }}</span></div>
                            <button @click="deleteGame(game)" class="w-full px-3 py-2 text-[10px] font-bold rounded-lg border border-red-500/20 text-red-400/60 hover:text-red-400 transition-all">{{ $t('admin.delete_game') }}</button>
                        </div>
                    </div>
                    <div class="hidden sm:block bg-zinc-900/50 border border-white/5 rounded-2xl overflow-x-auto">
                        <table class="w-full text-left">
                            <thead><tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                <th class="px-5 py-3">ID</th><th class="px-5 py-3">{{ $t('admin.col_players') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_opening') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_result') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_moves_count') }}</th><th class="px-5 py-3 text-center">{{ $t('admin.col_analyzed') }}</th><th class="px-5 py-3 text-right">{{ $t('admin.col_actions') }}</th>
                            </tr></thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="game in filteredGames.slice(0, 100)" :key="game.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-xs text-zinc-600 font-mono">{{ game.id }}</td>
                                    <td class="px-5 py-3 text-sm font-bold text-zinc-300">{{ game.white_player }} vs {{ game.black_player }}</td>
                                    <td class="px-5 py-3 text-center text-xs text-zinc-500">{{ game.opening_name || '—' }}</td>
                                    <td class="px-5 py-3 text-center text-sm font-bold text-zinc-400">{{ game.result }}</td>
                                    <td class="px-5 py-3 text-center text-sm text-zinc-500">{{ game.total_moves }}</td>
                                    <td class="px-5 py-3 text-center"><span :class="game.is_analyzed ? 'text-emerald-400' : 'text-zinc-600'" class="text-xs">{{ game.is_analyzed ? '✓' : '—' }}</span></td>
                                    <td class="px-5 py-3 text-right"><button @click="deleteGame(game)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-red-500/20 text-red-400/60 hover:text-red-400 hover:bg-red-500/10 transition-all">{{ $t('admin.delete') }}</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="filteredGames.length === 0 && allGames.length > 0" class="text-center py-10 text-zinc-600 text-sm italic">{{ $t('games.no_matches') }}</p>
                </div>

                <!-- ANALYTICS -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5"><p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.analyze_rate') }}</p><p class="text-2xl sm:text-3xl font-black text-amber-400 mt-1">{{ analyzeRate }}%</p></div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5"><p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.games_per_user') }}</p><p class="text-2xl sm:text-3xl font-black text-emerald-400 mt-1">{{ totalUsers ? (totalGames / totalUsers).toFixed(1) : 0 }}</p></div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5"><p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.unique_openings') }}</p><p class="text-2xl sm:text-3xl font-black text-blue-400 mt-1">{{ topOpenings.length }}</p></div>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5"><p class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ $t('admin.new_users_30d') }}</p><p class="text-2xl sm:text-3xl font-black text-purple-400 mt-1">{{ adminStats?.recent_users || 0 }}</p></div>
                    </div>
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-5">{{ $t('admin.games_per_day') }}</h3>
                        <div v-if="gamesPerDay.length" class="flex items-end gap-1.5 h-32">
                            <div v-for="d in gamesPerDay" :key="d.date" class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-bold text-amber-400">{{ d.count }}</span>
                                <div class="w-full bg-gradient-to-t from-amber-500 to-amber-400 rounded-t" :style="{ height: Math.max(4, (d.count / Math.max(...gamesPerDay.map(x => x.count))) * 100) + '%' }"></div>
                                <span class="text-[9px] text-zinc-600 font-mono">{{ d.date }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-zinc-600 italic">{{ $t('admin.no_data') }}</p>
                    </div>
                    <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-4 sm:p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('admin.active_players') }}</h3>
                        <div v-if="gamesPerUser.length" class="space-y-2">
                            <div v-for="u in gamesPerUser" :key="u.name" class="flex items-center gap-3 text-sm">
                                <span class="text-zinc-300 font-bold w-28 truncate">{{ u.name }}</span>
                                <div class="flex-1 bg-zinc-800 rounded-full h-2 overflow-hidden"><div class="h-full bg-emerald-400 rounded-full" :style="{ width: Math.max(4, (u.count / Math.max(...gamesPerUser.map(x => x.count))) * 100) + '%' }"></div></div>
                                <span class="text-xs font-bold text-zinc-400 w-8 text-right">{{ u.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AUDIT -->
                <div v-if="activeTab === 'audit'">
                    <div v-if="!auditLogs.length" class="text-center py-16 text-zinc-600 text-sm italic">{{ $t('admin.audit_empty') }}</div>
                    <template v-else>
                        <!-- Audit filters -->
                        <div class="mb-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center flex-wrap">
                            <input v-model="auditSearch" type="text" :placeholder="$t('admin.audit_search')" :aria-label="$t('admin.audit_search')"
                                class="flex-1 min-w-[200px] px-4 py-2.5 bg-black/30 border border-white/5 rounded-xl text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/30" />
                            <select v-model="auditFilterAction"
                                class="bg-black/30 border border-white/5 rounded-xl px-3 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/30">
                                <option value="">{{ $t('admin.audit_all_actions') }}</option>
                                <option v-for="act in uniqueAuditActions" :key="act" :value="act">{{ actionLabels[act] || act }}</option>
                            </select>
                            <select v-model="auditFilterUser"
                                class="bg-black/30 border border-white/5 rounded-xl px-3 py-2.5 text-sm text-zinc-400 focus:outline-none focus:border-amber-500/30">
                                <option value="">{{ $t('admin.audit_all_users') }}</option>
                                <option v-for="u in uniqueAuditUsers" :key="u.name" :value="u.name">{{ u.name }}</option>
                            </select>
                            <span class="text-xs text-zinc-600 shrink-0">{{ filteredAudit.length }} / {{ auditLogs.length }}</span>
                        </div>

                        <!-- Mobile audit cards -->
                        <div class="sm:hidden space-y-2">
                            <div v-for="log in filteredAudit.slice(0, 50)" :key="log.id"
                                @click="expandedLog = expandedLog === log.id ? null : log.id"
                                class="bg-zinc-900/50 border border-white/5 rounded-xl p-3 cursor-pointer hover:border-amber-500/10 transition-all">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-zinc-300">{{ log.user?.name || '—' }}</span>
                                    <span class="text-[10px] text-zinc-600 font-mono">{{ log.created_at?.replace('T', ' ').slice(0, 16) }}</span>
                                </div>
                                <p class="text-xs text-zinc-400">{{ actionLabels[log.action] || log.action }}</p>
                                <div v-if="expandedLog === log.id" class="mt-2 pt-2 border-t border-white/5 text-[10px] space-y-1">
                                    <p v-if="log.entity_type" class="text-zinc-500"><span class="text-zinc-600">{{ $t('admin.col_entity') }}:</span> {{ log.entity_type }}#{{ log.entity_id }}</p>
                                    <p class="text-zinc-500"><span class="text-zinc-600">IP:</span> {{ log.ip_address || '—' }}</p>
                                    <div v-if="formatMeta(log.meta)" class="bg-black/20 rounded-lg p-2 mt-1">
                                        <p v-for="line in formatMeta(log.meta)" :key="line" class="text-zinc-500 font-mono">{{ line }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop audit table -->
                        <div class="hidden sm:block bg-zinc-900/50 border border-white/5 rounded-2xl overflow-x-auto">
                            <table class="w-full text-left min-w-[700px]">
                                <thead><tr class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 bg-black/20">
                                    <th class="px-5 py-3">{{ $t('admin.col_time') }}</th>
                                    <th class="px-5 py-3">{{ $t('admin.col_user') }}</th>
                                    <th class="px-5 py-3">{{ $t('admin.col_action') }}</th>
                                    <th class="px-5 py-3">{{ $t('admin.col_entity') }}</th>
                                    <th class="px-5 py-3">{{ $t('admin.col_details') }}</th>
                                    <th class="px-5 py-3 text-right">IP</th>
                                </tr></thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="log in filteredAudit.slice(0, 100)" :key="log.id"
                                        @click="expandedLog = expandedLog === log.id ? null : log.id"
                                        class="hover:bg-white/[0.02] cursor-pointer transition-colors"
                                        :class="expandedLog === log.id ? 'bg-amber-500/[0.03]' : ''">
                                        <td class="px-5 py-3 text-xs text-zinc-500 font-mono whitespace-nowrap">{{ log.created_at?.replace('T', ' ').slice(0, 19) }}</td>
                                        <td class="px-5 py-3 text-sm text-zinc-300 font-bold">{{ log.user?.name || '—' }}</td>
                                        <td class="px-5 py-3 text-xs text-zinc-400">{{ actionLabels[log.action] || log.action }}</td>
                                        <td class="px-5 py-3 text-xs text-zinc-500 font-mono">
                                            <span v-if="log.entity_type">{{ log.entity_type }}#{{ log.entity_id }}</span>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-zinc-600">
                                            <template v-if="formatMeta(log.meta)">
                                                <span v-if="expandedLog !== log.id" class="text-amber-400/50 cursor-pointer">▸ {{ $t('admin.audit_show_details') }}</span>
                                                <div v-else class="space-y-0.5">
                                                    <p v-for="line in formatMeta(log.meta)" :key="line" class="font-mono text-zinc-500">{{ line }}</p>
                                                </div>
                                            </template>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-5 py-3 text-right text-[10px] text-zinc-600 font-mono">{{ log.ip_address || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="filteredAudit.length === 0" class="text-center py-10 text-zinc-600 text-sm italic">{{ $t('admin.audit_no_matches') }}</p>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
