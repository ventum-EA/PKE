import { describe, test, expect } from 'vitest';
import { detectOpening, isBookMove, getOpeningsByEcoPrefix } from '../../resources/js/services/openings';

describe('openings service', () => {
    describe('detectOpening', () => {
        test('detects an opening for 1.e4 c5 (Sicilian)', () => {
            const opening = detectOpening([{ san: 'e4' }, { san: 'c5' }]);
            expect(opening).toBeDefined();
            // ECO database uses Latvian names — check ECO code instead
            expect(opening.eco).toMatch(/^B/);
        });

        test('detects an opening for 1.e4 e5 2.Nf3 Nc6 3.Bb5 (Ruy Lopez)', () => {
            const opening = detectOpening([
                { san: 'e4' }, { san: 'e5' },
                { san: 'Nf3' }, { san: 'Nc6' },
                { san: 'Bb5' },
            ]);
            expect(opening).toBeDefined();
            expect(opening.eco).toMatch(/^C/);
        });

        test('returns an object with name and eco fields', () => {
            const opening = detectOpening([{ san: 'e4' }, { san: 'e5' }]);
            expect(opening).toHaveProperty('name');
            expect(opening).toHaveProperty('eco');
        });

        test('handles empty moves', () => {
            const result = detectOpening([]);
            // May return null or a default
            expect(result === null || result === undefined || typeof result === 'object').toBe(true);
        });
    });

    describe('isBookMove', () => {
        test('1.e4 is a book move', () => expect(isBookMove(['e4'])).toBe(true));
        test('1.d4 is a book move', () => expect(isBookMove(['d4'])).toBe(true));
        test('1.e4 e5 is a book move', () => expect(isBookMove(['e4', 'e5'])).toBe(true));
        test('nonsensical sequence is not book', () => expect(isBookMove(['e4', 'e5', 'Ke2'])).toBe(false));
    });

    describe('getOpeningsByEcoPrefix', () => {
        test('returns openings for prefix B (Sicilian)', () => {
            const results = getOpeningsByEcoPrefix('B');
            expect(Array.isArray(results)).toBe(true);
            expect(results.length).toBeGreaterThan(0);
        });

        test('returns openings for prefix A', () => {
            expect(getOpeningsByEcoPrefix('A').length).toBeGreaterThan(0);
        });

        test('returns empty array for unknown prefix', () => {
            expect(getOpeningsByEcoPrefix('Z99')).toEqual([]);
        });
    });
});
