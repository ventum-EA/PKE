import { ref } from "vue";

const toasts = ref([]);

let nextId = 0;

export function useNotification() {
    /**
     * Show a toast notification.
     * @param {string} msg      — text to display
     * @param {string} t        — type: 'success' | 'error' | 'info' | 'warning' | 'achievement'
     * @param {number} duration — auto-dismiss in ms (default 4000)
     * @param {object} extra    — optional extra data (e.g. { achievement } for unlock toasts)
     */
    const notify = (msg, t = "success", duration = 4000, extra = {}) => {
        const id = ++nextId;
        const toast = { id, message: msg, type: t, duration, ...extra };
        toasts.value.push(toast);

        // Auto-dismiss
        setTimeout(() => dismiss(id), duration);
    };

    const dismiss = (id) => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    // Legacy compat: pages that read `show` / `message` / `type` directly
    // still work through the toasts array, but these are kept for any edge cases.
    const show = { value: false };
    const message = { value: "" };
    const type = { value: "success" };

    return { toasts, notify, dismiss, show, message, type };
}
