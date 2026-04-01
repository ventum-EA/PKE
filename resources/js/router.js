import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "./stores/auth";

const routes = [
    {
        path: "/",
        component: () => import("./pages/dashboard.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/login",
        component: () => import("./pages/login.vue"),
        meta: { guestOnly: true },
    },
    {
        path: "/register",
        component: () => import("./pages/register.vue"),
        meta: { guestOnly: true },
    },
    {
        path: "/games",
        component: () => import("./pages/games.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/play",
        component: () => import("./pages/play.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/training",
        component: () => import("./pages/training.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/openings",
        component: () => import("./pages/openings.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/lessons",
        component: () => import("./pages/lessons.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/puzzles",
        component: () => import("./pages/puzzles.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/endgame",
        component: () => import("./pages/endgame.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/scenario",
        component: () => import("./pages/scenario.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/profile",
        component: () => import("./pages/profile.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/admin",
        component: () => import("./pages/admin.vue"),
        meta: { requiresAuth: true, adminOnly: true },
    },
    {
        path: "/users",
        redirect: "/admin",
    },
    {
        path: "/logout",
        component: () => import("./pages/logout.vue"),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    // Scroll to top on route change; restore saved position on back/forward.
    // Respects users who prefer reduced motion by using instant scroll.
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
    if (to.meta.requiresAuth && !auth.isLoggedIn) return next("/login");
    if (to.meta.guestOnly && auth.isLoggedIn) return next("/");
    if (to.meta.adminOnly && !auth.isAdmin) return next("/");
    next();
});

export default router;
