import { test, expect } from '@playwright/test';

test('public and authentication boundaries are reachable', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/Wedding Planner/i);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/login/);

    const readiness = await page.request.get('/ready');
    expect(readiness.ok()).toBeTruthy();
    await expect(readiness).toHaveJSON({ status: 'ok' });
});
