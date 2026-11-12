import { test, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const headerLeft = readFileSync(path.resolve('html/views/components/header/header_left.php'), 'utf8');
const sidenavClasses = headerLeft.match(/class="(header-sidenav-trigger[^"]*)"/)![1];

for (const width of [375, 768, 1024]) {
  for (const merchantLink of [false, true]) {
    test(`centers header controls at ${width}px with merchant ${merchantLink ? 'link' : 'image'}`, async ({ page }) => {
      await page.setViewportSize({ width, height: 800 });
      await page.setContent(`
        <header class="header"><nav><div class="nav-wrapper">
          <ul class="header-left"><li>
            <div style="margin-left:5px;display:flex;align-items:center;gap:8px">
              <a class="${sidenavClasses}" href="#"><img height="40" width="40"></a>
              <a class="header-ce-logo-home-link hide-on-small-only" href="/">CloudEats</a>
              ${merchantLink ? '' : '<span class="header-merchant-brand"><img></span>'}
              <button class="header-merchant-search-trigger"><i class="fas">S</i></button>
            </div>
          </li></ul>
          <ul class="header-right">
            ${merchantLink ? '<li><span class="header-merchant-brand"><a class="merchant-sidenav-trigger" href="#"><img></a><span class="header-merchant-status-dot"></span></span></li>' : ''}
            <li><a class="round-header-action" href="#"><i class="fas">B</i></a></li>
            <li><a class="user-badge" href="#"><i class="material-icons">account_circle</i></a>
              <ul class="dropdown-content"><li><a href="#">Menu</a></li></ul>
            </li>
            <li class="hide-on-small-only"><a href="#">More</a></li>
          </ul>
        </div></nav></header>
      `);
      for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/apps/neighborhub/css/neighborhub.css']) {
        await page.addStyleTag({ path: path.resolve(stylesheet) });
      }
      await page.addStyleTag({ content: '*, *::before, *::after { transition: none !important; }' });

      for (const scrolled of [false, true]) {
        await page.evaluate((value) => document.body.classList.toggle('scrolled', value), scrolled);
        const centers = await page.locator('header .nav-wrapper').evaluate((wrapper) => {
          const center = (element: Element) => {
            const rect = element.getBoundingClientRect();
            return rect.top + rect.height / 2;
          };
          return {
            wrapper: center(wrapper),
            controls: [
              '.header-left', '.header-right', '.header-sidenav-trigger img',
              '.header-merchant-brand img', '.header-merchant-search-trigger',
              '.header-right > li > a',
            ].map((selector) => ({ selector, center: center(wrapper.querySelector(selector)!) })),
          };
        });
        for (const control of centers.controls) {
          expect(Math.abs(control.center - centers.wrapper), control.selector).toBeLessThan(1);
        }
        if (merchantLink) {
          const positions = await page.locator('.header-right').evaluate(right => {
            const rect = (selector: string) => right.querySelector(selector)!.getBoundingClientRect();
            const trigger = rect('.merchant-sidenav-trigger');
            const image = rect('.merchant-sidenav-trigger img');
            const dot = rect('.header-merchant-status-dot');
            const basket = rect('.round-header-action');
            const account = rect('.user-badge');
            return {
              triggerWidth: trigger.width,
              triggerHeight: trigger.height,
              leftInset: image.left - trigger.left,
              rightInset: trigger.right - image.right,
              firstGap: basket.left - trigger.right,
              secondGap: account.left - basket.right,
              dotRight: dot.right - image.right,
              dotBottom: dot.bottom - image.bottom,
            };
          });
          expect(positions.triggerWidth).toBe(50);
          expect(positions.triggerHeight).toBe(50);
          expect(Math.abs(positions.leftInset - positions.rightInset)).toBeLessThan(1);
          expect(Math.abs(positions.firstGap - positions.secondGap)).toBeLessThan(1);
          expect(Math.abs(positions.dotRight)).toBeLessThanOrEqual(2);
          expect(Math.abs(positions.dotBottom)).toBeLessThanOrEqual(2);
        }
      }
      await expect(page.locator('.header-right > li.hide-on-small-only')).toHaveCSS('display', width <= 600 ? 'none' : 'flex');
    });
  }
}
