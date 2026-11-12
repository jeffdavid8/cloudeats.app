import { expect, test } from '@playwright/test';
import path from 'path';

test('suggestions remain clickable beyond the hero and address results report their count', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 800 });
  await page.route('https://landing.test/**', route => route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><body></body></html>' }));
  await page.goto('https://landing.test/');
  await page.setContent(`
    <main class="ce-landing">
      <section class="ce-hero">
        <div class="ce-hero-background"><div id="heroBgParallax" class="hero-bg-parallax"></div></div>
        <div class="ce-hero-container">
          <form id="ce-search-form" class="ce-search">
            <input id="ce-address" type="search"><button type="submit">Find places</button>
            <ul id="ce-suggestions" class="ce-suggestions" role="listbox" hidden></ul>
          </form>
          <p id="ce-location-note" class="ce-search-note"></p>
          <div id="ce-nearby-summary" class="ce-nearby-summary" aria-live="polite" hidden>
            <span id="ce-nearby-count"></span><a href="#merchant-grid-section">View places</a>
          </div>
          <nav class="ce-categories"><button type="button" data-category="Pizza">Pizza</button></nav>
          <button id="ce-locate"></button>
        </div>
      </section>
      <section id="merchant-grid-section" class="ce-results">
        <span id="ce-result-count"></span><div id="ce-merchant-grid"></div>
      </section>
    </main>
  `);
  await page.addStyleTag({ path: path.resolve('html/apps/neighborhub/css/landing.css') });
  await page.addStyleTag({ content: `
    .ce-hero { min-height: 0; padding: 20px 18px; }
    .ce-results { min-height: 1000px; position: relative; }
    .ce-results::before { content: ''; position: absolute; inset: 0; background: transparent; }
  ` });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.evaluate(() => {
    localStorage.removeItem('cloudEats.deliveryLocation');
    Object.assign(window, {
      requests: [],
      loading: () => {},
      mb: { ajax: (options: object) => (window as Window & { requests: object[] }).requests.push(options) },
    });
  });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/landing.js') });
  await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);

  const respond = async (action: string, value: object, failure = false) => page.evaluate(({ action, value, failure }) => {
    type Request = { url: string; success: (value: object) => void; error: () => void; complete?: () => void };
    const requests = (window as Window & { requests: Request[] }).requests;
    const index = requests.findIndex(request => request.url.includes(action));
    if (index < 0) throw new Error(`Missing request: ${action}`);
    const request = requests.splice(index, 1)[0];
    if (failure) request.error();
    else request.success(value);
    request.complete?.();
  }, { action, value, failure });
  const waitFor = async (action: string) => expect.poll(() => page.evaluate(action => {
    const requests = (window as Window & { requests: { url: string }[] }).requests;
    return requests.some(request => request.url.includes(action));
  }, action)).toBe(true);
  const search = page.locator('#ce-address');
  await search.fill('Main');
  await waitFor('merchant_suggestions');
  await respond('merchant_suggestions', { success: true, merchants: [] });
  await respond('geocode_proxy', Array.from({ length: 5 }, (_, i) => ({ display_name: `${i + 1} Main Street`, lat: 40, lon: -85 })));
  const list = page.locator('#ce-suggestions');
  const last = page.getByRole('option', { name: '5 Main Street' });
  await last.scrollIntoViewIfNeeded();
  const geometry = await last.evaluate(element => ({
    top: element.getBoundingClientRect().top,
    heroBottom: document.querySelector('.ce-hero')!.getBoundingClientRect().bottom,
  }));
  expect(geometry.top).toBeGreaterThan(geometry.heroBottom);
  await last.click();
  await expect(list).toBeHidden();
  await waitFor('search_merchants');
  await expect(page.locator('#ce-nearby-summary')).toBeHidden();
  const merchant = { id: 1, business_name: 'Shop', operational_status: { status: 'open', label: 'Open now', message: 'Closes soon' } };
  await respond('search_merchants', { success: true, merchants: [merchant, { ...merchant, id: 2 }] });
  await expect(page.locator('#ce-nearby-count')).toHaveText('2 places found nearby');
  await expect(page.locator('#ce-nearby-summary a')).toHaveAttribute('href', '#merchant-grid-section');

  await page.locator('.ce-categories button').click();
  await waitFor('search_merchants');
  await expect(page.locator('#ce-nearby-summary')).toBeHidden();
  await respond('search_merchants', { success: true, merchants: [merchant] });
  await expect(page.locator('#ce-nearby-count')).toHaveText('1 place matching Pizza found nearby');

  await page.locator('.ce-categories button').click();
  await waitFor('search_merchants');
  await respond('search_merchants', { success: true, merchants: [] });
  await expect(page.locator('#ce-nearby-count')).toHaveText('0 places matching Pizza found nearby');
  await page.locator('.ce-categories button').click();
  await waitFor('search_merchants');
  await respond('search_merchants', {}, true);
  await expect(page.locator('#ce-nearby-summary')).toBeHidden();
  await expect(page.locator('#ce-merchant-grid')).toContainText('Search is taking a break');

  await page.locator('.ce-categories button').click();
  await waitFor('search_merchants');
  await search.fill('new address');
  await respond('search_merchants', { success: true, merchants: [merchant] });
  await expect(page.locator('#ce-nearby-summary')).toBeHidden();
});
