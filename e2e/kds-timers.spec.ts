import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const kitchenScript = path.resolve('html/apps/neighborhub/js/kitchen.functions.js');
const screenTemplate = readFileSync(
  path.resolve('html/apps/neighborhub/views/pages/merchant/merchant_screen.php'),
  'utf8',
);
const screenScript = screenTemplate.match(/<script>([\s\S]*?)<\/script>/)![1]
  .replace(/<\?php[\s\S]*?\?>/g, '42');

declare global {
  interface Window {
    kdsTestRequests: Array<{ id?: string; reason?: string; url?: string; data?: string }>;
    startKdsTimers: () => void;
  }
}

for (const timezoneId of ['America/Indiana/Indianapolis', 'Asia/Tokyo']) {
  test.describe(`KDS timers in ${timezoneId}`, () => {
    test.use({ timezoneId });

    for (const screen of ['dashboard', 'standalone']) {
      test(`${screen} tracks elapsed time and safely handles timeout and invalid dates`, async ({ page }) => {
        await page.clock.install({ time: new Date('2026-03-08T06:04:58Z') });
        await page.setContent(`
          <section data-orders-section="pending">
            <div class="nh-order-card" data-order-id="1" data-created-at="2026-03-08 01:59:59"><div class="nh-card-header"></div></div>
            <div class="nh-order-card" data-order-id="2" data-created-at="2026-03-08 02:05:00"><div class="nh-card-header"></div></div>
            <div class="nh-order-card" data-order-id="3" data-created-at="2026-02-30 01:00:00"><div class="nh-card-header"></div></div>
            <div class="nh-order-card" data-order-id="4"><div class="nh-card-header"></div></div>
          </section>
          <section data-orders-section="confirmed">
            <div class="nh-order-card" data-order-id="5" data-created-at="2026-03-08 01:52:58"><div class="nh-card-header"></div></div>
            <div class="nh-order-card" data-order-id="6" data-created-at="2026-03-08 01:44:58"><div class="nh-card-header"></div></div>
          </section>
        `);
        await page.evaluate(() => {
          Object.assign(window, {
            kdsTestRequests: [],
            $: (header: Element) => ({ prepend: (badge: Element) => header.prepend(badge) }),
            mb: { ajax: (request: { url: string; data: string }) => window.kdsTestRequests.push(request) },
            M: { toast: () => {} },
          });
        });
        if (screen === 'standalone') {
          await page.addScriptTag({ content: screenScript });
        } else {
          await page.evaluate(() => {
            Object.assign(window, {
              denyOrder: (id: string, reason: string) => window.kdsTestRequests.push({ id, reason }),
            });
          });
        }
        await page.addScriptTag({ path: kitchenScript });
        await page.evaluate(() => window.startKdsTimers());

        await page.clock.runFor(1000);
        await expect(page.locator('[data-order-id="1"] .kds-timer-badge')).toHaveText('5:00');
        await expect(page.locator('[data-order-id="1"] .kds-timer-badge')).toHaveCSS('background-color', 'rgb(56, 142, 60)');
        await expect(page.locator('[data-order-id="5"] .kds-timer-badge')).toHaveCSS('background-color', 'rgb(251, 192, 45)');
        await expect(page.locator('[data-order-id="6"] .kds-timer-badge')).toHaveCSS('background-color', 'rgb(211, 47, 47)');
        for (const id of [2, 3, 4]) {
          await expect(page.locator(`[data-order-id="${id}"] .kds-timer-badge`)).toHaveText('Time unavailable');
        }
        const requests = await page.evaluate(() => window.kdsTestRequests);
        expect(requests).toHaveLength(1);
        if (screen === 'standalone') {
          expect(requests[0].url).toBe('/?api=neighborhub&action=deny_order');
          expect(JSON.parse(requests[0].data!)).toEqual({
            order_id: '1',
            merchant_id: 42,
            cancellation_reason: 'Auto-cancelled: Unconfirmed timeout threshold exceeded',
          });
        } else {
          expect(requests[0].id).toBe('1');
        }

        await page.evaluate(() => {
          document.querySelector('[data-orders-section="confirmed"]')!.insertAdjacentHTML(
            'beforeend',
            '<div class="nh-order-card" data-order-id="7" data-created-at="2026-03-08 02:04:59"><div class="nh-card-header"></div></div>',
          );
        });
        await page.clock.runFor(2000);
        await expect(page.locator('[data-order-id="7"] .kds-timer-badge')).toHaveText('0:02');
        await expect(page.locator('[data-order-id="2"] .kds-timer-badge')).toHaveText('0:02');
        expect(await page.evaluate(() => window.kdsTestRequests.length)).toBe(1);
      });
    }
  });
}

test('pending timeout starts at five minutes, not before', async ({ page }) => {
  await page.clock.install({ time: new Date('2026-11-01T06:04:57Z') });
  await page.setContent('<section data-orders-section="pending"><div class="nh-order-card" data-order-id="8" data-created-at="2026-11-01 01:59:59"><div class="nh-card-header"></div></div></section>');
  await page.evaluate(() => {
    Object.assign(window, {
      kdsTestRequests: [],
      $: (header: Element) => ({ prepend: (badge: Element) => header.prepend(badge) }),
      denyOrder: (id: string) => window.kdsTestRequests.push({ id }),
    });
  });
  await page.addScriptTag({ path: kitchenScript });
  await page.evaluate(() => window.startKdsTimers());
  await page.clock.runFor(1000);
  await expect(page.locator('.kds-timer-badge')).toHaveText('4:59');
  expect(await page.evaluate(() => window.kdsTestRequests.length)).toBe(0);
  await page.clock.runFor(1000);
  await expect(page.locator('.kds-timer-badge')).toHaveText('5:00');
  expect(await page.evaluate(() => window.kdsTestRequests)).toEqual([{ id: '8' }]);
});
