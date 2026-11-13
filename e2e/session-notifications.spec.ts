import { execFileSync } from 'child_process';
import { expect, test } from '@playwright/test';
import path from 'path';

const php = `
  define('MB_RUNNING', true);
  session_start();
  require 'html/includes/util.php';
  session_notify('Merchant application submitted.', 'success');
  session_notify('Please review this warning.', 'warning');
  include 'html/views/components/session_notifications.php';
`;
const markup = execFileSync('php', ['-r', php], { encoding: 'utf8' });

for (const mode of ['dayMode', 'nightMode']) {
  test(`notices remain readable and dismissible in ${mode}`, async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.setContent(`<html class="${mode}"><body>${markup}</body></html>`);
    await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
    await page.addStyleTag({ path: path.resolve('html/css/style.css') });
    const notices = page.locator('.session-notice');
    await expect(notices).toHaveCount(2);
    await expect(notices.first()).toContainText('Merchant application submitted.');
    const bounds = await notices.first().boundingBox();
    expect(bounds).not.toBeNull();
    expect(bounds!.x).toBeGreaterThanOrEqual(0);
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(320);
    const dismiss = notices.first().getByRole('button', { name: 'Dismiss notification' });
    await dismiss.focus();
    await expect(dismiss).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(notices).toHaveCount(1);
    await expect(notices.first()).toContainText('Please review this warning.');
    await notices.first().getByRole('button', { name: 'Dismiss notification' }).click();
    await expect(page.locator('.session-notifications')).toHaveCount(0);
  });
}
