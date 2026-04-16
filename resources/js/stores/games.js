import { defineStore } from "pinia";
import api from "../services/api";

export const useGamesStore = defineStore("games", {
    state: () => ({
        games: [],
        currentGame: null,
        currentMoves: [],
        stats: null,
        statsError: null,
        pagination: { current_page: 1, last_page: 1, total: 0 },
        isLoading: false,
        isAnalyzing: false,
    }),
    actions: {
        async fetchGames(params = {}, force = false) {
            if (!force && this.games.length > 0 && !params.filter) return;
            this.isLoading = true;
            try {
                const { data } = await api.get("/games", params);
                const payload = data.payload?.games || data.games || data;
                this.games = payload.data || payload || [];
                if (Array.isArray(this.games) && !payload.data) {
                    // Plain array response, no pagination wrapper
                    this.pagination = { current_page: 1, last_page: 1, total: this.games.length };
                } else {
                    this.pagination = {
                        current_page: payload.current_page || payload.meta?.current_page || 1,
                        last_page: payload.last_page || payload.meta?.last_page || 1,
                        total: payload.total ?? payload.meta?.total ?? this.games.length,
                    };
                }
            } finally {
                this.isLoading = false;
            }
        },

        async fetchGame(id) {
            this.isLoading = true;
            try {
                const { data } = await api.get(`/game/${id}`);
                this.currentGame = data.game || data;
                return this.currentGame;
            } finally {
                this.isLoading = false;
            }
        },

        async createGame(gameData) {
            const { data } = await api.post("/game/create", gameData);
            return data.game || data;
        },

        async deleteGame(id) {
            await api.delete(`/game/${id}`);
            this.games = this.games.filter((g) => g.id !== id);
        },

        async analyzeGame(id, depth = 15) {
            this.isAnalyzing = true;
            try {
                const { data } = await api.post(`/game/${id}/analyze`, { depth });
                return data;
            } finally {
                this.isAnalyzing = false;
            }
        },

        async fetchMoves(id) {
            const { data } = await api.get(`/game/${id}/moves`);
            this.currentMoves = data.moves || [];
            return this.currentMoves;
        },

        async saveMoves(id, moves) {
            const { data } = await api.post(`/game/${id}/moves`, { moves });
            return data;
        },

        async shareGame(id) {
            const { data } = await api.post(`/game/${id}/share`);
            return data.payload?.share_url || data.share_url || data;
        },

        /**
         * Download a game's PGN as a .pgn file via the browser's save dialog.
         */
        async downloadGame(id) {
            await api.download(`/game/${id}/download`, `game-${id}.pgn`);
        },

        async fetchStats(force = false) {
            if (!force && this.stats) return;
            this.statsError = null;
            try {
                const { data } = await api.get("/games/stats");
                this.stats = data;
            } catch (err) {
                console.error(err);
                this.statsError = err?.message || "Neizdevās ielādēt statistiku";
            }
        },
    },
});
