import { test, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const header = readFileSync(path.resolve('html/views/components/header/header_left.php'), 'utf8');
const searchScript = header.match(/<script>([\s\S]*?)<\/script>/)![1];

test('header search restores hidden categories when the query changes or clears', async ({ page }) => {
  await page.setContent(`
    <style>#header-merchant-search-input { min-height: 30px; width: 240px; }</style>
    <button class="header-merchant-search-trigger" aria-expanded="false">Search</button>
    <label class="header-merchant-search-overlay"><textarea id="header-merchant-search-input"></textarea></label>
    <h4 class="custom-section-title" id="pizza-title">Pizza</h4>
    <div class="row" id="pizza-row">
      <div class="col" id="pepperoni"><div class="product-item-card">Pepperoni pizza</div></div>
      <div class="col" id="cheese"><div class="product-item-card">Cheese pizza</div></div>
    </div>
    <h4 class="custom-section-title" id="dessert-title">Dessert</h4>
    <div class="row" id="dessert-row">
      <div class="col" id="brownie"><div class="product-item-card">Chocolate brownie</div></div>
    </div>
  `);
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ content: searchScript });

  const search = page.locator('#header-merchant-search-input');
  const setQuery = (value: string) => search.evaluate((input, query) => {
    (input as HTMLTextAreaElement).value = query;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }, value);
  await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);
  await setQuery('pepperoni');
  await expect(page.locator('#pepperoni')).toBeVisible();
  await expect(page.locator('#cheese')).toBeHidden();
  await expect(page.locator('#dessert-title')).toBeHidden();
  await expect(page.locator('#dessert-row')).toBeHidden();

  await setQuery('BROWNIE');
  await expect(page.locator('#pizza-row')).toBeHidden();
  await expect(page.locator('#dessert-title')).toBeVisible();
  await expect(page.locator('#dessert-row')).toBeVisible();
  await expect(page.locator('#brownie')).toBeVisible();

  await setQuery('no matching product');
  await expect(page.locator('#pizza-row')).toBeHidden();
  await expect(page.locator('#dessert-row')).toBeHidden();
  await setQuery('');
  for (const id of ['pizza-title', 'pizza-row', 'pepperoni', 'cheese', 'dessert-title', 'dessert-row', 'brownie']) {
    await expect(page.locator(`#${id}`)).toBeVisible();
  }
  await setQuery('  ');
  await expect(page.locator('#pizza-row')).toBeVisible();
  await expect(page.locator('#dessert-row')).toBeVisible();
});
