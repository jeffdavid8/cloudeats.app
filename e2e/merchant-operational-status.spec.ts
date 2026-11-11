import { test, expect } from '@playwright/test';
import path from 'path';

test('refreshes on a timer and visibility changes, synchronizes controls, and fails closed', async ({ page }) => {
  await page.setContent(`
    <span class="header-merchant-status-dot" data-merchant-id="10" aria-label="Open now" hidden></span>
    <span class="header-merchant-status-dot" data-merchant-id="11" aria-label="Open now" hidden></span>
    <div id="nh-merchant-operational-status" class="nh-merchant-operational-status is-closed"
         data-merchant-id="10" data-status="closed" role="status">
      <span class="nh-merchant-operational-dot"></span>
      <strong data-status-label>Closed</strong><span data-status-message>Hours not listed</span>
    </div>
    <button class="nh-add-standard-btn">Add</button>
    <button class="nh-customize-trigger" disabled>Already disabled</button>
  `);
  await page.addStyleTag({ path: path.resolve('html/css/style.css') });
  await page.evaluate(() => {
    const w = window as any;
    w.requests = [];
    w.intervals = [];
    w.retries = new Map();
    w.nextRetryId = 0;
    w.mb = {
      ajax(options: any) {
        w.requests.push(options);
        return { abort() {} };
      },
    };
    w.setInterval = (callback: () => void, delay: number) => {
      w.intervals.push({ callback, delay });
      return w.intervals.length;
    };
    w.setTimeout = (callback: () => void, delay: number) => {
      const id = ++w.nextRetryId;
      w.retries.set(id, { callback, delay });
      return id;
    };
    w.clearTimeout = (id: number) => w.retries.delete(id);
  });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchantStorefront.js') });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-operational-status.js') });
  await page.evaluate(() => {
    const storefront = Object.create((0, eval)('MerchantStorefront').prototype);
    storefront.initOperationalStatus();
  });
  const banner = page.locator('#nh-merchant-operational-status');
  const dot = page.locator('.header-merchant-status-dot[data-merchant-id="10"]');
  const otherDot = page.locator('.header-merchant-status-dot[data-merchant-id="11"]');
  const add = page.locator('.nh-add-standard-btn');
  await expect(dot).toBeHidden();
  await expect(add).toBeDisabled();
  expect(await page.evaluate(() => (window as any).intervals[0].delay)).toBe(60000);

  await page.evaluate(() => {
    const w = window as any;
    const request = w.requests[0];
    request.success({ success: true, merchant_id: 10, operational_status: { status: 'open', label: 'Open now', message: 'Closes in 60 minutes', urgency: '' } });
    request.complete();
  });
  await expect(banner.locator('strong')).toHaveText('Open now');
  await expect(dot).toBeVisible();
  await expect(otherDot).toBeHidden();
  await expect(add).toBeEnabled();
  await expect(page.locator('.nh-customize-trigger')).toBeDisabled();

  await page.evaluate(() => {
    const w = window as any;
    document.getElementById('nh-merchant-operational-status')!.style.display = 'none';
    w.intervals[0].callback();
    w.intervals[0].callback();
  });
  expect(await page.evaluate(() => (window as any).requests.length)).toBe(2);
  await page.evaluate(() => {
    const request = (window as any).requests[1];
    request.success({ success: true, merchant_id: 10, operational_status: { status: 'closed', label: 'Closed', message: 'Opens at 9:00 AM', urgency: '' } });
    request.complete();
  });
  await expect(add).toBeDisabled();
  await expect(dot).toBeHidden();
  await expect(banner.locator('strong')).toHaveText('Closed');
  await expect(banner).toBeHidden();

  await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
  expect(await page.evaluate(() => (window as any).requests.length)).toBe(3);
  await page.evaluate(() => {
    const request = (window as any).requests[2];
    request.error({ status: 500 }, 'error');
    request.complete();
  });
  await expect(banner.locator('strong')).toHaveText('Status unavailable');
  await expect(dot).toBeHidden();
  await expect(add).toBeDisabled();

  expect(await page.evaluate(() => (window as any).retries.get(1).delay)).toBe(5000);
  await page.evaluate(() => {
    const w = window as any;
    const retry = w.retries.get(1);
    w.retries.delete(1);
    retry.callback();
    const request = w.requests[3];
    request.error({ status: 0 }, 'timeout');
    request.complete();
  });
  expect(await page.evaluate(() => (window as any).retries.get(2).delay)).toBe(10000);
  await page.evaluate(() => {
    const w = window as any;
    const retry = w.retries.get(2);
    w.retries.delete(2);
    retry.callback();
  });
  await page.evaluate(() => {
    const request = (window as any).requests[4];
    request.success({ success: true, merchant_id: 10, operational_status: { status: 'open', label: 'Open now', message: 'Closes in 60 minutes', urgency: '' } });
    request.complete();
  });
  await expect(add).toBeEnabled();
  await expect(dot).toBeVisible();
  await page.evaluate(() => {
    const w = window as any;
    w.intervals[0].callback();
    const request = w.requests[5];
    request.error({ status: 500 }, 'error');
    request.complete();
  });
  await expect(dot).toBeHidden();
  expect(await page.evaluate(() => (window as any).retries.get(3).delay)).toBe(5000);
  await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
  expect(await page.evaluate(() => (window as any).retries.size)).toBe(0);
  expect(await page.evaluate(() => (window as any).requests.length)).toBe(7);
});

test('initializes on a non-storefront page and stops polling after removal', async ({ page }) => {
  await page.setContent('<div class="nh-merchant-operational-status" data-merchant-id="5" data-status="closed"><strong data-status-label>Closed</strong><span data-status-message>Closed</span></div>');
  await page.evaluate(() => {
    const w = window as any;
    w.cleared = 0;
    w.retryCleared = 0;
    w.setInterval = () => 123;
    w.clearInterval = (id: number) => { if (id === 123) w.cleared++; };
    w.setTimeout = () => 456;
    w.clearTimeout = (id: number) => { if (id === 456) w.retryCleared++; };
    w.mb = { ajax: (options: any) => { w.request = options; return { abort() {} }; } };
  });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-operational-status.js') });
  await page.evaluate(() => {
    const request = (window as any).request;
    request.error({ status: 0 }, 'timeout');
    request.complete();
  });
  await page.locator('.nh-merchant-operational-status').evaluate((element) => element.remove());
  await expect.poll(() => page.evaluate(() => (window as any).cleared)).toBe(1);
  expect(await page.evaluate(() => (window as any).retryCleared)).toBe(1);
});
