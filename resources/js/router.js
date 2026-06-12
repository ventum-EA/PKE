import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "./stores/auth";
import i18n from "./i18n";

const routes = [
    {
        path: "/",
        component: () => import("./pages/dashboard.vue"),
        meta: { requiresAuth: true, titleKey: "titles.dashboard" },
    },

    // Auth flow
    {
        path: "/login",
        component: () => import("./pages/login.vue"),
        meta: { guestOnly: true, titleKey: "titles.login" },
    },
    {
        path: "/register",
        component: () => import("./pages/register.vue"),
        meta: { guestOnly: true, titleKey: "titles.register" },
    },
    {
        path: "/forgot-password",
        component: () => import("./pages/forgot-password.vue"),
        meta: { guestOnly: true, titleKey: "titles.forgot_password" },
    },
    {
        path: "/reset-password",
        component: () => import("./pages/reset-password.vue"),
        meta: { guestOnly: true, titleKey: "titles.reset_password" },
    },
    {
        path: "/logout",
        component: () => import("./pages/logout.vue"),
        meta: { requiresAuth: true },
    },

    // Core features
    {
        path: "/games",
        component: () => import("./pages/games.vue"),
        meta: { requiresAuth: true, titleKey: "titles.games" },
    },
    {
        path: "/play",
        component: () => import("./pages/play.vue"),
        meta: { requiresAuth: true, titleKey: "titles.play" },
    },
    {
        path: "/training",
        component: () => import("./pages/training.vue"),
        meta: { requiresAuth: true, titleKey: "titles.training" },
    },
    {
        path: "/openings",
        component: () => import("./pages/openings.vue"),
        meta: { requiresAuth: true, titleKey: "titles.openings" },
    },
    {
        path: "/lessons",
        component: () => import("./pages/lessons.vue"),
        meta: { requiresAuth: true, titleKey: "titles.lessons" },
    },
    {
        path: "/puzzles",
        component: () => import("./pages/puzzles.vue"),
        meta: { requiresAuth: true, titleKey: "titles.puzzles" },
    },
    {
        path: "/endgame",
        component: () => import("./pages/endgame.vue"),
        meta: { requiresAuth: true, titleKey: "titles.endgame" },
    },
    {
        path: "/endgame-trainer",
        component: () => import("./pages/endgame-trainer.vue"),
        meta: { requiresAuth: true, titleKey: "titles.endgame_trainer" },
    },
    {
        path: "/scenario",
        component: () => import("./pages/scenario.vue"),
        meta: { requiresAuth: true, titleKey: "titles.scenario" },
    },

    // Public share route (no auth required)
    {
        path: "/shared/:token",
        component: () => import("./pages/shared.vue"),
        meta: { titleKey: "titles.shared" },
        props: true,
    },

    // Learn redirects to lessons (no dedicated learn page)
    { path: "/learn", redirect: "/lessons" },

    // New features
    {
        path: "/daily",
        component: () => import("./pages/daily.vue"),
        meta: { requiresAuth: true, titleKey: "titles.daily" },
    },
    {
        path: "/achievements",
        component: () => import("./pages/achievements.vue"),
        meta: { requiresAuth: true, titleKey: "titles.achievements" },
    },
    {
        path: "/multiplayer",
        component: () => import("./pages/multiplayer.vue"),
        meta: { requiresAuth: true, titleKey: "titles.multiplayer" },
    },
    {
        path: "/multiplayer/join/:token",
        component: () => import("./pages/multiplayer.vue"),
        meta: { requiresAuth: true, titleKey: "titles.join_game" },
        props: true,
    },
    {
        path: "/multiplayer/game/:id",
        component: () => import("./pages/multiplayer-game.vue"),
        meta: { requiresAuth: true, titleKey: "titles.multiplayer" },
        props: true,
    },

    // Profile + admin
    {
        path: "/profile",
        component: () => import("./pages/profile.vue"),
        meta: { requiresAuth: true, titleKey: "titles.profile" },
    },
    {
        path: "/admin",
        component: () => import("./pages/admin.vue"),
        meta: { requiresAuth: true, adminOnly: true, titleKey: "titles.admin" },
    },
    { path: "/users", redirect: "/admin" },

    // 404 fallback
    {
        path: "/:pathMatch(.*)*",
        component: () => import("./pages/not-found.vue"),
        meta: { titleKey: "titles.not_found" },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.hash) return { el: to.hash, behavior: "smooth" };
        const reduceMotion =
            typeof window !== "undefined" &&
            window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;
        return { top: 0, behavior: reduceMotion ? "auto" : "smooth" };
    },
});

/**
 * Resolves the page title from the current route's i18n key.
 */
export function updateDocumentTitle() {
    const route = router.currentRoute.value;
    if (route?.meta?.titleKey) {
        const t = i18n.global.t;
        const title = t(route.meta.titleKey);
        const siteName = t('site_name');
        document.title = `${title} · ${siteName}`;
    }
}

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();
    if (!auth.isInitialized) await auth.fetchUser();

    // Auth-required route, not logged in → send to login with redirect param
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
        return next({ path: "/login", query: { redirect: to.fullPath } });
    }

    // Guest-only route (login, register, password reset), already logged in
    // → respect the original ?redirect param if any.
    // Sanitize: only allow same-app paths, and never redirect into /logout
    // or another auth page (would cause an immediate logout / loop).
    if (to.meta.guestOnly && auth.isLoggedIn) {
        let redirect = typeof to.query.redirect === "string" ? to.query.redirect : "/";
        const unsafe = ["/logout", "/login", "/register", "/forgot-password", "/reset-password"];
        if (!redirect.startsWith("/") || redirect.startsWith("//") || unsafe.some((p) => redirect.startsWith(p))) {
            redirect = "/";
        }
        return next(redirect);
    }

    // Admin-only routes
    if (to.meta.adminOnly && !auth.isAdmin) {
        return next("/");
    }

    next();
});

router.afterEach(() => {
    updateDocumentTitle();
});

export default router;
