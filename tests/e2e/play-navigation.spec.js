import { test, expect } from '@playwright/test';
import { testUser, registerUser, loginUser } from './helpers.js';

test.describe('Play & Navigation', () => {
    let user;

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        user = await registerUser(page);
        await page.close();
    });

    test.beforeEach(async ({ page }) => {
        await loginUser(page, user);
    });

    test('play page loads with difficulty selector', async ({ page }) => {
        await page.goto('/play');
        await page.waitForTimeout(2000);
        // Should see difficulty control (range input or buttons)
        const body = await page.locator('body').textContent();
        const hasDifficulty =
            body.includes('grūtīb') || body.includes('difficulty') ||
            body.includes('ELO') || body.includes('līmen');
        expect(hasDifficulty).toBeTruthy();
    });

    test('can start a game against Stockfish', async ({ page }) => {
        await page.goto('/play');
        await page.waitForTimeout(2000);

        // Look for a "Start" / "Sākt" button or the board directly
        const startBtn = page.getByRole('button', { name: /sākt|start|play|spēlēt/i }).first();
        if (await startBtn.isVisible()) {
            await startBtn.click();
            await page.waitForTimeout(3000);
        }

        // A chessboard should be visible
        const board = page.locator('[class*="chess"], [class*="board"], svg').first();
        await expect(board).toBeVisible({ timeout: 5000 });
    });

    test('dashboard shows user statistics', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForTimeout(2000);
        const body = await page.locator('body').textContent();
        // Should contain stats-related content
        const hasStats =
            body.includes('partij') || body.includes('game') ||
            body.includes('ELO') || body.includes('1200') ||
            body.includes('uzvaru') || body.includes('win');
        expect(hasStats).toBeTruthy();
    });

    test('openings page loads opening list', async ({ page }) => {
        await page.goto('/openings');
        await page.waitForTimeout(2000);
        const body = await page.locator('body').textContent();
        // Should contain ECO codes or opening names
        const hasOpenings =
            body.includes('ECO') || body.includes('Sicīl') ||
            body.includes('Italian') || body.includes('atklātn') ||
            body.includes('opening') || body.includes('A00');
        expect(hasOpenings).toBeTruthy();
    });

    test('lessons page loads lesson categories', async ({ page }) => {
        await page.goto('/lessons');
        await page.waitForTimeout(2000);
        const body = await page.locator('body').textContent();
        const hasLessons =
            body.includes('nodarbīb') || body.includes('lesson') ||
            body.includes('taktik') || body.includes('tactic');
        expect(hasLessons).toBeTruthy();
    });

    test('profile page loads and shows user info', async ({ page }) => {
        await page.goto('/profile');
        await page.waitForTimeout(2000);
        await expect(page.locator('body')).toContainText(user.name);
        await expect(page.locator('body')).toContainText('ELO');
    });

    test('keyboard navigation works on main sections', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForTimeout(1000);
        // Tab through the page — should be able to reach interactive elements
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');
        // After tabbing, some element should have focus
        const focused = await page.evaluate(() => document.activeElement?.tagName);
        expect(['A', 'BUTTON', 'INPUT', 'SELECT']).toContain(focused);
    });
});
