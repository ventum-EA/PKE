import { watch, nextTick, onUnmounted } from "vue";

/**
 * useFocusTrap — WCAG-compliant focus management for modal dialogs.
 *
 * When `active` becomes true, this composable:
 *   1. Remembers which element had focus before the modal opened
 *   2. Moves focus to the first focusable element inside `containerRef`
 *   3. Traps Tab/Shift+Tab cycling within the container
 *   4. Calls `onEscape` when the Escape key is pressed
 *   5. Restores focus to the previously focused element when deactivated
 *
 * Usage:
 *   const modalRef = ref(null);
 *   const isOpen = ref(false);
 *   useFocusTrap(modalRef, { active: isOpen, onEscape: () => (isOpen.value = false) });
 *   <div ref="modalRef" role="dialog" aria-modal="true">...</div>
 */

const FOCUSABLE_SELECTOR = [
    "a[href]",
    "area[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "iframe",
    "object",
    "embed",
    "[tabindex]:not([tabindex='-1'])",
    "[contenteditable='true']",
].join(",");

function getFocusable(container) {
    if (!container) return [];
    return Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
        (el) => !el.hasAttribute("aria-hidden") && el.offsetParent !== null
    );
}

export function useFocusTrap(containerRef, { active, onEscape } = {}) {
    let previouslyFocused = null;

    function handleKeydown(e) {
        const container =
            containerRef && "value" in containerRef ? containerRef.value : containerRef;
        if (!container) return;

        if (e.key === "Escape" && typeof onEscape === "function") {
            e.stopPropagation();
            onEscape(e);
            return;
        }

        if (e.key !== "Tab") return;

        const focusable = getFocusable(container);
        if (focusable.length === 0) {
            // Nothing to focus — keep focus on the container itself
            e.preventDefault();
            container.focus?.();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const current = document.activeElement;

        if (e.shiftKey) {
            if (current === first || !container.contains(current)) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (current === last || !container.contains(current)) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    async function activate() {
        previouslyFocused = document.activeElement;
        await nextTick();

        const container =
            containerRef && "value" in containerRef ? containerRef.value : containerRef;
        if (!container) return;

        // Give the container a tabindex so it can receive focus if nothing else can
        if (!container.hasAttribute("tabindex")) {
            container.setAttribute("tabindex", "-1");
        }

        const focusable = getFocusable(container);
        (focusable[0] || container).focus?.();

        document.addEventListener("keydown", handleKeydown, true);
    }

    function deactivate() {
        document.removeEventListener("keydown", handleKeydown, true);
        if (previouslyFocused && typeof previouslyFocused.focus === "function") {
            try {
                previouslyFocused.focus();
            } catch {
                /* element may have unmounted */
            }
        }
        previouslyFocused = null;
    }

    // React to an `active` ref (the typical modal open/close pattern)
    if (active && "value" in active) {
        watch(
            () => active.value,
            (isActive) => {
                if (isActive) activate();
                else deactivate();
            },
            { immediate: true }
        );
    }

    onUnmounted(() => {
        document.removeEventListener("keydown", handleKeydown, true);
    });

    return { activate, deactivate };
}
