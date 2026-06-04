// Run: npx playwright test take-screenshots.spec.js
// Screenshots saved to: ./screenshots/

import { test } from "@playwright/test";
import { join } from "path";

const BASE = "http://localhost";
const SCREENSHOT_DIR = join(process.cwd(), "screenshots");
const ADMIN = { email: "admin@chess.local", password: "password" };

async function shot(page, name) {
    await page.waitForTimeout(800);
    await page.screenshot({
        path: join(SCREENSHOT_DIR, `${name}.png`),
        fullPage: false,
    });
    console.log(`  Saved: ${name}.png`);
}

// ── Guest pages (fresh context, no login) ───────────────────────────

test("01 - Login page", async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await shot(page, "01_login");
});

test("02 - Registration page", async ({ page }) => {
    await page.goto(`${BASE}/register`);
    await shot(page, "02_register");
});

// ── All authenticated pages in one serial block (single login) ──────

test.describe.serial("Authenticated screenshots", () => {
    /** @type {import('@playwright/test').Page} */
    let page;

    test.beforeAll(async ({ browser }) => {
        const ctx = await browser.newContext();
        page = await ctx.newPage();

        // Login once
        await page.goto(`${BASE}/login`);
        await page.fill(
            'input[type="email"], input[placeholder*="example"]',
            ADMIN.email
        );
        await page.fill('input[type="password"]', ADMIN.password);
        await page.getByRole("button", { name: /log in|pieslēgties/i }).click();
        await page.waitForURL((url) => !url.pathname.includes("/login"), {
            timeout: 15000,
        });
        // Wait for welcome modal to appear
        await page.waitForTimeout(1200);
    });

    test.afterAll(async () => {
        await page.context().close();
    });

    test("03 - Welcome modal", async () => {
        // The welcome modal should be visible right after first login
        await shot(page, "03_welcome_modal");

        // Dismiss it and mark tutorial complete so it never shows again
        const skipBtn = page.locator("button").filter({ hasText: /^Skip$|^Izlaist$/ });
        if (await skipBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
            await skipBtn.click();
        }
        await page.evaluate(() => {
            localStorage.setItem(
                "chess_tutorial_state",
                JSON.stringify({ completed: true, skipped: true })
            );
        });
        await page.waitForTimeout(500);
    });

    test("04 - Dashboard", async () => {
        await page.goto(`${BASE}/`);
        await page.waitForTimeout(600);
        await shot(page, "04_dashboard");
    });

    test("05 - Daily puzzle", async () => {
        await page.goto(`${BASE}/daily`);
        await shot(page, "05_daily_puzzle");
    });

    test("06 - Games list", async () => {
        await page.goto(`${BASE}/games`);
        await shot(page, "06_games");
    });

    test("07 - Upload game dialog", async () => {
        await page.goto(`${BASE}/games`);
        await page.waitForTimeout(600);
        // Button text is "Upload game" (EN) or "Augšupielādēt partiju" (LV)
        const uploadBtn = page.locator("button, a").filter({
            hasText: /upload game|augšupielādēt partiju/i,
        });
        await uploadBtn.first().click();
        await page.waitForTimeout(600);
        await shot(page, "07_upload_game");
        // Close the dialog
        await page.keyboard.press("Escape");
        await page.waitForTimeout(300);
    });

    test("08 - Game view", async () => {
        await page.goto(`${BASE}/games`);
        await page.waitForTimeout(1000);
        // Button text is "◉ Skatīt" (analyzed) or "⚡ Analizēt" (not analyzed) in LV
        const viewBtn = page
            .locator("button")
            .filter({ hasText: /Skatīt|View|Analizēt|Analyze/i })
            .first();
        if (await viewBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
            await viewBtn.click();
            await page.waitForTimeout(1500);
        }
        await shot(page, "08_game_view");
        await page.keyboard.press("Escape");
        await page.waitForTimeout(300);
    });

    test("09 - Play vs Stockfish", async () => {
        await page.goto(`${BASE}/play`);
        await shot(page, "09_play");
    });

    test("10 - Training", async () => {
        await page.goto(`${BASE}/training`);
        await shot(page, "10_training");
    });

    test("11 - Openings", async () => {
        await page.goto(`${BASE}/openings`);
        await shot(page, "11_openings");
    });

    test("12 - Lessons", async () => {
        await page.goto(`${BASE}/lessons`);
        await shot(page, "12_lessons");
    });

    test("13 - Puzzles", async () => {
        await page.goto(`${BASE}/puzzles`);
        await shot(page, "13_puzzles");
    });

    test("14 - Achievements", async () => {
        await page.goto(`${BASE}/achievements`);
        await shot(page, "14_achievements");
    });

    test("15 - Multiplayer", async () => {
        await page.goto(`${BASE}/multiplayer`);
        await shot(page, "15_multiplayer");
    });

    test("16 - Friends", async () => {
        await page.goto(`${BASE}/multiplayer`);
        await page.waitForTimeout(1000);
        // Tab text from $t('mp.tab_friends') = "Draugi" (displayed uppercase via CSS)
        const friendsTab = page
            .locator("button")
            .filter({ hasText: /Draugi|Friends/i });
        if (await friendsTab.isVisible({ timeout: 3000 }).catch(() => false)) {
            await friendsTab.click();
            await page.waitForTimeout(800);
        }
        await shot(page, "16_friends");
    });

    test("17 - Profile", async () => {
        await page.goto(`${BASE}/profile`);
        await shot(page, "17_profile");
    });

    test("18 - Settings (scrolled)", async () => {
        await page.goto(`${BASE}/profile`);
        await page.waitForTimeout(400);
        await page.evaluate(() => window.scrollTo(0, 800));
        await page.waitForTimeout(600);
        await shot(page, "18_settings");
    });

    test("19 - Admin: Overview", async () => {
        await page.goto(`${BASE}/admin`);
        await page.waitForTimeout(1000);
        await shot(page, "19_admin_overview");
    });

    test("20 - Admin: Users", async () => {
        // Tab text: "Lietotāji" (LV) / "Users" (EN)
        const tab = page.locator("button").filter({ hasText: /Lietotāji|Users/i });
        if (await tab.isVisible({ timeout: 3000 }).catch(() => false)) {
            await tab.click();
            await page.waitForTimeout(800);
        }
        await shot(page, "20_admin_users");
    });

    test("21 - Admin: Games", async () => {
        const tab = page.locator("button").filter({ hasText: /Partijas|Games/i });
        if (await tab.isVisible({ timeout: 3000 }).catch(() => false)) {
            await tab.click();
            await page.waitForTimeout(800);
        }
        await shot(page, "21_admin_games");
    });

    test("22 - Admin: Analytics", async () => {
        const tab = page.locator("button").filter({ hasText: /Analītika|Analytics/i });
        if (await tab.isVisible({ timeout: 3000 }).catch(() => false)) {
            await tab.click();
            await page.waitForTimeout(800);
        }
        await shot(page, "22_admin_analytics");
    });

    test("23 - Admin: Audit log", async () => {
        const tab = page.locator("button").filter({ hasText: /Audita|Audit/i });
        if (await tab.isVisible({ timeout: 3000 }).catch(() => false)) {
            await tab.click();
            await page.waitForTimeout(800);
        }
        await shot(page, "23_admin_audit");
    });
});
