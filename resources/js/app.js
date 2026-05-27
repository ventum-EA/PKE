import "./bootstrap";
import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import i18n from "./i18n";
import App from "./app.vue";
import { setAuthRouter } from "./stores/auth";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
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

app.mount("#app");
