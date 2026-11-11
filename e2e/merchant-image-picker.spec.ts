import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import path from 'path';

const html = execFileSync('php', [path.resolve('tests/fixtures/merchant-image-picker-render-worker.php')], { encoding: 'utf8' });

test.beforeEach(async ({ page }) => {
  await page.setContent(html);
  await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
  await page.addStyleTag({ path: path.resolve('html/apps/neighborhub/css/merchant-image-picker.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-image-picker.js') });
  await page.evaluate(() => {
    (window as any).statuses = [];
    (window as any).changes = [];
    (window as any).pickers = [];
    document.querySelectorAll('[data-image-picker]').forEach(element => {
      const Picker = (0, eval)('MerchantImagePicker');
      const picker = new Picker(element, {
        merchantId: 8,
        onStatus: (message: string, error: boolean) => (window as any).statuses.push({ message, error }),
        onChange: (value: string) => (window as any).changes.push(value),
        onUpload: (image: object) => (window as any).pickers.forEach((item: any) => item.addImage(image)),
      });
      (window as any).pickers.push(picker);
    });
  });
});

test('shows gallery thumbnails and changes the selected preview', async ({ page }) => {
  const picker = page.locator('[data-image-picker]').first();
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/profile.png');
  await expect(picker.locator('.dropdown-content li img')).toHaveCount(3);
  await picker.locator('.select-dropdown.dropdown-trigger').click();
  await picker.locator('.dropdown-content li').filter({ hasText: 'Image 52' }).click();
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/two.png');
  await expect(picker.locator('select')).toHaveValue('52');
  expect(await page.evaluate(() => (window as any).changes)).toEqual(['52']);
});

test('upload adds a selectable thumbnail to every picker and surfaces errors', async ({ page }) => {
  await page.evaluate(() => {
    (window as any).mb = { ajax: (options: any) => {
      (window as any).request = options;
    } };
  });
  const picker = page.locator('[data-image-picker]').first();
  await picker.locator('input[type=file]').setInputFiles({
    name: 'logo.png', mimeType: 'image/png', buffer: Buffer.from('fake image'),
  });
  expect(await page.evaluate(() => (window as any).pickers[0].uploading)).toBe(true);
  await page.evaluate(() => {
    const request = (window as any).request;
    request.success({ success: true, image: { id: 53, image_url: '/three.png' } });
    request.complete();
  });
  await expect(picker.locator('select')).toHaveValue('53');
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/three.png');
  await expect(page.locator('[data-image-picker]').nth(1).locator('option[value="53"]')).toHaveCount(1);
  await expect(page.locator('[data-image-picker]').nth(1).locator('.dropdown-content li img')).toHaveCount(4);
  expect(await page.evaluate(() => (window as any).changes)).toEqual(['53']);
  await picker.locator('input[type=file]').setInputFiles({
    name: 'bad.png', mimeType: 'image/png', buffer: Buffer.from('bad'),
  });
  await page.evaluate(() => {
    const request = (window as any).request;
    request.error({ responseJSON: { error: 'Upload failed' } });
    request.complete();
  });
  expect(await page.evaluate(() => (window as any).statuses.at(-1))).toEqual({ message: 'Upload failed', error: true });
  await expect(picker.locator('select')).toHaveValue('53');
});

test('accepts a dropped file', async ({ page }) => {
  await page.evaluate(() => {
    (window as any).mb = { ajax: (options: any) => { (window as any).request = options; } };
    const transfer = new DataTransfer();
    transfer.items.add(new File(['image'], 'dropped.png', { type: 'image/png' }));
    document.querySelector('[data-image-dropzone]')!.dispatchEvent(new DragEvent('drop', {
      bubbles: true, dataTransfer: transfer,
    }));
  });
  expect(await page.evaluate(() => (window as any).request.data.get('asset').name)).toBe('dropped.png');
  expect(await page.evaluate(() => (window as any).request.data.get('merchant_id'))).toBe('8');
});

test('editor picker posts selected gallery image to the live preview and draft', async ({ page }) => {
  const editor = execFileSync('php', [path.resolve('tests/fixtures/storefront-editor-render-worker.php')], { encoding: 'utf8' });
  const script = editor.match(/<script>\s*(\(function\(\)[\s\S]*?)<\/script>/)![1];
  await page.setContent(editor.replace(/<script[^>]*>[\s\S]*?<\/script>/g, ''));
  await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-image-picker.js') });
  await page.evaluate(() => {
    (window as any).mb = { ajax: (options: any) => { (window as any).request = options; } };
    const preview = (document.querySelector('#storefront-editor-preview') as HTMLIFrameElement).contentWindow!;
    preview.postMessage = (data) => { (window as any).previewMessage = data; };
  });
  await page.addScriptTag({ content: script });
  const logo = page.locator('#storefront-editor-logo');
  await logo.evaluate(element => {
    (element as HTMLSelectElement).value = '51';
    element.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await expect(page.locator('#storefront-editor-logo').locator('..').locator('.dropdown-content li img')).toHaveCount(3);
  await expect(page.locator('[data-image-picker]').first().locator('[data-image-preview]')).toHaveAttribute('src', '/one.png');
  expect(await page.evaluate(() => (window as any).previewMessage.configuration.assets.logo_id)).toBe(51);
  await page.locator('#storefront-editor-save').click();
  const saved = await page.evaluate(() => JSON.parse((window as any).request.data));
  expect(saved.configuration.assets.logo_id).toBe(51);
});

test('store settings save the selected profile image only on submit', async ({ page }) => {
  const management = execFileSync('php', [path.resolve('tests/fixtures/merchant-management-render-worker.php')], { encoding: 'utf8' });
  const scripts = [...management.matchAll(/<script>([\s\S]*?)<\/script>/g)];
  const script = scripts[scripts.length - 1][1];
  await page.setContent(management.replace(/<script[^>]*>[\s\S]*?<\/script>/g, ''));
  await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchant-image-picker.js') });
  await page.evaluate(() => {
    (window as any).requests = [];
    (window as any).mb = { ajax: (options: any) => { (window as any).requests.push(options); } };
  });
  await page.addScriptTag({ content: script });
  await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
  const picker = page.locator('#settings-form [data-image-picker]');
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/profile.png');
  await expect(picker.locator('.dropdown-content li img')).toHaveCount(3);
  await picker.locator('select').evaluate(element => {
    (element as HTMLSelectElement).value = '51';
    element.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/one.png');
  expect(await page.evaluate(() => (window as any).requests.filter((request: any) => request.url.includes('update_merchant_settings')).length)).toBe(0);
  await page.evaluate(() => (0, eval)('saveSettings')({ preventDefault() {}, stopPropagation() {} }));
  const saved = await page.evaluate(() => {
    const request = (window as any).requests.find((item: any) => item.url.includes('update_merchant_settings'));
    return JSON.parse(request.data);
  });
  expect(saved.profile_image_id).toBe('51');
  await picker.locator('select').evaluate(element => {
    (element as HTMLSelectElement).value = '52';
    element.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await page.evaluate(() => {
    const request = (window as any).requests.find((item: any) => item.url.includes('update_merchant_settings'));
    request.success({ success: true });
  });
  await expect(picker.locator('select')).toHaveValue('52');
  await expect(picker.locator('[data-image-preview]')).toHaveAttribute('src', '/two.png');
  await expect(picker.locator('option[value=""]')).toHaveAttribute('data-image-url', '/one.png');
});
