import { test, expect } from '@playwright/test';
test('administrator navigation and keyboard focus', async ({page}) => {
 await page.goto('/wp-login.php');
 // WordPress wp_attempt_focus schedules a focus/select after 200ms.
 await page.waitForTimeout(250); await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('password'); await page.locator('#wp-submit').click(); await page.waitForURL('**/wp-admin/**');
 await page.goto('/wp-admin/admin.php?page=sfbk-dashboard'); await expect(page.locator('.sfbk-admin h1')).toHaveText('Dashboard');
 await page.keyboard.press('Tab'); await expect(page.locator(':focus')).toBeVisible();
 for (const slug of ['calendar','trainings','sessions','customers','payments','settings']) { await page.goto('/wp-admin/admin.php?page=sfbk-'+slug); await expect(page.locator('.sfbk-admin h1')).toBeVisible(); }
 const checkbox=page.locator('input[name="sfbk_delete_data"]');
 await checkbox.check(); await page.locator('#submit').click(); await expect(checkbox).toBeChecked();
 await checkbox.uncheck(); await page.locator('#submit').click(); await expect(checkbox).not.toBeChecked();
 const denied=await page.request.post('/wp-admin/admin-post.php',{form:{action:'sfbk_save_uninstall',sfbk_delete_data:'1'}}); expect(denied.status()).toBe(403);
 await page.reload(); await expect(checkbox).not.toBeChecked();
 await checkbox.focus(); await page.keyboard.press('Space'); await expect(checkbox).toBeChecked(); await page.keyboard.press('Space'); await expect(checkbox).not.toBeChecked();
 await page.setViewportSize({width:360,height:800}); await expect(page.locator('.sfbk-admin h1')).toBeVisible();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=document.documentElement.clientWidth)).toBe(true);
 await page.screenshot({path:'artifacts/playwright/settings.png',fullPage:true});
});
