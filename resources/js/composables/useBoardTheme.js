/**
 * Board theme composable — manages board colors and piece styles.
 *
 * Themes define the light/dark square colors for the chess board.
 * Piece styles define how pieces are rendered (fill/stroke colors).
 * Preferences are synced with the user profile.
 *
 * Usage:
 *   const { currentTheme, currentPieceStyle, themes, pieceStyles, setTheme, setPieceStyle, boardColors, pieceColors } = useBoardTheme();
 */

import { ref, computed, watch } from 'vue';
import { useAuthStore } from '../stores/auth';

export const BOARD_THEMES = {
    classic: {
        label: 'Classic', label_lv: 'Klasisks',
        light: '#F0D9B5', dark: '#B58863',
        lastMoveLight: 'rgba(155, 199, 0, 0.41)', lastMoveDark: 'rgba(155, 199, 0, 0.41)',
    },
    wood: {
        label: 'Wood', label_lv: 'Koks',
        light: '#E8C99B', dark: '#A67C52',
        lastMoveLight: 'rgba(180, 150, 80, 0.4)', lastMoveDark: 'rgba(180, 150, 80, 0.4)',
    },
    blue: {
        label: 'Blue', label_lv: 'Zils',
        light: '#DEE3E6', dark: '#8CA2AD',
        lastMoveLight: 'rgba(0, 150, 255, 0.25)', lastMoveDark: 'rgba(0, 150, 255, 0.25)',
    },
    green: {
        label: 'Green', label_lv: 'Zaļš',
        light: '#FFFFDD', dark: '#86A666',
        lastMoveLight: 'rgba(155, 199, 0, 0.41)', lastMoveDark: 'rgba(155, 199, 0, 0.41)',
    },
    midnight: {
        label: 'Midnight', label_lv: 'Pusnakts',
        light: '#C8C8C8', dark: '#4D5B6A',
        lastMoveLight: 'rgba(100, 180, 255, 0.3)', lastMoveDark: 'rgba(100, 180, 255, 0.3)',
    },
    dark: {
        label: 'Dark', label_lv: 'Tumšs',
        light: '#4A4A4A', dark: '#2C2C2C',
        lastMoveLight: 'rgba(245, 158, 11, 0.3)', lastMoveDark: 'rgba(245, 158, 11, 0.3)',
    },
};

export const PIECE_STYLES = {
    standard: {
        label: 'Standard', label_lv: 'Standarta',
        white: { fill: '#FFFFFF', stroke: '#333333', strokeWidth: 0.5 },
        black: { fill: '#111111', stroke: '#666666', strokeWidth: 0.5 },
    },
    neo: {
        label: 'Neo', label_lv: 'Neo',
        white: { fill: '#F5F5F5', stroke: '#1a1a1a', strokeWidth: 0.8 },
        black: { fill: '#1a1a1a', stroke: '#999999', strokeWidth: 0.8 },
    },
    high_contrast: {
        label: 'High Contrast', label_lv: 'Augsts kontrasts',
        white: { fill: '#FFFFFF', stroke: '#000000', strokeWidth: 1.0 },
        black: { fill: '#000000', stroke: '#FFFFFF', strokeWidth: 1.0 },
    },
    warm: {
        label: 'Warm', label_lv: 'Silts',
        white: { fill: '#FFF8E7', stroke: '#8B6914', strokeWidth: 0.5 },
        black: { fill: '#5C3310', stroke: '#D4A259', strokeWidth: 0.5 },
    },
};

const currentThemeKey = ref('classic');
const currentPieceKey = ref('standard');

export function useBoardTheme() {
    // Sync with auth store on init
    try {
        const auth = useAuthStore();
        if (auth.user?.board_theme && BOARD_THEMES[auth.user.board_theme]) {
            currentThemeKey.value = auth.user.board_theme;
        }
        if (auth.user?.piece_style && PIECE_STYLES[auth.user.piece_style]) {
            currentPieceKey.value = auth.user.piece_style;
        }
    } catch {}

    const currentTheme = computed(() => BOARD_THEMES[currentThemeKey.value] || BOARD_THEMES.classic);
    const currentPieceStyle = computed(() => PIECE_STYLES[currentPieceKey.value] || PIECE_STYLES.standard);

    const boardColors = computed(() => ({
        light: currentTheme.value.light,
        dark: currentTheme.value.dark,
        lastMoveLight: currentTheme.value.lastMoveLight,
        lastMoveDark: currentTheme.value.lastMoveDark,
    }));

    const pieceColors = computed(() => ({
        white: currentPieceStyle.value.white,
        black: currentPieceStyle.value.black,
    }));

    function setTheme(key) {
        if (BOARD_THEMES[key]) currentThemeKey.value = key;
    }

    function setPieceStyle(key) {
        if (PIECE_STYLES[key]) currentPieceKey.value = key;
    }

    return {
        themes: BOARD_THEMES,
        pieceStyles: PIECE_STYLES,
        themeKey: currentThemeKey,
        pieceKey: currentPieceKey,
        currentTheme,
        currentPieceStyle,
        boardColors,
        pieceColors,
        setTheme,
        setPieceStyle,
    };
}
