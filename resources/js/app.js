import "./bootstrap";
import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import i18n from "./i18n";
import App from "./app.vue";
import { setAuthRouter, useAuthStore } from "./stores/auth";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(i18n);

// Wire the router into the auth store so logout/deleteAccount can
// SPA-navigate to /login (instead of forcing a full page reload).
setAuthRouter(router);

// Listen for 401 events dispatched by the api.js interceptor and
// redirect via Vue Router, preserving where the user was trying to go.
window.addEventListener("auth:unauthorized", (event) => {
    const redirect = event?.detail?.redirect;
    const target = redirect && redirect !== "/login"
        ? { path: "/login", query: { redirect } }
        : { path: "/login" };

    if (router.currentRoute.value.path !== "/login") {
        router.push(target);
    }
});

// Wait for the backend to verify the session BEFORE attaching the router.
// This prevents the race condition where the router guard sees user=null
// (because fetchUser hasn't completed) and redirects to /login on refresh.
const authStore = useAuthStore();
authStore.fetchUser().catch(() => {}).finally(() => {
    app.use(router);
    app.mount("#app");
});
