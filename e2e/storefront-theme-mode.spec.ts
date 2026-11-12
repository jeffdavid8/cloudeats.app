import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const themes = path.resolve('html/apps/neighborhub/views/storefront/themes');
const defaultCss = readFileSync(path.join(themes, 'default.php'), 'utf8')
  .match(/<style>([\s\S]*?)<\/style>/)![1]
  .replace(/<\?php[\s\S]*?\?>/g, '')
  .replace(/<\?=[\s\S]*?\?>/g, '#ffffff');

for (const theme of ['default', 'pizza-king', 'bakery', 'diner']) {
  test(`${theme} uses configured day background and its own night palette`, async ({ page }) => {
    await page.setContent('<div class="merchant-storefront-hero"></div><div class="merchant-storefront-block"><h3>Heading</h3><p>Text</p></div>');
    await page.addStyleTag({ path: path.resolve('html/apps/neighborhub/css/merchant-operational-status.css') });
    await page.addStyleTag({ content: defaultCss });
    if (theme !== 'default') {
      const css = readFileSync(path.join(themes, `${theme}.php`), 'utf8')
        .match(/<style>([\s\S]*?)<\/style>/)![1].replace(/<\?=[\s\S]*?\?>/g, '');
      await page.addStyleTag({ content: css });
    }
    await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
    await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchantStorefront.js') });
    await page.evaluate((name) => {
      document.body.dataset.storefrontTheme = name;
      document.documentElement.className = 'dayMode';
    }, theme);
    const body = page.locator('body');
    await expect(body).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    const dayText = await body.evaluate(el => getComputedStyle(el).color);
    const day = await body.evaluate(el => ({
      background: getComputedStyle(el).getPropertyValue('--brand-bg').trim(),
      text: getComputedStyle(el).getPropertyValue('--brand-text-main').trim(),
    }));
    await page.evaluate(() => { document.documentElement.className = 'nightMode'; });
    const night = await body.evaluate(el => ({
      background: getComputedStyle(el).getPropertyValue('--brand-bg').trim(),
      text: getComputedStyle(el).getPropertyValue('--brand-text-main').trim(),
      rendered: getComputedStyle(el).backgroundColor,
    }));
    expect(night.background).not.toBe(day.background);
    expect(night.text).not.toBe(day.text);
    await expect(body).not.toHaveCSS('background-color', 'rgb(255, 255, 255)');
    await expect(body).not.toHaveCSS('color', dayText);
    await page.evaluate((name) => {
      const storefront = Object.create((0, eval)('MerchantStorefront').prototype);
      storefront.applyPublishedConfiguration({ theme: name, colors: { background: '#cde4f2' }, assets: {}, asset_urls: {} });
    }, theme);
    await expect(body).toHaveCSS('background-color', night.rendered);
    await page.evaluate(() => { document.documentElement.className = 'dayMode'; });
    await expect(body).toHaveCSS('background-color', 'rgb(205, 228, 242)');
    await page.evaluate(() => { document.documentElement.className = 'nightMode'; });
    await expect(body).toHaveCSS('background-color', night.rendered);
  });
}
