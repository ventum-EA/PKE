<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useNotification } from '../composables/useNotification';
import { useSounds } from '../composables/useSounds';
import { useWebSocket } from '../composables/useWebSocket';
import api from '../services/api';

const { t, locale } = useI18n();
const router = useRouter();
const route = useRoute();
const { notify } = useNotification();
const auth = useAuthStore();
const { onMatchFound, onNotification, joinUserChannel, joinPresence, leavePresence, isUserOnline } = useWebSocket();
const { playGameStart, playNotify } = useSounds();

// State
const tab = ref('play'); // play | friends | history
const isLoading = ref(true);

// Queue
const inQueue = ref(false);
const queueCount = ref(0);
const queueTime = ref(0);
const timeControl = ref(600);
let queueTimer = null;

// Invite
const inviteColor = ref('random');
const inviteRated = ref(true);
const inviteToken = ref(null);
const copiedInvite = ref(false);

// Friends
const friends = ref([]);
const incoming = ref([]);
const outgoing = ref([]);
const friendSearch = ref('');
const addingFriend = ref(false);

// History
const history = ref([]);

// Active game check
const activeGame = ref(null);

const TC_OPTIONS = [
    { value: 180,  label: '3 min',  tag: 'Bullet' },
    { value: 300,  label: '5 min',  tag: 'Blitz'  },
    { value: 600,  label: '10 min', tag: 'Rapid'  },
    { value: 900,  label: '15 min', tag: 'Rapid'  },
    { value: 1800, label: '30 min', tag: 'Classic' },
];
const currentTC = computed(() => TC_OPTIONS.find(t => t.value === timeControl.value) || TC_OPTIONS[2]);

const inviteUrl = computed(() => {
    if (!inviteToken.value) return '';
    return `${window.location.origin}/multiplayer/join/${inviteToken.value}`;
});

// Queue
async function joinQueue() {
    try {
        const { data } = await api.post('/multiplayer/queue/join', { time_control: timeControl.value });
        inQueue.value = true;
        queueCount.value = data.queue_count || 0;
        queueTime.value = 0;
        queueTimer = setInterval(() => queueTime.value++, 1000);

        // Try immediate match via a single poll
        try {
            const poll = await api.get('/multiplayer/queue/poll', { params: { time_control: timeControl.value } });
            if (poll.data?.matched && poll.data?.game) {
                stopQueue();
                playGameStart();
                notify(t('mp.match_found'), 'success');
                router.push(`/multiplayer/game/${poll.data.game.id}`);
                return;
            }
        } catch { /* intentionally silenced */ }

        // Otherwise wait for WebSocket match notification
    } catch (err) {
        notify(err?.message || t('mp.queue_error'), 'error');
    }
}

async function leaveQueue() {
    try {
        await api.post('/multiplayer/queue/leave');
    } catch (err) { console.warn("API call silenced:", err) }
    stopQueue();
}

function stopQueue() {
    inQueue.value = false;
    if (queueTimer) { clearInterval(queueTimer); queueTimer = null; }
}

// Invite
async function createInvite() {
    try {
        const { data } = await api.post('/multiplayer/create', {
            color: inviteColor.value,
            time_control: timeControl.value,
            rated: inviteRated.value,
        });
        inviteToken.value = data.invite_token;
        // WebSocket will notify us when opponent joins via user channel
    } catch (err) {
        notify(err?.message || t('mp.create_error'), 'error');
    }
}

function cancelInvite() {
    inviteToken.value = null;
}

async function copyInvite() {
    try {
        await navigator.clipboard.writeText(inviteUrl.value);
        copiedInvite.value = true;
        setTimeout(() => copiedInvite.value = false, 2000);
    } catch {
        notify(t('mp.copy_failed'), 'error');
    }
}

// Friends
async function loadFriends() {
    try {
        const { data } = await api.get('/friends');
        friends.value = data.friends || [];
        incoming.value = data.incoming || [];
        outgoing.value = data.outgoing || [];
    } catch { /* intentionally silenced */ }
}

async function addFriend() {
    if (!friendSearch.value.trim()) return;
    addingFriend.value = true;
    try {
        await api.post('/friends/add', { name: friendSearch.value.trim() });
        friendSearch.value = '';
        notify(t('mp.friend_request_sent'), 'success');
        await loadFriends();
    } catch (err) {
        notify(err?.response?.data?.message || t('mp.friend_error'), 'error');
    } finally {
        addingFriend.value = false;
    }
}

async function acceptFriend(requestId) {
    try {
        await api.post(`/friends/${requestId}/accept`);
        notify(t('mp.friend_accepted'), 'success');
        await loadFriends();
    } catch { /* intentionally silenced */ }
}

async function removeFriend(friendId) {
    try {
        await api.delete(`/friends/${friendId}`);
        await loadFriends();
    } catch { /* intentionally silenced */ }
}

async function challengeFriend(friendId) {
    try {
        const { data } = await api.post('/multiplayer/create', {
            color: 'random',
            time_control: timeControl.value,
            rated: true,
        });
        // Copy the invite link for sharing manually for now
        const url = `${window.location.origin}/multiplayer/join/${data.invite_token}`;
        await navigator.clipboard.writeText(url);
        notify(t('mp.challenge_copied'), 'success');
        inviteToken.value = data.invite_token;
    } catch (err) {
        notify(err?.message || t('mp.create_error'), 'error');
    }
}

// History
async function loadHistory() {
    try {
        const { data } = await api.get('/multiplayer/history');
        history.value = data.games || [];
    } catch { /* intentionally silenced */ }
}

// Check for active game on mount
async function checkActiveGame() {
    try {
        const { data } = await api.get('/multiplayer/history');
        const active = (data.games || []).find(g => g.status === 'active');
        if (active) activeGame.value = active;
    } catch { /* intentionally silenced */ }
}

// Handle join token from URL
async function handleJoinToken() {
    const token = route.params.token;
    if (!token) return;
    try {
        const { data } = await api.post(`/multiplayer/join/${token}`);
        if (data.game) {
            router.replace(`/multiplayer/game/${data.game.id}`);
        }
    } catch (err) {
        notify(err?.response?.data?.message || t('mp.join_error'), 'error');
    }
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function resultLabel(game) {
    if (!game.result) return '';
    const myColor = game.my_color;
    if (game.result === '1/2-1/2') return t('mp.draw');
    const whiteWon = game.result === '1-0';
    return (whiteWon && myColor === 'white') || (!whiteWon && myColor === 'black') ? t('mp.won') : t('mp.lost');
}
function resultType(game) {
    if (!game.result) return 'none';
    const myColor = game.my_color;
    if (game.result === '1/2-1/2') return 'draw';
    const whiteWon = game.result === '1-0';
    return (whiteWon && myColor === 'white') || (!whiteWon && myColor === 'black') ? 'won' : 'lost';
}
function resultColor(game) {
    const rt = resultType(game);
    if (rt === 'won') return 'text-emerald-400';
    if (rt === 'lost') return 'text-red-400';
    return 'text-zinc-400';
}

onMounted(async () => {
    await handleJoinToken();
    await Promise.all([checkActiveGame(), loadFriends(), loadHistory()]);
    isLoading.value = false;

    // Subscribe to WebSocket channels
    if (auth.user?.id) {
        joinUserChannel(auth.user.id);
        joinPresence();

        // Listen for match found (from queue or invite)
        onMatchFound(auth.user.id, (game) => {
            stopQueue();
            playGameStart();
            notify(t('mp.match_found'), 'success');
            router.push(`/multiplayer/game/${game.id}`);
        });

        // Listen for real-time notifications (friend requests, etc.)
        onNotification(auth.user.id, (e) => {
            playNotify();
            notify(e.message, 'info');
            if (e.type === 'friend_request' || e.type === 'friend_accepted') loadFriends();
        });
    }
});

onUnmounted(() => {
    stopQueue();
    leavePresence();
});
</script>

<template>
    <div class="min-h-screen p-3 sm:p-6 lg:p-10 text-white">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6 sm:mb-10">
                <h1 class="text-2xl sm:text-4xl font-black tracking-tight">
                    <span class="text-amber-400">⚔</span> {{ $t('mp.title') }}
                </h1>
                <p class="text-zinc-500 text-xs sm:text-sm mt-1 uppercase tracking-widest font-bold">{{ $t('mp.subtitle') }}</p>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="space-y-4" aria-busy="true">
                <div class="h-14 bg-zinc-900/50 border border-white/5 rounded-xl animate-pulse"></div>
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5 space-y-4">
                    <div class="h-4 w-24 bg-zinc-800/40 rounded animate-pulse"></div>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <div v-for="i in 5" :key="i" class="h-16 bg-zinc-800/30 rounded-xl animate-pulse"></div>
                    </div>
                    <div class="h-14 bg-zinc-800/40 rounded-xl animate-pulse"></div>
                </div>
            </div>

            <template v-else>

            <!-- Active game banner -->
            <router-link v-if="activeGame" :to="`/multiplayer/game/${activeGame.id}`"
                class="block mb-6 p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl hover:bg-amber-500/15 transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-400">{{ $t('mp.active_game') }}</p>
                        <p class="text-[11px] text-zinc-400">
                            vs {{ activeGame.my_color === 'white' ? activeGame.black?.name : activeGame.white?.name }}
                            · {{ activeGame.total_moves }} {{ $t('common.moves') }}
                        </p>
                    </div>
                    <span class="text-xs text-amber-400/60 group-hover:text-amber-400 transition-colors">{{ $t('mp.resume') }} →</span>
                </div>
            </router-link>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-zinc-900/50 border border-white/5 rounded-xl p-1">
                <button v-for="tb in ['play', 'friends', 'history']" :key="tb" @click="tab = tb"
                    :class="['flex-1 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all',
                        tab === tb ? 'bg-amber-500/10 text-amber-400' : 'text-zinc-500 hover:text-zinc-300']">
                    {{ tb === 'play' ? $t('mp.tab_play') : tb === 'friends' ? $t('mp.tab_friends') : $t('mp.tab_history') }}
                    <span v-if="tb === 'friends' && incoming.length" class="ml-1 text-[9px] bg-red-500 text-white px-1.5 py-0.5 rounded-full">{{ incoming.length }}</span>
                </button>
            </div>

            <!-- PLAY TAB -->
            <div v-if="tab === 'play'" class="space-y-6">
                <!-- Time control selector -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('mp.time_control') }}</h3>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <button v-for="tc in TC_OPTIONS" :key="tc.value" @click="timeControl = tc.value"
                            :class="['p-3 rounded-xl border text-center transition-all',
                                timeControl === tc.value
                                    ? 'bg-amber-500/10 border-amber-500/20 text-amber-400'
                                    : 'border-white/5 text-zinc-500 hover:text-zinc-300 hover:border-white/10']">
                            <p class="text-sm font-black">{{ tc.label }}</p>
                            <p class="text-[9px] text-zinc-600 mt-0.5">{{ tc.tag }}</p>
                        </button>
                    </div>
                </div>

                <!-- Quick play (queue) -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('mp.quick_play') }}</h3>
                    <div v-if="!inQueue">
                        <button @click="joinQueue" class="w-full py-4 bg-amber-500 text-black font-black rounded-xl text-sm hover:bg-amber-400 transition-all">
                            ⚡ {{ $t('mp.find_opponent') }}
                        </button>
                    </div>
                    <div v-else class="text-center space-y-3">
                        <div class="flex items-center justify-center gap-3">
                            <div class="w-5 h-5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-sm text-amber-400 font-bold">{{ $t('mp.searching') }}</span>
                        </div>
                        <p class="text-xs text-zinc-500 tabular-nums font-mono">{{ formatTime(queueTime) }}</p>
                        <p class="text-[10px] text-zinc-600">
                            {{ queueCount > 0 ? (queueCount + ' ' + $t('mp.in_queue')) : $t('mp.queue_empty_hint') }}
                        </p>
                        <button @click="leaveQueue" class="px-6 py-2 text-xs font-bold rounded-xl border border-white/10 text-zinc-400 hover:text-red-400 hover:border-red-500/20 transition-all">
                            {{ $t('mp.cancel') }}
                        </button>
                    </div>
                </div>

                <!-- Create invite -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $t('mp.invite_friend') }}</h3>

                    <div v-if="!inviteToken">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
                            <label class="text-xs text-zinc-400 font-bold shrink-0">{{ $t('mp.your_color') }}</label>
                            <div class="flex gap-1.5 flex-wrap">
                                <button v-for="c in ['white','black','random']" :key="c" @click="inviteColor = c"
                                    :class="['px-3 py-1.5 rounded-lg text-xs font-bold border transition-all',
                                        inviteColor === c ? 'bg-amber-500/10 border-amber-500/20 text-amber-400' : 'border-white/5 text-zinc-500']">
                                    {{ c === 'white' ? '♔' : c === 'black' ? '♚' : '🎲' }} {{ $t('mp.color_' + c) }}
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mb-4">
                            <label class="text-xs text-zinc-400 font-bold">{{ $t('mp.rated_game') }}</label>
                            <button @click="inviteRated = !inviteRated"
                                :class="['w-10 h-5 rounded-full transition-all relative', inviteRated ? 'bg-amber-500' : 'bg-zinc-700']">
                                <span :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-all', inviteRated ? 'left-5' : 'left-0.5']"></span>
                            </button>
                        </div>
                        <button @click="createInvite" class="w-full py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl hover:bg-zinc-700 transition-all text-sm">
                            🔗 {{ $t('mp.create_invite') }}
                        </button>
                    </div>

                    <div v-else class="text-center space-y-3">
                        <p class="text-xs text-zinc-400">{{ $t('mp.share_link') }}</p>
                        <div class="flex items-center gap-2">
                            <input :value="inviteUrl" readonly class="flex-1 bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-xs font-mono text-zinc-400 select-all">
                            <button @click="copyInvite" class="px-4 py-2 rounded-lg text-xs font-bold border transition-all"
                                :class="copiedInvite ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'border-white/10 text-zinc-400 hover:text-amber-400'">
                                {{ copiedInvite ? '✓' : '📋' }}
                            </button>
                        </div>
                        <div class="flex items-center justify-center gap-2 text-xs text-zinc-500">
                            <div class="w-3 h-3 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                            {{ $t('mp.waiting_opponent') }}
                        </div>
                        <button @click="cancelInvite" class="text-xs text-zinc-600 hover:text-red-400 transition-colors">{{ $t('mp.cancel') }}</button>
                    </div>
                </div>
            </div>

            <!-- FRIENDS TAB -->
            <div v-else-if="tab === 'friends'" class="space-y-4">
                <!-- Add friend -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-3">{{ $t('mp.add_friend') }}</h3>
                    <div class="flex gap-2">
                        <input v-model="friendSearch" :placeholder="$t('mp.username_placeholder')" @keyup.enter="addFriend"
                            class="flex-1 bg-black/30 border border-white/5 rounded-lg px-3 py-2 text-sm text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/20 transition-colors">
                        <button @click="addFriend" :disabled="addingFriend || !friendSearch.trim()"
                            class="px-4 py-2 bg-amber-500/10 text-amber-400 font-bold text-xs rounded-lg border border-amber-500/20 hover:bg-amber-500/15 disabled:opacity-40 transition-all">
                            {{ addingFriend ? '···' : $t('mp.send') }}
                        </button>
                    </div>
                </div>

                <!-- Incoming requests -->
                <div v-if="incoming.length" class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-white/5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $t('mp.incoming_requests') }} ({{ incoming.length }})</h3>
                    </div>
                    <div class="divide-y divide-white/5">
                        <div v-for="req in incoming" :key="req.id" class="flex items-center gap-3 px-5 py-3">
                            <span class="text-sm font-bold text-zinc-300 flex-1">{{ req.name }}</span>
                            <span class="text-[10px] text-zinc-600 font-mono">{{ req.elo_rating }}</span>
                            <button @click="acceptFriend(req.request_id)" class="px-3.5 py-2 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/15 active:scale-95 transition-all" :aria-label="$t('mp.accept')">✓</button>
                            <button @click="removeFriend(req.id)" class="px-3.5 py-2 text-xs font-bold rounded-lg text-zinc-500 border border-white/5 hover:text-red-400 active:scale-95 transition-all" :aria-label="$t('mp.decline')">✕</button>
                        </div>
                    </div>
                </div>

                <!-- Friends list -->
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-white/5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $t('mp.friends_list') }} ({{ friends.length }})</h3>
                    </div>
                    <div v-if="friends.length" class="divide-y divide-white/5">
                        <div v-for="friend in friends" :key="friend.id" class="flex items-center gap-3 px-5 py-3 hover:bg-white/[0.02] transition-colors">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0" :class="isUserOnline(friend.id) ? 'bg-emerald-400' : 'bg-zinc-700'"></div>
                            <span class="text-sm font-bold text-zinc-300 flex-1">{{ friend.name }}</span>
                            <span class="text-[10px] text-zinc-600 font-mono tabular-nums">{{ friend.elo_rating }}</span>
                            <button @click="challengeFriend(friend.id)" class="px-3.5 py-2 text-xs font-bold rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/15 active:scale-95 transition-all">
                                ⚔ {{ $t('mp.challenge') }}
                            </button>
                            <button @click="removeFriend(friend.id)" class="p-2 text-zinc-700 hover:text-red-400 text-sm transition-colors rounded-lg hover:bg-red-500/5" :aria-label="$t('mp.remove_friend')">✕</button>
                        </div>
                    </div>
                    <div v-else class="px-5 py-10 text-center">
                        <p class="text-2xl mb-2 opacity-30">👥</p>
                        <p class="text-sm text-zinc-600">{{ $t('mp.no_friends') }}</p>
                    </div>
                </div>
            </div>

            <!-- HISTORY TAB -->
            <div v-else-if="tab === 'history'">
                <div class="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-white/5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $t('mp.recent_games') }}</h3>
                    </div>
                    <div v-if="history.length" class="divide-y divide-white/5">
                        <div v-for="game in history" :key="game.id"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-white/[0.02] transition-colors cursor-pointer"
                            @click="game.status === 'active' ? router.push(`/multiplayer/game/${game.id}`) : null">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-zinc-300 truncate">
                                    {{ game.white?.name || '?' }} vs {{ game.black?.name || '?' }}
                                </p>
                                <p class="text-[10px] text-zinc-600 mt-0.5">
                                    {{ game.opening_name || '' }} · {{ game.total_moves }} {{ $t('common.moves') }}
                                    <span v-if="game.rated" class="text-amber-400/40">● {{ $t('mp.rated') }}</span>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p :class="['text-sm font-bold', resultColor(game)]">{{ resultLabel(game) }}</p>
                                <p v-if="game.my_color && (game.my_color === 'white' ? game.white?.elo_change : game.black?.elo_change) != null"
                                   class="text-[10px] font-mono tabular-nums"
                                   :class="(game.my_color === 'white' ? game.white?.elo_change : game.black?.elo_change) >= 0 ? 'text-emerald-400' : 'text-red-400'">
                                    {{ (game.my_color === 'white' ? game.white?.elo_change : game.black?.elo_change) > 0 ? '+' : '' }}{{ game.my_color === 'white' ? game.white?.elo_change : game.black?.elo_change }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-5 py-10 text-center">
                        <p class="text-2xl mb-2 opacity-30">⚔</p>
                        <p class="text-sm text-zinc-600">{{ $t('mp.no_games') }}</p>
                    </div>
                </div>
            </div>

            </template>
        </div>
    </div>
</template>
