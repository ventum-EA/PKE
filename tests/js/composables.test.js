import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { mount } from "@vue/test-utils";
import { defineComponent, h, ref, computed } from "vue";
import { useDebounce } from "@/composables/useDebounce";
import { useNotification } from "@/composables/useNotification";
import { useConfirm } from "@/composables/useConfirm";
import { useResponsiveBoard } from "@/composables/useResponsiveBoard";

/* ─── useDebounce ─────────────────────────────────────────────────── */

describe("composables/useDebounce", () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it("delays call until timeout elapses", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner, 200);
        debounced("a"); debounced("b"); debounced("c");
        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(200);
        expect(inner).toHaveBeenCalledTimes(1);
        expect(inner).toHaveBeenCalledWith("c");
    });

    it("passes through all arguments", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner, 100);
        debounced("first", 42, { key: "value" });
        vi.advanceTimersByTime(100);
        expect(inner).toHaveBeenCalledWith("first", 42, { key: "value" });
    });

    it("uses default 300ms delay when not specified", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner);
        debounced();
        vi.advanceTimersByTime(299);
        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(inner).toHaveBeenCalledTimes(1);
    });

    it("resets timer on subsequent calls", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner, 100);
        debounced("a");
        vi.advanceTimersByTime(80);
        debounced("b");
        vi.advanceTimersByTime(80);
        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(20);
        expect(inner).toHaveBeenCalledWith("b");
    });
});

/* ─── useNotification ─────────────────────────────────────────────── */

describe("composables/useNotification", () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it("adds a toast on notify", () => {
        const { toasts, notify } = useNotification();
        const before = toasts.value.length;
        notify("Hello!", "success");
        expect(toasts.value.length).toBe(before + 1);
        const last = toasts.value[toasts.value.length - 1];
        expect(last.message).toBe("Hello!");
        expect(last.type).toBe("success");
    });

    it("auto-dismisses after duration", () => {
        const { toasts, notify } = useNotification();
        const before = toasts.value.length;
        notify("Temp", "info", 2000);
        expect(toasts.value.length).toBe(before + 1);
        vi.advanceTimersByTime(2000);
        expect(toasts.value.length).toBe(before);
    });

    it("manual dismiss removes the toast", () => {
        const { toasts, notify, dismiss } = useNotification();
        notify("Manual", "warning", 99999);
        const id = toasts.value[toasts.value.length - 1].id;
        dismiss(id);
        expect(toasts.value.find(t => t.id === id)).toBeUndefined();
    });

    it("supports multiple concurrent toasts", () => {
        const { toasts, notify } = useNotification();
        const before = toasts.value.length;
        notify("One", "success"); notify("Two", "error"); notify("Three", "info");
        expect(toasts.value.length).toBe(before + 3);
    });

    it("passes extra data through", () => {
        const { toasts, notify } = useNotification();
        notify("Achievement!", "achievement", 4000, { achievement: { name: "First Win" } });
        const last = toasts.value[toasts.value.length - 1];
        expect(last.achievement.name).toBe("First Win");
    });
});

/* ─── useConfirm ──────────────────────────────────────────────────── */

describe("composables/useConfirm", () => {
    it("opens the confirmation dialog", () => {
        const { show, title, message, confirm } = useConfirm();
        confirm("Delete?", "This cannot be undone", "danger");
        expect(show.value).toBe(true);
        expect(title.value).toBe("Delete?");
        expect(message.value).toBe("This cannot be undone");
    });

    it("resolves true on confirm", async () => {
        const { confirm, onConfirm, show } = useConfirm();
        const promise = confirm("Sure?", "Yes?");
        onConfirm();
        expect(await promise).toBe(true);
        expect(show.value).toBe(false);
    });

    it("resolves false on cancel", async () => {
        const { confirm, onCancel, show } = useConfirm();
        const promise = confirm("Sure?", "No?");
        onCancel();
        expect(await promise).toBe(false);
        expect(show.value).toBe(false);
    });
});

/* ─── useResponsiveBoard ──────────────────────────────────────────── */

describe("composables/useResponsiveBoard", () => {
    function harness(opts) {
        let captured;
        const H = defineComponent({
            setup() { captured = useResponsiveBoard(opts); return () => h("div"); },
        });
        mount(H, { attachTo: document.body });
        return captured;
    }

    it("returns boardSize as a computed ref", () => {
        const { boardSize } = harness({ maxSize: 400, padding: 48 });
        expect(boardSize.value).toBeGreaterThan(0);
    });

    it("respects maxSize on wide screens", () => {
        // jsdom default window.innerWidth is 1024
        const { boardSize } = harness({ maxSize: 400, minSize: 200, padding: 48 });
        expect(boardSize.value).toBe(400);
    });

    it("clamps to minSize on tiny screens", () => {
        const orig = window.innerWidth;
        Object.defineProperty(window, 'innerWidth', { value: 200, writable: true });
        const { boardSize } = harness({ maxSize: 400, minSize: 260, padding: 48 });
        expect(boardSize.value).toBe(260);
        Object.defineProperty(window, 'innerWidth', { value: orig, writable: true });
    });
});
