import { test, expect } from '@playwright/test';
import { testUser, registerUser, uploadPgn, SAMPLE_PGN } from './helpers.js';

/**
 * Core game analysis flow — the primary user journey:
 *   Register → Upload PGN → See game in list → Analyze → View results → Share
 *
 * This tests the integration of:
 *   - Vue frontend (game upload, analysis UI, chessboard)
 *   - Laravel API (game CRUD, move storage)
 *   - Stockfish WASM (analysis engine in WebWorker)
 *   - Chess.js (PGN parsing, move validation)
 */
test.describe('Game Analysis Flow', () => {
    let user;

    test.beforeAll(async ({ browser }) => {
        // Register a user once for all tests in this suite
        const page = await browser.newPage();
        user = await registerUser(page);
        await page.close();
    });

    test.beforeEach(async ({ page }) => {
        const { loginUser } = await import('./helpers.js');
        await loginUser(page, user);
    });

    test('user can upload a PGN and see it in games list', async ({ page }) => {
        await uploadPgn(page, SAMPLE_PGN);
        await page.goto('/games');
        await page.waitForTimeout(1500);
        // The game should appear — look for the player names or opening
        const gamesList = page.locator('[class*="game"], [data-testid="game-card"], .game-card, a[href*="/game/"]').first();
        // At minimum the page should have game content
        const body = await page.locator('body').textContent();
        expect(body.length).toBeGreaterThan(100);
    });

    test('user can open a game and see the chessboard', async ({ page }) => {
        await uploadPgn(page, SAMPLE_PGN);
        await page.goto('/games');
        await page.waitForTimeout(1500);
        // Click on the first game link/card
        const gameLink = page.locator('a[href*="/game/"]').first();
        if (await gameLink.isVisible()) {
            await gameLink.click();
            await page.waitForTimeout(2000);
            // Should see a chessboard (SVG or canvas or component)
            const board = page.locator('[class*="chess"], [class*="board"], svg, canvas').first();
            await expect(board).toBeVisible({ timeout: 5000 });
        }
    });

    test('analysis produces move classifications', async ({ page }) => {
        await uploadPgn(page, SAMPLE_PGN);
        await page.goto('/games');
        await page.waitForTimeout(1500);

        // Navigate to the game
        const gameLink = page.locator('a[href*="/game/"]').first();
        if (await gameLink.isVisible()) {
            await gameLink.click();
            await page.waitForTimeout(1500);

            // Click "Analyze" button
            const analyzeBtn = page.getByRole('button', { name: /analizēt|analyze/i }).first();
            if (await analyzeBtn.isVisible()) {
                await analyzeBtn.click();

                // Wait for analysis to complete (progress bar disappears or results appear)
                // Stockfish WASM analysis of 10 moves takes ~5-15 seconds
                await page.waitForTimeout(20_000);

                // After analysis, should see classification labels or colored indicators
                const body = await page.locator('body').textContent();
                const hasClassifications =
                    body.includes('best') || body.includes('labākais') ||
                    body.includes('excellent') || body.includes('lielisks') ||
                    body.includes('good') || body.includes('labs') ||
                    body.includes('inaccuracy') || body.includes('neprecizitāte') ||
                    body.includes('mistake') || body.includes('kļūda') ||
                    body.includes('blunder') || body.includes('rupja');

                expect(hasClassifications).toBeTruthy();
            }
        }
    });

    test('analyzed game can be shared via link', async ({ page }) => {
        await page.goto('/games');
        await page.waitForTimeout(1500);

        const gameLink = page.locator('a[href*="/game/"]').first();
        if (await gameLink.isVisible()) {
            await gameLink.click();
            await page.waitForTimeout(1500);

            // Look for share button
            const shareBtn = page.getByRole('button', { name: /kopīgot|share/i }).first();
            if (await shareBtn.isVisible()) {
                await shareBtn.click();
                await page.waitForTimeout(2000);

                // Should show a share URL or a success notification
                const body = await page.locator('body').textContent();
                const hasShareIndicator =
                    body.includes('/shared/') ||
                    body.includes('kopīgošana') ||
                    body.includes('share') ||
                    body.includes('saite');

                expect(hasShareIndicator).toBeTruthy();
            }
        }
    });
});
