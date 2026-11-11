import { test, expect } from '@playwright/test';
test('stylesheet is limited to plugin pages', async ({page}) => {
 await page.goto('/wp-login.php');
 // WordPress wp_attempt_focus schedules a focus/select after 200ms.
 await page.waitForTimeout(250); await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('password'); await page.locator('#wp-submit').click(); await page.waitForURL('**/wp-admin/**');
 await page.goto('/wp-admin/index.php'); await expect(page.locator('#sfbk-admin-css')).toHaveCount(0);
 await page.goto('/wp-admin/admin.php?page=sfbk-dashboard'); await expect(page.locator('#sfbk-admin-css')).toHaveCount(1);
});
