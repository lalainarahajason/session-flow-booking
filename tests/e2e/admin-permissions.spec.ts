import { test, expect } from '@playwright/test';
test('trainer cannot access settings directly', async ({page}) => {
 await page.goto('/wp-login.php');
 // WordPress wp_attempt_focus schedules a focus/select after 200ms.
 await page.waitForTimeout(250); await page.locator('#user_login').fill('sfbk_browser_trainer'); await page.locator('#user_pass').fill('sfbk_browser_test_only'); await page.locator('#wp-submit').click(); await page.waitForURL('**/wp-admin/**');
 await page.goto('/wp-admin/admin.php?page=sfbk-dashboard'); await expect(page.locator('.sfbk-admin h1')).toBeVisible();
 const response=await page.goto('/wp-admin/admin.php?page=sfbk-settings'); expect(response?.status()).toBe(403); await expect(page.locator('form[action*="admin-post.php"]')).toHaveCount(0);
});
