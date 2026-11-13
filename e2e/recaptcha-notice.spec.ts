import { execFileSync } from 'child_process';
import { expect, test } from '@playwright/test';
import path from 'path';

for (const form of ['signup', 'merchant']) {
  for (const mode of ['dayMode', 'nightMode']) {
    test(`${form} displays reCAPTCHA attribution near submission in ${mode}`, async ({ page }) => {
      const markup = execFileSync('php', [
        '-d', 'short_open_tag=1',
        path.resolve('tests/fixtures/recaptcha-notice-page-worker.php'), form,
      ], { encoding: 'utf8' }).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
      await page.setViewportSize({ width: 375, height: 900 });
      await page.setContent(`<html class="${mode}"><body>${markup}</body></html>`);
      for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/css/nightmode.css', 'html/apps/neighborhub/css/neighborhub.css']) {
        await page.addStyleTag({ path: path.resolve(stylesheet) });
      }
      const container = page.locator(form === 'signup' ? '#neighborhub-signup-modal' : '#step-4');
      await container.evaluate(element => { (element as HTMLElement).style.display = 'block'; });
      const notice = container.locator('.recaptcha-notice');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('This site is protected by reCAPTCHA');
      await expect(notice.getByRole('link', { name: 'Privacy Policy' })).toHaveAttribute('href', 'https://policies.google.com/privacy');
      await expect(notice.getByRole('link', { name: 'Terms of Service' })).toHaveAttribute('href', 'https://policies.google.com/terms');
      const bounds = await notice.boundingBox();
      expect(bounds).not.toBeNull();
      expect(bounds!.x).toBeGreaterThanOrEqual(0);
      expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(375);
      const submit = container.locator(form === 'signup' ? '#neighborhub-signup-submit' : '#btn-submit-merchant');
      await expect(submit).toBeVisible();
      expect(bounds!.y + bounds!.height).toBeLessThanOrEqual((await submit.boundingBox())!.y);
    });
  }
}
