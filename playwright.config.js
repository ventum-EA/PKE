import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,       // Tests share auth state — run sequentially
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'html',
    timeout: 60_000,            // Stockfish WASM analysis can take time

    use: {
        baseURL: process.env.BASE_URL || 'http://localhost',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        locale: 'lv-LV',
    },

    /* Web server — start the app before tests if not already running */
    webServer: process.env.CI ? undefined : {
        command: 'composer dev',
        url: 'http://localhost',
        reuseExistingServer: true,
        timeout: 30_000,
    },
});
