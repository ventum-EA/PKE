import { onMounted, onUnmounted, ref } from "vue";
import { useRouter } from "vue-router";

/**
 * Global keyboard shortcuts, Gmail-style.
 *
 * Shortcuts are ignored when the user is typing in an input, textarea, or
 * contenteditable element — we never want `g` to teleport somebody mid-word.
 *
 * Supported:
 *   ?     — open the shortcuts help modal
 *   g d   — dashboard
 *   g g   — games list
 *   g p   — play vs Stockfish
 *   g t   — training
 *   g e   — endgame
 *   g s   — scenario editor
 *   g u   — puzzles (uzdevumi)
 *   g o   — openings
 *   g r   — profile
 *   Esc   — close modal (if open)
 */
const SEQUENCE_TIMEOUT = 1000;

export function useKeyboardShortcuts() {
    const router = useRouter();
    const helpOpen = ref(false);

    let pendingPrefix = null;
    let pendingTimer = null;

    const isEditableTarget = (target) => {
        if (!target) return false;
        const tag = target.tagName;
        if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") return true;
        if (target.isContentEditable) return true;
        return false;
    };

    const routeMap = {
        d: "/",
        g: "/games",
        p: "/play",
        t: "/training",
        e: "/endgame",
        s: "/scenario",
        u: "/puzzles",
        o: "/openings",
        r: "/profile",
    };

    const clearPending = () => {
        pendingPrefix = null;
        if (pendingTimer) {
            clearTimeout(pendingTimer);
            pendingTimer = null;
        }
    };

    const handleKeydown = (e) => {
        // Don't interfere with typing or modifier-key combos (Ctrl/Cmd)
        if (isEditableTarget(e.target)) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        // Escape closes the help modal
        if (e.key === "Escape" && helpOpen.value) {
            e.preventDefault();
            helpOpen.value = false;
            clearPending();
            return;
        }

        // ? opens the help modal
        if (e.key === "?") {
            e.preventDefault();
            helpOpen.value = true;
            clearPending();
            return;
        }

        const key = e.key.toLowerCase();

        // If a prefix is pending, the next key completes the sequence.
        if (pendingPrefix === "g") {
            const target = routeMap[key];
            clearPending();
            if (target) {
                e.preventDefault();
                router.push(target);
            }
            return;
        }

        // Start a prefix sequence on `g`
        if (key === "g") {
            pendingPrefix = "g";
            pendingTimer = setTimeout(clearPending, SEQUENCE_TIMEOUT);
        }
    };

    onMounted(() => {
        window.addEventListener("keydown", handleKeydown);
    });

    onUnmounted(() => {
        window.removeEventListener("keydown", handleKeydown);
        clearPending();
    });

    return {
        helpOpen,
        openHelp: () => {
            helpOpen.value = true;
        },
        closeHelp: () => {
            helpOpen.value = false;
        },
    };
}
