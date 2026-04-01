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

        /**
         * Update name + email. Returns a resolved user or throws a validation error
         * object from Laravel ({ errors: { email: ['...'], name: ['...'] } }).
         */
        async updateProfile({ name, email }) {
            const { data } = await api.put("/user/profile", { name, email });
            this.user = data.user || data;
            return this.user;
        },

        /**
         * Change password. Throws on validation failure (e.g. wrong current password).
         */
        async changePassword({ current_password, password, password_confirmation }) {
            await api.put("/user/password", {
                current_password,
                password,
                password_confirmation,
            });
        },

        async updateSettings(settings) {
            const { data } = await api.put("/user/settings", settings);
            this.user = data.user || data;
            return this.user;
        },

        /**
         * GDPR self-deletion. Sends the password for re-confirmation, then
         * clears local state and forces a redirect to /login.
         */
        async deleteAccount(password) {
            await api.delete("/user/me", { password });
            this.user = null;
            this.isInitialized = false;
            window.location.href = "/login";
        },
    },
});
