import { createI18n } from "vue-i18n";
import lv from "./locales/lv.json";
import en from "./locales/en.json";

/**
 * Determines the initial locale:
 *   1. localStorage preference (set by the language switcher)
 *   2. Browser navigator.language
 *   3. Fallback to 'lv' (Latvian)
 */
function getInitialLocale() {
    const stored = localStorage.getItem("pke-locale");
    if (stored && ["lv", "en"].includes(stored)) return stored;

    const browser = navigator.language?.toLowerCase() || "";
    if (browser.startsWith("en")) return "en";
    return "lv";
}

const i18n = createI18n({
    legacy: false,          // Composition API mode
    locale: getInitialLocale(),
    fallbackLocale: "lv",
    messages: { lv, en },
});

export default i18n;

/**
 * Helper to change locale at runtime and persist.
 */
export function setLocale(locale) {
    if (!["lv", "en"].includes(locale)) return;
    i18n.global.locale.value = locale;
    localStorage.setItem("pke-locale", locale);
    document.documentElement.setAttribute("lang", locale);
}
