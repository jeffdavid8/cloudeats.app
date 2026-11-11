import { test, expect } from '@playwright/test';
import path from 'path';

test('merchant sidenav opens and closes independently of the main sidenav', async ({ page }) => {
  await page.setContent(`
    <header><a class="header-sidenav-trigger" href="#">Main</a>
      <a class="merchant-sidenav-trigger" href="#merchant-slide-out" aria-controls="merchant-slide-out" aria-expanded="false">Merchant</a>
    </header>
    <ul id="slide-out" class="sidenav"><li>Main navigation</li></ul>
    <ul id="merchant-slide-out" class="sidenav" aria-label="Merchant navigation">
      <li><a href="#!" class="merchant-sidenav-close">Close</a></li><li><a href="/products">Products</a></li>
    </ul>
  `);
  await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.evaluate(() => (window as any).M.Sidenav.init(document.getElementById('slide-out')));
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-sidenav.js') });
  const trigger = page.locator('.merchant-sidenav-trigger');
  await trigger.click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  expect(await page.evaluate(() => (window as any).M.Sidenav.getInstance(document.getElementById('slide-out')).isOpen)).toBe(false);
  await page.locator('.merchant-sidenav-close').click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(trigger).toBeFocused();
  await trigger.click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await page.locator('.sidenav-overlay:visible').click({ position: { x: 350, y: 30 } });
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
});
