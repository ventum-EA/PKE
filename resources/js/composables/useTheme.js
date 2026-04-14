import { ref, watch, onMounted } from "vue";

const STORAGE_KEY = "pke-theme";
const VALID_THEMES = ["dark", "light"];

/**
 * Global theme state (singleton). Persists to localStorage and syncs
 * to `document.documentElement.dataset.theme` so CSS custom properties
 * in app.css switch automatically.
 */
const currentTheme = ref("dark");

function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
}

export function useTheme() {
    onMounted(() => {
        // Restore from localStorage on first mount
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && VALID_THEMES.includes(stored)) {
            currentTheme.value = stored;
        } else if (window.matchMedia("(prefers-color-scheme: light)").matches) {
            currentTheme.value = "light";
        }
        applyTheme(currentTheme.value);
    });

    watch(currentTheme, (theme) => {
        applyTheme(theme);
        localStorage.setItem(STORAGE_KEY, theme);
    });

    function toggleTheme() {
        currentTheme.value = currentTheme.value === "dark" ? "light" : "dark";
    }

    return {
        theme: currentTheme,
        toggleTheme,
        isDark: () => currentTheme.value === "dark",
    };
}
