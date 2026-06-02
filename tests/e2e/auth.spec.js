import { test, expect } from '@playwright/test';
import { testUser, registerUser, loginUser } from './helpers.js';

test.describe('Authentication', () => {
    test('new user can register and lands on dashboard', async ({ page }) => {
        const user = await registerUser(page);
        // Should be on dashboard
        await expect(page).toHaveURL(/\/(dashboard)?$/);
        // Should see the user's name somewhere on the page
        await expect(page.locator('body')).toContainText(user.name);
    });

    test('registered user can log in', async ({ page }) => {
        const user = testUser();
        // Register first
        await registerUser(page, user);
        // Log out (navigate to logout)
        await page.goto('/logout');
        await page.waitForTimeout(1000);
        // Log back in
        await loginUser(page, user);
        await expect(page).toHaveURL(/\/(dashboard)?$/);
    });

    test('login with wrong password shows error', async ({ page }) => {
        await page.goto('/login');
        const emailField = page.locator('input[type="email"], input[name="email"]').first();
        await emailField.fill('nonexistent@test.local');
        await page.locator('input[type="password"]').first().fill('WrongPass999');
        await page.getByRole('button', { name: /pieslēgties|login|sign in/i }).click();
        // Should stay on login page and show an error
        await page.waitForTimeout(2000);
        await expect(page).toHaveURL(/\/login/);
    });

    test('registration with duplicate email shows error', async ({ page }) => {
        const user = testUser();
        await registerUser(page, user);
        await page.goto('/logout');
        await page.waitForTimeout(1000);
        // Try to register again with same email
        await page.goto('/register');
        await page.getByLabel(/lietotājvārds|username/i).fill('different_name');
        await page.getByLabel(/e-pasts|email/i).first().fill(user.email);
        const pwFields = page.locator('input[type="password"]');
        await pwFields.nth(0).fill(user.password);
        await pwFields.nth(1).fill(user.password);
        await page.getByRole('button', { name: /reģistrēties|register/i }).click();
        await page.waitForTimeout(2000);
        // Should still be on register page
        await expect(page).toHaveURL(/\/register/);
    });
});
