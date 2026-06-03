import { ref, watch, onMounted } from "vue";
import { useAuthStore } from "../stores/auth";

const STORAGE_KEY = "pke-theme";
const VALID_THEMES = ["dark", "light"];

// Default to dark — matches the HTML attribute set in welcome.blade.php
const currentTheme = ref("dark");

function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
}

// Apply immediately from localStorage (sync, no flash).
// This mirrors the inline <script> in welcome.blade.php.
try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && VALID_THEMES.includes(stored)) {
        currentTheme.value = stored;
    }
} catch { /* private browsing or SSR */ }

export function useTheme() {
    onMounted(() => {
        // Re-apply on mount in case the ref was updated by another composable
        applyTheme(currentTheme.value);
    });

    watch(currentTheme, (theme) => {
        applyTheme(theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch { /* private browsing */ }
        // Sync to user's DB setting if logged in
        const auth = useAuthStore();
        if (auth.user) {
            const wantsDark = theme === "dark";
            if (auth.user.dark_mode !== wantsDark) {
                auth.updateSettings({ dark_mode: wantsDark }).catch(() => {});
            }
        }
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
