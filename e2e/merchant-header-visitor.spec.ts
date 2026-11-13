import { execFileSync } from 'child_process';
import { expect, test } from '@playwright/test';
import path from 'path';

test('visitor merchant icon stays beside basket and returns to top', async ({ page }) => {
  const html = execFileSync('php', [
    '-d', 'short_open_tag=1',
    path.resolve('tests/fixtures/merchant-sidenav-render-worker.php'), 'guest',
  ], { encoding: 'utf8' }).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
  await page.setViewportSize({ width: 320, height: 600 });
  await page.setContent(`${html}<main style="height:2000px"></main>`);
  for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/apps/neighborhub/css/neighborhub.css']) {
    await page.addStyleTag({ path: path.resolve(stylesheet) });
  }
  const icon = page.locator('.header-right .header-merchant-action');
  await expect(icon).toHaveAttribute('aria-label', 'Return to top of Example');
  await expect(page.locator('.header-left .header-merchant-brand')).toHaveCount(0);
  const iconBounds = await icon.boundingBox();
  const basketBounds = await page.locator('.nh-header-cart').boundingBox();
  expect(iconBounds!.x + iconBounds!.width).toBeLessThanOrEqual(basketBounds!.x);
  await page.evaluate(() => window.scrollTo(0, 1000));
  await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(0);
  await icon.click();
  await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);
});
