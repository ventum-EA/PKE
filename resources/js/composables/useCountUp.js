import { ref, watch } from "vue";

/**
 * Animates a numeric value from its current display state to a target.
 * Uses requestAnimationFrame with an easeOutCubic curve.
 *
 * Usage:
 *   const { display } = useCountUp(() => props.value);
 *   // {{ display }} in the template
 *
 * Handles non-numeric values gracefully — strings like "42%" are split into
 * a number + suffix and the number is animated, suffix is re-appended.
 */
export function useCountUp(source, { duration = 800 } = {}) {
    const display = ref(0);
    let rafId = null;

    function parse(raw) {
        if (typeof raw === "number") return { num: raw, suffix: "" };
        if (typeof raw !== "string") return { num: 0, suffix: "" };
        const match = raw.match(/^(-?[\d.]+)(.*)$/);
        if (!match) return { num: 0, suffix: raw };
        return { num: parseFloat(match[1]) || 0, suffix: match[2] };
    }

    function animate(target) {
        if (rafId) cancelAnimationFrame(rafId);
        const { num: endNum, suffix } = parse(target);
        const { num: startNum } = parse(display.value);
        const isFloat = String(endNum).includes(".");
        const start = performance.now();

        function step(now) {
            const elapsed = now - start;
            const t = Math.min(elapsed / duration, 1);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - t, 3);
            const current = startNum + (endNum - startNum) * eased;
            display.value = (isFloat ? current.toFixed(1) : Math.round(current)) + suffix;
            if (t < 1) {
                rafId = requestAnimationFrame(step);
            } else {
                display.value = (isFloat ? endNum.toFixed(1) : endNum) + suffix;
                rafId = null;
            }
        }

        rafId = requestAnimationFrame(step);
    }

    if (typeof source === "function") {
        watch(source, (value) => animate(value), { immediate: true });
    } else {
        animate(source);
    }

    return { display };
}
