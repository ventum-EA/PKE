/**
 * E2E test helpers — shared fixtures and utility functions.
 *
 * Uses a timestamp-based unique user for each test run so tests
 * don't collide with existing data or parallel runs.
 */

/** Generate a unique test user for this run */
export function testUser() {
    const id = Date.now().toString(36);
    return {
        name: `e2e_${id}`,
        email: `e2e_${id}@test.local`,
        password: 'TestPass123!',
    };
}

/** A valid, short PGN for testing — Italian Game, 10 moves */
export const SAMPLE_PGN = `[Event "Test Game"]
[Site "E2E"]
[Date "2026.01.15"]
[White "Player1"]
[Black "Player2"]
[Result "1-0"]
[ECO "C50"]

1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. O-O Nf6 5. d3 d6 6. c3 O-O 7. Re1 a6
8. Bb3 Ba7 9. h3 Be6 10. Bxe6 fxe6 1-0`;

/** A deliberately invalid PGN for negative testing */
export const INVALID_PGN = `[Event "Bad"]
1. e4 e5 2. Qxe5?? Zz9 invalidmove`;

/**
 * Register a new user via the UI.
 * Returns the user credentials for later login.
 */
export async function registerUser(page, user = null) {
    const u = user || testUser();
    await page.goto('/register');
    await page.getByLabel(/lietotājvārds|username/i).fill(u.name);
    await page.getByLabel(/e-pasts|email/i).first().fill(u.email);
    // Password fields — first is password, second is confirm
    const pwFields = page.locator('input[type="password"]');
    await pwFields.nth(0).fill(u.password);
    await pwFields.nth(1).fill(u.password);
    await page.getByRole('button', { name: /reģistrēties|register/i }).click();
    // Wait for redirect to dashboard
    await page.waitForURL(/\/(dashboard)?$/, { timeout: 10_000 });
    return u;
}

/**
 * Log in an existing user via the UI.
 */
export async function loginUser(page, user) {
    await page.goto('/login');
    // Email or username field
    const emailField = page.locator('input[type="email"], input[name="email"]').first();
    await emailField.fill(user.email);
    await page.locator('input[type="password"]').first().fill(user.password);
    await page.getByRole('button', { name: /pieslēgties|login|sign in/i }).click();
    await page.waitForURL(/\/(dashboard)?$/, { timeout: 10_000 });
}

/**
 * Upload a PGN via the games page.
 */
export async function uploadPgn(page, pgn = SAMPLE_PGN) {
    await page.goto('/games');
    // Click the upload button
    const uploadBtn = page.getByRole('button', { name: /augšupielādēt|upload|import/i }).first();
    await uploadBtn.click();
    // Wait for modal/dialog
    await page.waitForTimeout(500);
    // Find the PGN text area and paste
    const pgnInput = page.locator('textarea').first();
    await pgnInput.fill(pgn);
    // Submit
    const submitBtn = page.getByRole('button', { name: /saglabāt|save|submit|augšupielādēt/i }).last();
    await submitBtn.click();
    // Wait for success notification or game to appear
    await page.waitForTimeout(2000);
}
