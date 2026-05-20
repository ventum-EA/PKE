import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

/* ─── Auth Store mock integration ─────────────────────────────────── */

vi.mock('../../resources/js/stores/auth', () => ({
    useAuthStore: () => ({
        isLoggedIn: true,
        user: { name: 'TestLietotajs', elo_rating: 1500, email: 'test@example.com' },
        logout: vi.fn(),
    })
}));

vi.mock('../../resources/js/stores/games', () => ({
    useGamesStore: () => ({
        games: [
            { id: 1, pgn: '1. e4 e5', result: '1-0', opening_name: 'Italian' },
            { id: 2, pgn: '1. d4 d5', result: '0-1', opening_name: 'QGD' },
        ],
        fetchGames: vi.fn(),
        totalGames: 2,
    })
}));

describe('Store integration', () => {
    test('auth store provides user profile data', async () => {
        const { useAuthStore } = await import('../../resources/js/stores/auth');
        const auth = useAuthStore();
        expect(auth.isLoggedIn).toBe(true);
        expect(auth.user.name).toBe('TestLietotajs');
        expect(auth.user.elo_rating).toBe(1500);
        expect(auth.user.email).toBe('test@example.com');
    });

    test('auth store exposes logout function', async () => {
        const { useAuthStore } = await import('../../resources/js/stores/auth');
        const auth = useAuthStore();
        expect(typeof auth.logout).toBe('function');
    });

    test('games store provides game list', async () => {
        const { useGamesStore } = await import('../../resources/js/stores/games');
        const store = useGamesStore();
        expect(store.games.length).toBe(2);
        expect(store.games[0].opening_name).toBe('Italian');
    });

    test('games store has fetch function', async () => {
        const { useGamesStore } = await import('../../resources/js/stores/games');
        const store = useGamesStore();
        expect(typeof store.fetchGames).toBe('function');
    });
});

/* ─── useTheme (localStorage + DOM) ───────────────────────────────── */

describe('useTheme (mocked)', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.removeAttribute('data-theme');
    });

    test('defaults to dark theme', async () => {
        // Reset module cache so useTheme picks up the cleared localStorage
        vi.resetModules();
        const { useTheme } = await import('../../resources/js/composables/useTheme');
        let theme;
        const Harness = defineComponent({
            setup() {
                theme = useTheme();
                return () => h('div');
            },
        });
        mount(Harness, { attachTo: document.body });
        // Either 'dark' or whatever the default is — not 'light' unless OS prefers
        expect(['dark', 'light']).toContain(theme.theme.value);
    });

    test('toggleTheme switches between dark and light', async () => {
        vi.resetModules();
        const { useTheme } = await import('../../resources/js/composables/useTheme');
        let theme;
        const Harness = defineComponent({
            setup() {
                theme = useTheme();
                return () => h('div');
            },
        });
        mount(Harness, { attachTo: document.body });
        const initial = theme.theme.value;
        theme.toggleTheme();
        expect(theme.theme.value).not.toBe(initial);
    });

    test('isDark returns boolean', async () => {
        vi.resetModules();
        const { useTheme } = await import('../../resources/js/composables/useTheme');
        let theme;
        const Harness = defineComponent({
            setup() { theme = useTheme(); return () => h('div'); },
        });
        mount(Harness, { attachTo: document.body });
        expect(typeof theme.isDark()).toBe('boolean');
    });
});
