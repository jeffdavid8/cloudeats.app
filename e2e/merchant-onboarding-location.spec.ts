import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const source = readFileSync(path.resolve('html/apps/neighborhub/views/pages/customer/merchant_onboarding.php'), 'utf8');
const script = source.match(/<!-- Wizard Frontend Controller Script -->\s*<script>([\s\S]*?)<\/script>/)![1]
  .replace(/<\?= json_encode\([\s\S]*?\) \?>/, '"test-site-key"');

test('location controls remain usable when reCAPTCHA is unavailable', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.setContent(`
    <form id="merchant-onboarding-form">
      <input id="business_name" value="Example"><input id="phone" value="123">
      <input id="type" value="food"><input id="address"><ul id="wizard-address-suggestions"></ul>
      <input id="latitude"><input id="longitude"><input id="delivery_max_distance" value="7">
      <input id="store_hours"><input id="delivery_assignment_mode">
      <button type="button" id="btn-wizard-gps">Use Current GPS</button>
      <button type="button" id="btn-open-map-picker" class="modal-trigger" data-target="map-picker-modal">Pick on Map</button>
      <button id="btn-submit-merchant" type="submit">Complete Registration</button>
    </form>
    <div id="map-picker-modal" class="modal"><div id="osm-picker-map"></div>
      <button type="button" id="btn-confirm-map-location" class="modal-close">Confirm Location</button>
    </div>
  `);
  await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.addScriptTag({ content: `
    window.testState = { toasts: [], requests: [], selected: null };
    M.toast = options => window.testState.toasts.push(options.html);
    M.updateTextFields = () => {};
    window.loading = () => {};
    window.fireConfettiCannon = () => {};
    window.mb = { ajax: options => {
      window.testState.requests.push({ url: options.url, data: options.data });
      if (options.url.includes('reverse_geocode_proxy')) options.success({ display_name: 'Selected address' });
      else { options.success({ success: false, error: 'Test response' }); options.complete(); }
    } };
    Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
      getCurrentPosition: callback => callback({ coords: { latitude: 40.1, longitude: -85.2 } })
    } });
    const map = { setView() { return this; }, on(event, callback) {
      if (event === 'click') window.testState.selectMap = callback;
      return this;
    }, invalidateSize() {} };
    window.L = {
      map: () => map,
      tileLayer: () => ({ addTo() {} }),
      marker: coords => ({ addTo() { return this; }, on() {}, setLatLng(next) { window.testState.selected = next; } })
    };
  ` });
  await page.addScriptTag({ content: script });
  await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));

  await page.locator('#btn-wizard-gps').click();
  await expect(page.locator('#latitude')).toHaveValue('40.1');
  await expect(page.locator('#longitude')).toHaveValue('-85.2');
  await expect(page.locator('#address')).toHaveValue('Selected address');
  await page.locator('#btn-open-map-picker').click();
  await expect(page.locator('#map-picker-modal')).toBeVisible();
  await expect.poll(() => page.evaluate(() => typeof (window as any).testState.selectMap)).toBe('function');
  await page.evaluate(() => (window as any).testState.selectMap({ latlng: { lat: 41.25, lng: -86.5 } }));
  await page.locator('#btn-confirm-map-location').click();
  await expect(page.locator('#latitude')).toHaveValue('41.250000');
  await expect(page.locator('#longitude')).toHaveValue('-86.500000');
  await expect(page.locator('#address')).toHaveValue('Selected address');
  await page.evaluate(() => {
    Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
      getCurrentPosition: (_success: unknown, failure: (error: object) => void) => failure({ code: 1 }),
    } });
  });
  await page.locator('#btn-wizard-gps').click();
  expect(await page.evaluate(() => (window as any).testState.toasts)).toContain('Unable to retrieve your location. Please check browser permissions.');
  await expect(page.locator('#latitude')).toHaveValue('41.250000');

  await page.locator('#btn-submit-merchant').click();
  expect(await page.evaluate(() => (window as any).testState.toasts)).toContain('Security verification is unavailable. Please try again later.');
  await expect(page.locator('#btn-submit-merchant')).toBeEnabled();

  await page.evaluate(() => {
    (window as any).grecaptcha = {
      ready: (callback: () => void) => callback(),
      execute: () => Promise.reject(new Error('Verification unavailable')),
    };
  });
  await page.locator('#btn-submit-merchant').click();
  await expect.poll(() => page.evaluate(() => (window as any).testState.toasts)).toContain('Security verification failed. Please try again.');
  await expect(page.locator('#btn-submit-merchant')).toBeEnabled();
  await page.evaluate(() => {
    (window as any).grecaptcha.execute = (key: string, options: { action: string }) => {
      (window as any).testState.verification = { key, action: options.action };
      return Promise.resolve('verified-token');
    };
  });
  await page.locator('#btn-submit-merchant').click();
  await expect.poll(() => page.evaluate(() => (window as any).testState.toasts)).toContain('Test response');
  expect(await page.evaluate(() => (window as any).testState.verification)).toEqual({ key: 'test-site-key', action: 'neighborhub_signup' });
  const requests = await page.evaluate(() => (window as any).testState.requests);
  const submissions = requests.filter((request: { url: string }) => request.url.includes('submit_merchant_application'));
  expect(submissions).toHaveLength(1);
  expect(JSON.parse(submissions[0].data).recaptcha_token).toBe('verified-token');
  await expect(page.locator('#btn-submit-merchant')).toBeEnabled();
  expect(errors).toEqual([]);
});
