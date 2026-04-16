import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { mount } from "@vue/test-utils";
import { defineComponent, h, ref, computed } from "vue";
import { useDebounce } from "@/composables/useDebounce";
import { useFocusTrap } from "@/composables/useFocusTrap";

describe("composables/useDebounce", () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("delays the underlying call until the timeout elapses", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner, 200);

        debounced("a");
        debounced("b");
        debounced("c");

        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(199);
        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(inner).toHaveBeenCalledTimes(1);
        expect(inner).toHaveBeenCalledWith("c");
    });

    it("passes through arguments to the wrapped function", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner, 100);

        debounced("first", 42, { key: "value" });
        vi.advanceTimersByTime(100);

        expect(inner).toHaveBeenCalledWith("first", 42, { key: "value" });
    });

    it("uses default delay of 300ms when not specified", () => {
        const inner = vi.fn();
        const debounced = useDebounce(inner);

        debounced();
        vi.advanceTimersByTime(299);
        expect(inner).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(inner).toHaveBeenCalledTimes(1);
    });
});

describe("composables/useFocusTrap", () => {
    // Host component that renders a modal-like region with 3 focusable buttons
    const Host = defineComponent({
        props: { open: Boolean },
        setup(props) {
            const containerRef = ref(null);
            const active = computed(() => props.open);
            useFocusTrap(containerRef, {
                active,
                onEscape: () => { },
            });
            return () =>
                props.open
                    ? h("div", { ref: containerRef, role: "dialog" }, [
                        h("button", { id: "btn-1" }, "One"),
                        h("button", { id: "btn-2" }, "Two"),
                        h("button", { id: "btn-3" }, "Three"),
                    ])
                    : null;
        },
    });

    it.skip("moves focus to the first focusable element when activated", async () => {
        const wrapper = mount(Host, {
            props: { open: true },
            attachTo: document.body,
        });

        await new Promise((r) => setTimeout(r, 20));
        expect(document.activeElement?.id).toBe("btn-1");
        wrapper.unmount();
    });

    it.skip("restores focus to the previously focused element on deactivate", async () => {
        const outsideButton = document.createElement("button");
        outsideButton.id = "outside";
        document.body.appendChild(outsideButton);
        outsideButton.focus();
        expect(document.activeElement?.id).toBe("outside");

        const wrapper = mount(Host, {
            props: { open: true },
            attachTo: document.body,
        });

        await new Promise((r) => setTimeout(r, 20));
        expect(document.activeElement?.id).toBe("btn-1");

        await wrapper.setProps({ open: false });
        await new Promise((r) => setTimeout(r, 20));
        expect(document.activeElement?.id).toBe("outside");

        outsideButton.remove();
        wrapper.unmount();
    });
});
