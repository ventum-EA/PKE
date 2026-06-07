import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "./stores/auth";

const routes = [
    {
        path: "/",
        component: () => import("./pages/dashboard.vue"),
        meta: { requiresAuth: true, title: "Sākums" },
    },

    // Auth flow
    {
        path: "/login",
        component: () => import("./pages/login.vue"),
        meta: { guestOnly: true, title: "Pieslēgties" },
    },
    {
        path: "/register",
        component: () => import("./pages/register.vue"),
        meta: { guestOnly: true, title: "Reģistrēties" },
    },
    {
        path: "/forgot-password",
        component: () => import("./pages/forgot-password.vue"),
        meta: { guestOnly: true, title: "Aizmirsta parole" },
    },
    {
        path: "/reset-password",
        component: () => import("./pages/reset-password.vue"),
        meta: { guestOnly: true, title: "Atjaunot paroli" },
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
        meta: { requiresAuth: true, title: "Manas partijas" },
    },
    {
        path: "/play",
        component: () => import("./pages/play.vue"),
        meta: { requiresAuth: true, title: "Spēlēt" },
    },
    {
        path: "/training",
        component: () => import("./pages/training.vue"),
        meta: { requiresAuth: true, title: "Treniņi" },
    },
    {
        path: "/openings",
        component: () => import("./pages/openings.vue"),
        meta: { requiresAuth: true, title: "Atklātnes" },
    },
    {
        path: "/lessons",
        component: () => import("./pages/lessons.vue"),
        meta: { requiresAuth: true, title: "Mācības" },
    },
    {
        path: "/puzzles",
        component: () => import("./pages/puzzles.vue"),
        meta: { requiresAuth: true, title: "Uzdevumi" },
    },
    {
        path: "/endgame",
        component: () => import("./pages/endgame.vue"),
        meta: { requiresAuth: true, title: "Galotnes" },
    },
    {
        path: "/endgame-trainer",
        component: () => import("./pages/endgame-trainer.vue"),
        meta: { requiresAuth: true, title: "Galotņu treniņš" },
    },
    {
        path: "/scenario",
        component: () => import("./pages/scenario.vue"),
        meta: { requiresAuth: true, title: "Scenāriji" },
    },

    // Public share route (no auth required)
    {
        path: "/shared/:token",
        component: () => import("./pages/shared.vue"),
        meta: { title: "Kopīgota partija" },
        props: true,
    },

    // Learn redirects to lessons (no dedicated learn page)
    { path: "/learn", redirect: "/lessons" },

    // New features
    {
        path: "/daily",
        component: () => import("./pages/daily.vue"),
        meta: { requiresAuth: true, title: "Dienas uzdevums" },
    },
    {
        path: "/achievements",
        component: () => import("./pages/achievements.vue"),
        meta: { requiresAuth: true, title: "Sasniegumi" },
    },
    {
        path: "/multiplayer",
        component: () => import("./pages/multiplayer.vue"),
        meta: { requiresAuth: true, title: "Daudzspēlētāju" },
    },
    {
        path: "/multiplayer/join/:token",
        component: () => import("./pages/multiplayer.vue"),
        meta: { requiresAuth: true, title: "Pievienoties spēlei" },
        props: true,
    },
    {
        path: "/multiplayer/game/:id",
        component: () => import("./pages/multiplayer-game.vue"),
        meta: { requiresAuth: true, title: "Tiešsaistes spēle" },
        props: true,
    },

    // Profile + admin
    {
        path: "/profile",
        component: () => import("./pages/profile.vue"),
        meta: { requiresAuth: true, title: "Profils" },
    },
    {
        path: "/admin",
        component: () => import("./pages/admin.vue"),
        meta: { requiresAuth: true, adminOnly: true, title: "Administrēšana" },
    },
    { path: "/users", redirect: "/admin" },

    // 404 fallback
    {
        path: "/:pathMatch(.*)*",
        component: () => import("./pages/not-found.vue"),
        meta: { title: "Lapa nav atrasta" },
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

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();
    if (!auth.isInitialized) await auth.fetchUser();

    // Auth-required route, not logged in → send to login with redirect param
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
        return next({ path: "/login", query: { redirect: to.fullPath } });
    }

    // Guest-only route (login, register, password reset), already logged in
    // → respect the original ?redirect param if any
    if (to.meta.guestOnly && auth.isLoggedIn) {
        const redirect = typeof to.query.redirect === "string" ? to.query.redirect : "/";
        return next(redirect);
    }

    // Admin-only routes
    if (to.meta.adminOnly && !auth.isAdmin) {
        return next("/");
    }

    next();
});

router.afterEach((to) => {
    if (to.meta.title) {
        document.title = `${to.meta.title} · Šaha mājaslapa`;
    }
});

export default router;
