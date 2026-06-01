import { ref, computed } from 'vue';

const STORAGE_KEY = 'chess_tutorial_state';

const isActive = ref(false);
const currentStep = ref(0);
const hasCompleted = ref(false);

try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        const state = JSON.parse(stored);
        hasCompleted.value = !!state.completed;
    }
} catch { /* intentionally silenced */ }

const STEPS = [
    { key: 'welcome',    target: null,                  route: null,        position: 'center' },
    { key: 'dashboard',  target: '#main-content',       route: '/',         position: 'center' },
    { key: 'play',       target: '[data-tutorial="play"]',       route: '/play',     position: 'bottom' },
    { key: 'games',      target: '[data-tutorial="games"]',      route: '/games',    position: 'bottom' },
    { key: 'openings',   target: '[data-tutorial="openings"]',   route: '/openings', position: 'bottom' },
    { key: 'lessons',    target: '[data-tutorial="lessons"]',    route: '/lessons',  position: 'bottom' },
    { key: 'training',   target: '[data-tutorial="training"]',   route: '/training', position: 'bottom' },
    { key: 'profile',    target: null,                  route: null,        position: 'center' },
    { key: 'complete',   target: null,                  route: '/',         position: 'center' },
];

export function useTutorial() {
    const totalSteps = STEPS.length;
    const step = computed(() => STEPS[currentStep.value] || null);
    const progress = computed(() => Math.round(((currentStep.value + 1) / totalSteps) * 100));
    const isFirstTime = computed(() => !hasCompleted.value);

    function start() {
        currentStep.value = 0;
        isActive.value = true;
    }

    function next() {
        if (currentStep.value < totalSteps - 1) {
            currentStep.value++;
        } else {
            finish();
        }
    }

    function prev() {
        if (currentStep.value > 0) {
            currentStep.value--;
        }
    }

    function skip() {
        finish();
    }

    function finish() {
        isActive.value = false;
        hasCompleted.value = true;
        currentStep.value = 0;
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ completed: true, completedAt: new Date().toISOString() }));
        } catch { /* intentionally silenced */ }
    }

    function reset() {
        hasCompleted.value = false;
        currentStep.value = 0;
        try { localStorage.removeItem(STORAGE_KEY); } catch { /* intentionally silenced */ }
    }

    return {
        isActive,
        currentStep,
        step,
        totalSteps,
        progress,
        hasCompleted,
        isFirstTime,
        STEPS,
        start,
        next,
        prev,
        skip,
        finish,
        reset,
    };
}
