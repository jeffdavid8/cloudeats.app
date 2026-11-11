import { expect, test } from '@playwright/test';
import path from 'path';

test('merchant cards use server operational status and report invalid responses', async ({ page }) => {
  await page.route('https://cloudeats.app.local/', route => route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><body></body></html>' }));
  await page.goto('https://cloudeats.app.local/');
  await page.setContent(`
    <div id="heroBgParallax"></div>
    <form id="ce-search-form"><input id="ce-address"><ul id="ce-suggestions"></ul></form>
    <button id="ce-locate"></button><span id="ce-location-note"></span>
    <section id="merchant-grid-section"><span id="ce-result-count"></span><div id="ce-merchant-grid"></div></section>
  `);
  await page.addStyleTag({ path: path.resolve('html/apps/neighborhub/css/landing.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  const errors: string[] = [];
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  await page.evaluate(() => {
    const merchants = [
      { id: 1, business_name: 'Open', image_url: '/missing.png', operational_status: { status: 'open', label: 'Open now', message: 'Closes in 1 hour' } },
      { id: 2, business_name: 'Closing', image_url: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', operational_status: { status: 'open', label: 'Closing soon', message: 'Closes in 15 minutes' } },
      { id: 3, business_name: 'Closed', operational_status: { status: 'closed', label: 'Closed', message: 'Opens Wednesday at 9:00 AM' } },
      { id: 4, business_name: 'Missing hours', operational_status: { status: 'closed', label: 'Closed', message: 'Hours not listed' } },
      { id: 5, business_name: 'Invalid status' },
      { id: 6, business_name: 'Paused', operational_status: { status: 'closed', label: 'Temporarily paused', message: 'Temporarily paused by vendor' } },
      { id: 7, business_name: 'No image', operational_status: { status: 'open', label: 'Open now', message: 'Closes in 1 hour' } },
    ];
    Object.assign(window, {
      loading: () => {},
      mb: { ajax: (options: { success: (value: object) => void; complete: () => void }) => {
        options.success({ success: true, merchants });
        options.complete();
      } },
    });
  });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/landing.js') });
  await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);
  await page.evaluate(() => {
    const input = document.getElementById('ce-address') as HTMLInputElement;
    input.value = 'Example address';
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });
  await page.evaluate(() => {
    Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
      getCurrentPosition: (success: (position: object) => void) => success({ coords: { latitude: 40, longitude: -85 } }),
    } });
  });
  await page.locator('#ce-locate').click();
  const cards = page.locator('.ce-merchant-card');
  await expect(cards).toHaveCount(7);
  await expect(cards.nth(0).locator('.ce-card-status')).toHaveText('Open nowCloses in 1 hour');
  await expect(cards.nth(0).locator('.ce-card-status')).toHaveClass(/is-open/);
  await expect(cards.nth(0)).toHaveAttribute('aria-label', 'Browse Open. Open now. Closes in 1 hour');
  await expect(cards.nth(0).locator('.ce-card-image-status-dot')).toBeVisible();
  await expect(cards.nth(0).locator('.ce-card-image')).toHaveClass(/is-placeholder/);
  await expect(cards.nth(0).locator('.ce-card-image')).toHaveText('CE');
  await expect(cards.nth(1).locator('.ce-card-status')).toHaveText('Closing soonCloses in 15 minutes');
  await expect(cards.nth(1).locator('.ce-card-image-status-dot')).toBeVisible();
  await expect(cards.nth(1).locator('.ce-card-image img')).toBeVisible();
  await expect(cards.nth(2).locator('.ce-card-status')).toHaveText('ClosedOpens Wednesday at 9:00 AM');
  await expect(cards.nth(2).locator('.ce-card-status')).toHaveClass(/is-closed/);
  await expect(cards.nth(3).locator('.ce-card-status')).toHaveText('Hours not listed');
  await expect(cards.nth(4).locator('.ce-card-status')).toHaveText('Status unavailable');
  await expect(cards.nth(5).locator('.ce-card-status')).toHaveText('Temporarily pausedTemporarily paused by vendor');
  for (const index of [2, 3, 4, 5]) {
    await expect(cards.nth(index).locator('.ce-card-image-status-dot')).toHaveCount(0);
  }
  await expect(cards.nth(6).locator('.ce-card-image')).toHaveText('CE');
  await expect(cards.nth(6).locator('.ce-card-image-status-dot')).toBeVisible();
  await page.setViewportSize({ width: 375, height: 812 });
  for (const index of [0, 1, 6]) {
    const dot = cards.nth(index).locator('.ce-card-image-status-dot');
    await expect(dot).toBeVisible();
    await expect(dot).toHaveCSS('background-color', 'rgb(51, 132, 92)');
    const bounds = await dot.boundingBox();
    const imageBounds = await cards.nth(index).locator('.ce-card-image').boundingBox();
    expect(bounds).not.toBeNull();
    expect(imageBounds).not.toBeNull();
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(imageBounds!.x + imageBounds!.width);
    expect(bounds!.y + bounds!.height).toBeLessThanOrEqual(imageBounds!.y + imageBounds!.height);
  }
  expect(errors.some(message => message.includes('Invalid merchant operational status'))).toBe(true);
});
