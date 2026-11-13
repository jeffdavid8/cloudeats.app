import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

test('signup initializes without reCAPTCHA and verifies when it becomes available', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.setContent(`
    <button id="btn-header-signup" class="btn-signup">Sign up</button>
    <div id="neighborhub-signup-modal" data-recaptcha-site-key="test-site-key">
      <form id="neighborhub-signup-form">
        <input name="name" value="Example" required>
        <input name="email" type="email" value="test@example.com" required>
        <input name="password" type="password" value="long-password" required>
        <input name="password_confirmation" type="password" value="long-password" required>
        <p id="neighborhub-signup-message"></p>
        <button id="neighborhub-signup-submit" type="submit">Create account</button>
      </form>
    </div>
  `);
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ content: `
    window.testState = { opens: 0, loading: [], requests: [], fail: false };
    window.M = { Modal: { init: () => ({ open: () => window.testState.opens++ }) }, toast: () => {} };
    window.loading = value => window.testState.loading.push(value);
    window.fireConfettiCannon = () => {};
    window.mb = { ajax: options => {
      window.testState.requests.push(JSON.parse(options.data));
      if (window.testState.fail) options.error({ responseJSON: { error: 'Registration failed.' } });
      else options.success({ message: 'Verification email sent.' });
    } };
  ` });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/signup.js') });
  await page.locator('#btn-header-signup').click();
  await expect.poll(() => page.evaluate(() => (window as any).testState.opens)).toBe(1);
  await page.locator('#neighborhub-signup-submit').click();
  await expect(page.locator('#neighborhub-signup-message')).toHaveText('Signup verification is temporarily unavailable. Please try again later.');
  expect(await page.evaluate(() => (window as any).testState.requests.length)).toBe(0);

  await page.evaluate(() => {
    (window as any).grecaptcha = {
      ready: (callback: () => void) => callback(),
      execute: (key: string, options: { action: string }) => {
        (window as any).testState.recaptcha = { key, action: options.action };
        return Promise.resolve('test-token');
      },
    };
  });
  await page.locator('#neighborhub-signup-submit').click();
  await expect(page.locator('#neighborhub-signup-message')).toHaveText('Verification email sent.');
  expect(await page.evaluate(() => (window as any).testState.recaptcha)).toEqual({ key: 'test-site-key', action: 'neighborhub_signup' });
  expect(await page.evaluate(() => (window as any).testState.requests[0].recaptcha_token)).toBe('test-token');
  await page.locator('[name=name]').fill('Example');
  await page.locator('[name=email]').fill('test@example.com');
  await page.locator('[name=password]').fill('long-password');
  await page.locator('[name=password_confirmation]').fill('long-password');
  await page.evaluate(() => { (window as any).testState.fail = true; });
  await page.locator('#neighborhub-signup-submit').click();
  await expect(page.locator('#neighborhub-signup-message')).toHaveText('Registration failed.');
  await expect(page.locator('#neighborhub-signup-submit')).toBeEnabled();
  expect(await page.evaluate(() => (window as any).testState.loading)).toEqual([2, 0, 2, 0]);
  await page.evaluate(() => {
    (window as any).grecaptcha.execute = () => Promise.reject(new Error('Verification failed'));
  });
  await page.locator('#neighborhub-signup-submit').click();
  await expect(page.locator('#neighborhub-signup-message')).toHaveText('We could not complete signup. Please try again.');
  await expect(page.locator('#neighborhub-signup-submit')).toBeEnabled();
  expect(await page.evaluate(() => (window as any).testState.requests.length)).toBe(2);
  expect(await page.evaluate(() => (window as any).testState.loading)).toEqual([2, 0, 2, 0, 2, 0]);
  expect(errors).toEqual([]);
});

test('shared ajax uses browser user agent and preserves security headers', async ({ page }) => {
  const source = readFileSync(path.resolve('html/js/mediabrain.js'), 'utf8');
  const ajax = source.slice(source.indexOf('mb.ajax = function'), source.indexOf('// Shorthand for a mb.ajax() post request'));
  const warnings: string[] = [];
  page.on('console', message => { if (message.text().includes('Refused to set unsafe header')) warnings.push(message.text()); });
  await page.route('https://ajax-test.local/**', route => route.fulfill({ contentType: 'application/json', body: '{}' }));
  await page.goto('https://ajax-test.local/');
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ content: `var mb = { csrf_token: 'test-token' }; ${ajax}` });
  const request = page.waitForRequest(request => request.url().endsWith('/test'));
  await page.evaluate(() => (window as any).mb.ajax({ url: '/test' }));
  const headers = (await request).headers();
  expect(headers['x-csrf-token']).toBe('test-token');
  expect(headers.authorization).toBe('Bearer test-token');
  expect(headers['x-requested-with']).toBe('XMLHttpRequest');
  expect(headers['user-agent']).not.toBe('cloudeats.app');
  expect(warnings).toEqual([]);
});
