import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import path from 'path';

const fixture = path.resolve('tests/fixtures/storefront-editor-render-worker.php');
const editor = execFileSync('php', [fixture], { encoding: 'utf8' }).replace(/<script>[\s\S]*?<\/script>/, '');

for (const width of [400, 1200]) {
  for (const mode of ['dayMode', 'nightMode']) {
    test(`editor footer stays visible and usable at ${width}px in ${mode}`, async ({ page }) => {
      await page.setViewportSize({ width, height: 800 });
      await page.setContent(`<html class="${mode}"><body>${editor}<div style="height:1000px"></div></body></html>`);
      await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
      await page.addStyleTag({ content: '.storefront-editor-panel select { display: block; }' });
      const panel = page.locator('.storefront-editor-panel');
      const footer = page.locator('.storefront-editor-footer');
      const status = page.locator('#storefront-editor-status');
      await status.evaluate(element => { element.textContent = 'Draft saved.'; });
      if (width > 900) {
        await page.evaluate(() => window.scrollTo(0, 200));
        await expect.poll(() => panel.evaluate(element => element.scrollHeight - element.clientHeight)).toBeGreaterThan(100);
        await panel.evaluate(element => { element.scrollTop = 100; });
      } else {
        await page.evaluate(() => window.scrollTo(0, 400));
      }
      const bounds = await footer.evaluate(element => {
        const footerRect = element.getBoundingClientRect();
        const panelRect = element.closest('.storefront-editor-panel')!.getBoundingClientRect();
        return { bottom: footerRect.bottom, expected: Math.min(panelRect.bottom - 1, window.innerHeight),
          background: getComputedStyle(element).backgroundColor };
      });
      expect(Math.abs(bounds.bottom - bounds.expected)).toBeLessThan(3);
      expect(bounds.background).toBe(mode === 'nightMode' ? 'rgb(31, 41, 55)' : 'rgb(255, 255, 255)');
      await expect(status).toBeVisible();
      await page.evaluate(() => {
        document.querySelector('#storefront-editor-save')!.addEventListener('click', () => {
          document.querySelector('#storefront-editor-status')!.textContent = 'Clicked';
        });
      });
      await page.locator('#storefront-editor-save').click();
      await expect(status).toHaveText('Clicked');
      if (width > 900) {
        await panel.evaluate(element => { element.scrollTop = element.scrollHeight; });
        await expect(page.locator('#storefront-editor-block-type')).toBeInViewport();
        await expect(footer).toBeInViewport();
        const lastControl = await page.locator('#storefront-editor-block-type').evaluate(element => {
          const control = element.getBoundingClientRect();
          const panel = element.closest('.storefront-editor-panel')!.getBoundingClientRect();
          const footer = document.querySelector('.storefront-editor-footer')!.getBoundingClientRect();
          return { top: control.top - panel.top, bottom: footer.top - control.bottom };
        });
        expect(lastControl.top).toBeGreaterThanOrEqual(0);
        expect(lastControl.bottom).toBeGreaterThanOrEqual(0);
      } else {
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await expect(footer).not.toBeInViewport();
      }
    });
  }
}
