import { test, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const headerLeft = readFileSync(path.resolve('html/views/components/header/header_left.php'), 'utf8');
const searchScript = headerLeft.match(/<script>([\s\S]*?)<\/script>/)![1];
const header = readFileSync(path.resolve('html/views/components/header/header.php'), 'utf8');
const searchPanel = header.match(/<div id="header-merchant-search-panel"[\s\S]*?<\/div>\s*<\?php endif;/)![0]
  .replace(/<\?php endif;$/, '')
  .replace(/<\?=[\s\S]*?\?>/, 'Example merchant');
const legacyFilter = readFileSync(path.resolve('html/apps/neighborhub/views/components/merchant_menu_product_search_filter.php'), 'utf8');
const legacyScript = legacyFilter.match(/<script>([\s\S]*?)<\/script>/)![1];

for (const width of [375, 1024]) {
  test(`menu search remains visible, filters and resets at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 800 });
    await page.setContent(`
      <header class="header"><nav>
        <div class="nav-wrapper">
          <ul class="header-left"><li><button class="header-merchant-search-trigger" aria-controls="header-merchant-search-panel" aria-expanded="false">Search</button></li></ul>
          <ul class="header-right"><li><a href="#" class="nh-header-cart">Basket</a></li></ul>
        </div>
        <div class="nh-merchant-operational-status">Open now</div>
        ${searchPanel}
      </nav></header>
      <main style="padding-top: 250px">
        <input id="iv-product-search-input"><span id="iv-result-count"></span>
        <h4 class="custom-section-title" id="pizza-title">Pizza</h4>
        <div class="row" id="pizza-row">
          <div class="col s12 m6 l4" id="pepperoni"><div class="product-item-card">Pepperoni pizza</div></div>
          <div class="col s12 m6 l4" id="cheese"><div class="product-item-card">Cheese pizza</div></div>
        </div>
        <h4 class="custom-section-title" id="dessert-title">Dessert</h4>
        <div class="row" id="dessert-row">
          <div class="col s12 m6 l4" id="brownie"><div class="product-item-card">Chocolate brownie</div></div>
        </div>
        <section class="iv-product-category" id="ice-cream"><h4>Ice cream</h4>
          <div class="row"><div class="col s12 m6 l4" id="sundae" style="display:flex"><article class="product-item-card">Strawberry sundae</article></div></div>
        </section>
      </main>
    `);
    for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/apps/neighborhub/css/merchant-operational-status.css']) {
      await page.addStyleTag({ path: path.resolve(stylesheet) });
    }

    await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
    await page.addScriptTag({ content: searchScript });
    await page.addScriptTag({ content: legacyScript });
    await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);

    const trigger = page.locator('.header-merchant-search-trigger');
    const panel = page.locator('#header-merchant-search-panel');
    const search = page.locator('#header-merchant-search-input');
    const clear = page.getByRole('button', { name: 'Clear search' });
    const noResults = page.locator('#header-merchant-search-no-results');
    await expect(panel).toBeHidden();
    await trigger.click();
    await expect(panel).toBeVisible();
    await expect(search).toBeFocused();
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(clear).toBeHidden();
    await page.evaluate(() => document.body.classList.add('scrolled'));
    const bounds = await page.evaluate(() => ({
      nav: document.querySelector('.nav-wrapper')!.getBoundingClientRect().bottom,
      status: document.querySelector('.nh-merchant-operational-status')!.getBoundingClientRect().bottom,
      panel: document.querySelector('#header-merchant-search-panel')!.getBoundingClientRect().top,
      basket: document.querySelector('.nh-header-cart')!.getBoundingClientRect().bottom,
    }));
    expect(bounds.panel).toBeGreaterThanOrEqual(bounds.nav);
    expect(bounds.panel).toBeGreaterThanOrEqual(bounds.status);
    expect(bounds.panel).toBeGreaterThanOrEqual(bounds.basket);
    await expect(search).toHaveCSS('font-size', '16px');
    await page.locator('html').evaluate(el => el.classList.add('nightMode'));
    await expect(search).toBeVisible();

    await search.fill('pepperoni');
    await expect(page.locator('#pepperoni')).toBeVisible();
    await expect(page.locator('#cheese')).toBeHidden();
    await expect(page.locator('#dessert-title')).toBeHidden();
    await expect(page.locator('#ice-cream')).toBeHidden();
    await expect(page.locator('#iv-product-search-input')).toHaveValue('pepperoni');
    await expect(page.locator('#iv-result-count')).toHaveText('Showing 1 of 4 items');
    await search.press('Tab');
    await expect(clear).toBeFocused();
    await expect(panel).toBeVisible();
    await expect(search).toHaveValue('pepperoni');
    await search.fill('no matching product');
    await expect(noResults).toBeVisible();
    await clear.click();
    await expect(search).toBeFocused();
    await expect(noResults).toBeHidden();
    await expect(page.locator('#sundae')).toHaveCSS('display', 'flex');
    await expect(page.locator('#dessert-title')).toBeVisible();
    await search.fill('BROWNIE');
    await page.getByRole('button', { name: 'Cancel' }).click();
    await expect(panel).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('#pepperoni')).toBeVisible();
    await expect(page.locator('#iv-product-search-input')).toHaveValue('');

    await page.locator('#iv-product-search-input').fill('sundae');
    await expect(page.locator('#pizza-title')).toBeHidden();
    await expect(page.locator('#ice-cream')).toBeVisible();
    await trigger.click();
    await expect(search).toHaveValue('sundae');
    await search.press('Escape');
    await expect(panel).toBeHidden();
    await expect(page.locator('#pizza-title')).toBeVisible();
    await expect(page.locator('#iv-product-search-input')).toHaveValue('');
    await trigger.click();
    await search.fill('cheese');
    await trigger.click();
    await expect(panel).toBeHidden();
    await expect(page.locator('#brownie')).toBeVisible();
  });
}
