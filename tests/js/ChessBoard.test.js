import { describe, test, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ChessBoard from '../../resources/js/components/ChessBoard.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key) => key })
}));

vi.mock('../../resources/js/composables/useBoardTheme', () => ({
    useBoardTheme: () => ({
        boardColors: { value: { light: '#f0d9b5', dark: '#b58863' } },
        pieceColors: { value: { white: { fill: '#fff' }, black: { fill: '#000' } } }
    })
}));

const startFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

function mountBoard(props = {}) {
    return mount(ChessBoard, {
        props: { fen: startFen, size: 400, ...props }
    });
}

describe('ChessBoard.vue', () => {
    test('renders an SVG element', () => {
        expect(mountBoard().find('svg').exists()).toBe(true);
    });

    test('SVG viewBox matches the requested size', () => {
        const wrapper = mountBoard({ size: 320 });
        const svg = wrapper.find('svg');
        // Vue renders :viewBox as camelCase; check HTML directly
        expect(svg.html()).toContain('0 0 320 320');
    });

    test('renders at least 64 board squares', () => {
        expect(mountBoard().findAll('rect').length).toBeGreaterThanOrEqual(64);
    });

    test('renders with custom FEN without crashing', () => {
        const wrapper = mountBoard({ fen: '8/8/8/4k3/8/8/8/4K2R w K - 0 1' });
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    test('renders when flipped', () => {
        expect(mountBoard({ flipped: true }).find('svg').exists()).toBe(true);
    });

    test('applies highlight squares', () => {
        const wrapper = mountBoard({
            highlightSquares: [{ square: 'e4', color: 'rgba(255,0,0,0.4)' }]
        });
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    test('has role=img and aria-label for accessibility', () => {
        const svg = mountBoard().find('svg');
        expect(svg.attributes('role')).toBe('img');
        expect(svg.attributes('aria-label')).toBeTruthy();
    });

    test('has a <title> element for screen readers', () => {
        expect(mountBoard().find('svg title').exists()).toBe(true);
    });

    test('emits no events on mount', () => {
        expect(mountBoard().emitted()).toEqual({});
    });
});
