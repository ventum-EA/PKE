import { defineStore } from "pinia";
import api from "../services/api";

/**
 * Auth store — single source of truth for the current session.
 *
 * Conventions:
 *   - Every endpoint returns `{ user, ... }` after the api.js unwrap. We treat
 *     `user` as the authoritative shape (no more `data.user || data` fallback).
 *   - Logout uses the SPA router (set externally via `setRouter`) so navigation
 *     stays smooth and bundle state is preserved.
 *   - `login` returns `{ requires_2fa: true }` if the backend reports the
 *     account has 2FA enabled and an OTP wasn't provided. The login page
 *     uses this to switch to its 2FA step.
 */

let router = null;

export function setAuthRouter(r) {
    router = r;
}

function goToLogin() {
    if (router) {
        router.push({ path: "/login", query: { redirect: router.currentRoute.value.fullPath } });
    } else {
        // Fallback only used in tests / before router is wired up
        window.location.href = "/login";
    }
}

export const useAuthStore = defineStore("auth", {
    state: () => ({
        user: null,
        isInitialized: false,
    }),

    getters: {
        isLoggedIn: (state) => !!state.user,
        isAdmin: (state) => state.user?.role === "admin",
        currentLocale: (state) => state.user?.locale || "lv",
        eloRating: (state) => state.user?.elo_rating ?? 1200,
    },

    actions: {
        async fetchUser() {
            try {
                const { data } = await api.get("/user");
                // Handle both flat { user } and ApiResponse-wrapped { payload: { user } }
                this.user = data.user ?? data.payload?.user ?? null;
            } catch {
                this.user = null;
            } finally {
                this.isInitialized = true;
            }
            return this.user;
        },

        /**
         * Log in. Returns `{ requires_2fa: true }` if the server tells us
         * the account has 2FA enabled and the OTP is needed next.
         *
         * Flow:
         *   1. POST /login with email + password → session is created
         *   2. If response has `requires_2fa: true`, prompt the user for OTP
         *   3. Call verify2FA(otp) — that calls POST /2fa/verify
         */
        async login({ email, password, otp }) {
            await api.csrf();

            // If we're at step 2 (user is providing OTP), verify and we're done
            if (otp) {
                return this.verify2FA(otp);
            }

            // Step 1: send credentials
            try {
                const { data } = await api.post("/login", { email, password });

                if (data?.requires_2fa) {
                    // Note: session exists at this point but the middleware
                    // will block most API calls until /2fa/verify succeeds.
                    return { requires_2fa: true };
                }

                this.user = data.user || null;
                this.isInitialized = true;
                return { user: this.user };
            } catch (err) {
                throw err;
            }
        },

        /**
         * Submit a 6-digit OTP code to complete the 2FA flow.
         */
        async verify2FA(otp) {
            const { data } = await api.post("/2fa/verify", { code: otp });
            this.user = data.user || (await this.fetchUser());
            this.isInitialized = true;
            return { user: this.user };
        },

        async register(userData) {
            await api.csrf();
            const { data } = await api.post("/register", userData);
            this.user = data.user || null;
            this.isInitialized = true;

            // Verify the session is actually established by fetching user
            // This catches cases where the session cookie wasn't set properly
            if (this.user) {
                try {
                    await this.fetchUser();
                } catch {
                    // Session didn't stick — user will see auth errors
                }
            }

            return this.user;
        },

        async logout() {
            try {
                await api.post("/logout");
            } catch {
                // Even if logout fails server-side, clear local state.
            } finally {
                this.user = null;
                this.isInitialized = false;
                goToLogin();
            }
        },

        async updateProfile({ name, email }) {
            const { data } = await api.put("/user/profile", { name, email });
            this.user = data.user ?? data.payload?.user ?? this.user;
            return this.user;
        },

        async changePassword({ current_password, password, password_confirmation }) {
            await api.put("/user/password", {
                current_password,
                password,
                password_confirmation,
            });
        },

        async updateSettings(settings) {
            const { data } = await api.put("/user/settings", settings);
            this.user = data.user ?? data.payload?.user ?? this.user;
            return this.user;
        },

        /**
         * Begin 2FA setup. Returns { qr_code_svg, recovery_codes, secret }
         * which the profile page renders so the user can scan into their app.
         */
        async setup2FA() {
            const { data } = await api.post("/2fa/setup");
            return data;
        },

        /**
         * Confirm 2FA setup by verifying the user can read codes from their app.
         */
        async confirm2FA(code) {
            const { data } = await api.post("/2fa/confirm", { code });
            this.user = data.user || this.user;
            return this.user;
        },

        /**
         * Disable 2FA on the current account. Requires the user's password
         * as confirmation to prevent session-hijack abuse.
         */
        async disable2FA(password) {
            await api.post("/2fa/disable", { password });
            if (this.user) this.user.two_factor_enabled = false;
        },

        updateElo(newElo) {
            if (this.user) this.user.elo_rating = newElo;
        },

        async deleteAccount(password) {
            await api.delete("/user/me", { password });
            this.user = null;
            this.isInitialized = false;
            goToLogin();
        },
    },
});
