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

// Pre-fetch auth state BEFORE mounting the router.
// This prevents the router guard from seeing user=null on F5 refresh
// and redirecting to /login before the session check completes.
const authStore = useAuthStore();
authStore.fetchUser()
    .catch(() => { /* 401 is expected when not logged in */ })
    .finally(() => {
        // Only mount router + app AFTER we know the auth state
        app.use(router);
        setAuthRouter(router);

        // 401 redirect listener — safe to register now that router exists
        window.addEventListener("auth:unauthorized", (event) => {
            const redirect = event?.detail?.redirect;
            const target = redirect && redirect !== "/login"
                ? { path: "/login", query: { redirect } }
                : { path: "/login" };
            if (router.currentRoute.value.path !== "/login") {
                router.push(target);
            }
        });

        app.mount("#app");
    });
