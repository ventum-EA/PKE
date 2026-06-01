/**
 * WebSocket composable — manages Laravel Echo channels.
 *
 * Usage:
 *   const { joinGame, leaveGame, onMove, onGameEnd, onDrawOffer } = useWebSocket();
 *   const { joinUserChannel, onNotification, onMatchFound } = useWebSocket();
 *   const { joinPresence, onlineUsers } = useWebSocket();
 */

import { ref, onUnmounted } from 'vue';

const onlineUsers = ref([]);
let presenceChannel = null;
const activeChannels = new Set();

export function useWebSocket() {
    const channels = [];

    // ── Game channel (private) ────────────────────────
    function joinGame(gameId) {
        const name = `game.${gameId}`;
        if (activeChannels.has(name)) return;
        activeChannels.add(name);
        const ch = window.Echo.private(name);
        channels.push(ch);
        return ch;
    }

    function leaveGame(gameId) {
        const name = `game.${gameId}`;
        activeChannels.delete(name);
        window.Echo.leave(name);
    }

    function onMove(gameId, callback) {
        const ch = joinGame(gameId);
        if (ch) ch.listen('.move.made', (e) => callback(e.game));
    }

    function onGameEnd(gameId, callback) {
        const ch = joinGame(gameId);
        if (ch) ch.listen('.game.ended', (e) => callback(e.game));
    }

    function onDrawOffer(gameId, callback) {
        const ch = joinGame(gameId);
        if (ch) ch.listen('.draw.update', (e) => callback(e));
    }

    // ── User channel (private) ────────────────────────
    function joinUserChannel(userId) {
        const name = `user.${userId}`;
        if (activeChannels.has(name)) return;
        activeChannels.add(name);
        const ch = window.Echo.private(name);
        channels.push(ch);
        return ch;
    }

    function leaveUserChannel(userId) {
        const name = `user.${userId}`;
        activeChannels.delete(name);
        window.Echo.leave(name);
    }

    function onNotification(userId, callback) {
        const ch = joinUserChannel(userId);
        if (ch) ch.listen('.notification', (e) => callback(e));
    }

    function onMatchFound(userId, callback) {
        const ch = joinUserChannel(userId);
        if (ch) ch.listen('.match.found', (e) => callback(e.game));
    }

    // ── Presence channel (online users) ───────────────
    function joinPresence() {
        if (presenceChannel) return;
        presenceChannel = window.Echo.join('online-users')
            .here((users) => { onlineUsers.value = users; })
            .joining((user) => { onlineUsers.value.push(user); })
            .leaving((user) => { onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id); });
    }

    function leavePresence() {
        if (!presenceChannel) return;
        window.Echo.leave('online-users');
        presenceChannel = null;
        onlineUsers.value = [];
    }

    function isUserOnline(userId) {
        return onlineUsers.value.some(u => u.id === userId);
    }

    // ── Cleanup ───────────────────────────────────────
    function cleanup() {
        channels.forEach(ch => {
            try { ch.stopListening(); } catch { /* intentionally silenced */ }
        });
    }

    onUnmounted(cleanup);

    return {
        // Game
        joinGame, leaveGame, onMove, onGameEnd, onDrawOffer,
        // User
        joinUserChannel, leaveUserChannel, onNotification, onMatchFound,
        // Presence
        joinPresence, leavePresence, onlineUsers, isUserOnline,
        // Util
        cleanup,
    };
}
