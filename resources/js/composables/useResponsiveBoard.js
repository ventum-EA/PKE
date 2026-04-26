import { ref, onMounted, onUnmounted, computed } from 'vue';

/**
 * Provides a reactive board size that adapts to screen width.
 *
 * @param {object} opts
 * @param {number} opts.maxSize   – largest board size on big screens (default 480)
 * @param {number} opts.minSize   – smallest board size on narrow screens (default 260)
 * @param {number} opts.padding   – horizontal padding to subtract (default 48 = 24px each side)
 * @param {number} opts.preferred – preferred size when viewport allows (default maxSize)
 *
 * Returns { boardSize: Ref<number> }
 */
export function useResponsiveBoard(opts = {}) {
    const {
        maxSize = 480,
        minSize = 260,
        padding = 48,
        preferred,
    } = opts;

    const pref = preferred ?? maxSize;
    const screenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

    function update() {
        screenWidth.value = window.innerWidth;
    }

    onMounted(() => {
        update();
        window.addEventListener('resize', update, { passive: true });
    });
    onUnmounted(() => {
        window.removeEventListener('resize', update);
    });

    const boardSize = computed(() => {
        const available = screenWidth.value - padding;
        return Math.max(minSize, Math.min(pref, available));
    });

    return { boardSize, screenWidth };
}
