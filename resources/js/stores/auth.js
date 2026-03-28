import { defineStore } from "pinia";
import api from "../services/api";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        user: null,
        isInitialized: false,
    }),
    getters: {
        isLoggedIn: (state) => !!state.user,
        isAdmin: (state) => state.user?.role === "admin",
    },
    actions: {
        async fetchUser() {
            try {
                const { data } = await api.get("/user");
                this.user = data.user || data;
            } catch {
                this.user = null;
            } finally {
                this.isInitialized = true;
            }
        },
        async login(credentials) {
            await api.csrf();
            const { data } = await api.post("/login", credentials);
            this.user = data.user || data;
            this.isInitialized = true;
        },
        async register(userData) {
            await api.csrf();
            const { data } = await api.post("/register", userData);
            this.user = data.user || data;
            this.isInitialized = true;
        },
        async logout() {
            try {
                await api.post("/logout");
            } finally {
                this.user = null;
                this.isInitialized = false;
                window.location.href = "/login";
            }
        },
    },
});
