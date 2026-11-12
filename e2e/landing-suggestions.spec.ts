import { expect, test } from '@playwright/test';
import path from 'path';

async function setup(page: import('@playwright/test').Page) {
  await page.route('https://suggestions.test/**', route => route.fulfill({ contentType: 'text/html', body: '<html><body>Storefront</body></html>' }));
  await page.goto('https://suggestions.test/');
  await page.setContent(`
    <div id="heroBgParallax"></div>
    <form id="ce-search-form"><input id="ce-address"><ul id="ce-suggestions" role="listbox" hidden></ul><button type="submit">Find places</button></form>
    <button id="ce-locate"></button><span id="ce-location-note"></span>
    <section id="merchant-grid-section"><span id="ce-result-count"></span><div id="ce-merchant-grid"></div></section>
  `);
  await page.addStyleTag({ path: path.resolve('html/apps/neighborhub/css/landing.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.evaluate(() => {
    localStorage.setItem('cloudEats.deliveryLocation', JSON.stringify({ address: 'Saved address', lat: 40, lng: -85 }));
    Object.assign(window, {
      loading: () => {},
      pendingSuggestions: [],
      mb: { ajax: (options: { url: string; success: (value: object) => void; complete?: () => void }) => {
        if (options.url.includes('search_merchants')) {
          options.success({ success: true, merchants: [] });
          options.complete?.();
        } else {
          (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.push(options);
        }
      } },
    });
  });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/landing.js') });
  await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);
}

async function reply(page: import('@playwright/test').Page, action: string, value: object, failure = false) {
  await page.evaluate(({ action, value, failure }) => {
    type Request = { url: string; success: (value: object) => void; error: () => void };
    const pending = (window as Window & { pendingSuggestions: Request[] }).pendingSuggestions;
    const index = pending.findIndex(request => request.url.includes(action));
    if (index < 0) throw new Error(`Missing ${action} request`);
    const request = pending.splice(index, 1)[0];
    if (failure) request.error();
    else request.success(value);
  }, { action, value, failure });
}

test('merchant suggestions precede addresses and open the store without changing saved location', async ({ page }) => {
  await setup(page);
  const search = page.locator('#ce-address');
  await search.fill('pizza');
  await expect.poll(() => page.evaluate(() => (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.length)).toBe(2);
  await reply(page, 'geocode_proxy', [{ display_name: 'Pizza Street', lat: 40, lon: -85 }]);
  await expect(page.locator('#ce-suggestions')).toBeHidden();
  await reply(page, 'merchant_suggestions', { success: true, merchants: [{ id: 7, business_name: 'Pizza King', address: '123 Main St', city: 'Marion', state: 'IN' }] });
  const options = page.getByRole('option');
  await expect(options).toHaveCount(2);
  await expect(options.nth(0)).toContainText('Pizza King');
  await expect(options.nth(0)).toContainText('123 Main St, Marion, IN');
  await expect(options.nth(1)).toContainText('Pizza Street');
  await expect(page.locator('.ce-suggestion-heading')).toHaveText(['Stores', 'Addresses']);
  await search.press('Enter');
  await expect(page).toHaveURL('https://suggestions.test/?app=neighborhub&view=customer&p=merchant_products&merchant_id=7');
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('cloudEats.deliveryLocation')!).address)).toBe('Saved address');
});

test('address selection works if merchant lookup fails and stale results stay hidden', async ({ page }) => {
  await setup(page);
  const search = page.locator('#ce-address');
  await search.fill('main');
  await expect.poll(() => page.evaluate(() => (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.length)).toBe(2);
  await reply(page, 'merchant_suggestions', {}, true);
  await expect(page.locator('#ce-location-note')).toContainText('Store suggestions are unavailable');
  await reply(page, 'geocode_proxy', [{ display_name: '123 Main St', lat: 41, lon: -84 }]);
  await page.getByRole('option', { name: '123 Main St' }).click();
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('cloudEats.deliveryLocation')!).address)).toBe('123 Main St');

  await search.fill('another');
  await expect.poll(() => page.evaluate(() => (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.length)).toBe(2);
  await search.fill('an');
  await reply(page, 'merchant_suggestions', { success: true, merchants: [{ id: 7, business_name: 'Stale store' }] });
  await reply(page, 'geocode_proxy', [{ display_name: 'Stale address', lat: 40, lon: -85 }]);
  await expect(page.locator('#ce-suggestions')).toBeHidden();
});

test('merchant suggestions remain usable when address lookup fails, and escape dismisses pending results', async ({ page }) => {
  await setup(page);
  const search = page.locator('#ce-address');
  await search.fill('pizza');
  await expect.poll(() => page.evaluate(() => (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.length)).toBe(2);
  await reply(page, 'geocode_proxy', {}, true);
  await expect(page.locator('#ce-location-note')).toContainText('Address suggestions are unavailable');
  await reply(page, 'merchant_suggestions', { success: true, merchants: [{ id: 8, business_name: 'Local Pizza' }] });
  await expect(page.getByRole('option', { name: 'Local Pizza' })).toBeVisible();
  await expect(page.locator('.ce-suggestion-heading')).toHaveText(['Stores']);

  await search.fill('new search');
  await expect.poll(() => page.evaluate(() => (window as Window & { pendingSuggestions: object[] }).pendingSuggestions.length)).toBe(2);
  await search.press('Escape');
  await reply(page, 'merchant_suggestions', { success: true, merchants: [{ id: 9, business_name: 'Too late' }] });
  await reply(page, 'geocode_proxy', [{ display_name: 'Too late address', lat: 1, lon: 2 }]);
  await expect(page.locator('#ce-suggestions')).toBeHidden();
});
