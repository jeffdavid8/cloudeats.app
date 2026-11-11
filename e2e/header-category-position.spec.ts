import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

const left = readFileSync(path.resolve('html/views/components/header/header_left.php'), 'utf8');
const script = left.match(/<script>([\s\S]*?)<\/script>/)![1];
const header = readFileSync(path.resolve('html/views/components/header/header.php'), 'utf8');
const panel = header.match(/<div id="header-merchant-search-panel"[\s\S]*?<\/div>\s*<\?php endif;/)![0]
  .replace(/<\?php endif;$/, '').replace(/<\?=[\s\S]*?\?>/, 'Example merchant');

test('floating categories clear the changing header only while search is open', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 800 });
  await page.setContent(`
    <header class="header"><nav>
      <div class="nav-wrapper"><ul class="header-left"><li>
        <button class="header-merchant-search-trigger" aria-expanded="false">Search</button>
      </li></ul></div>
      <div class="nh-merchant-operational-status">Open now</div>${panel}
    </nav></header>
    <div class="merchant-menu-dropdown"><div class="top-right-dropdown-wrapper">
      <a href="#" class="dropdown-trigger" data-target="top-right-menu">Categories</a>
      <ul id="top-right-menu" class="dropdown-content"><li><a href="#pizza">Pizza</a></li></ul>
    </div></div>
    <main style="padding-top:300px;height:2000px">
      <h4 class="custom-section-title">Pizza</h4>
      <div class="row"><div class="col"><div class="product-item-card">Pepperoni pizza</div></div></div>
    </main>
  `);
  for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/apps/neighborhub/css/merchant-operational-status.css']) {
    await page.addStyleTag({ path: path.resolve(stylesheet) });
  }
  await page.addStyleTag({ content: `
    .top-right-dropdown-wrapper { position:fixed; top:8rem; right:1rem; z-index:885; }
    body.scrolled .top-right-dropdown-wrapper { top:7rem; }
  ` });
  await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
  await page.addScriptTag({ path: path.resolve('html/js/materialize.min.js') });
  await page.addScriptTag({ content: script });
  await expect.poll(() => page.evaluate(() => (window as Window & { jQuery?: { isReady: boolean } }).jQuery?.isReady)).toBe(true);
  await page.addScriptTag({ content: "M.Dropdown.init(document.querySelector('.dropdown-trigger'), { constrainWidth: false });" });

  const search = page.locator('#header-merchant-search-input');
  const categories = page.locator('.top-right-dropdown-wrapper');
  const getPositions = () => page.evaluate(() => ({
    header: document.querySelector('header.header')!.getBoundingClientRect().bottom,
    categories: document.querySelector('.top-right-dropdown-wrapper')!.getBoundingClientRect().top,
  }));
  const assertClearance = async () => {
    await expect.poll(async () => {
      const positions = await getPositions();
      return positions.categories - positions.header;
    }).toBeGreaterThanOrEqual(7.5);
  };
  const originalTop = (await getPositions()).categories;
  await page.locator('.header-merchant-search-trigger').click();
  await assertClearance();
  const openedTop = (await getPositions()).categories;
  expect(openedTop).toBeGreaterThan(originalTop);
  await search.fill('no matching product');
  await expect(page.locator('#header-merchant-search-no-results')).toBeVisible();
  await assertClearance();
  expect((await getPositions()).categories).toBeGreaterThan(openedTop);

  await page.locator('.nh-merchant-operational-status').evaluate(el => { (el as HTMLElement).style.display = 'none'; });
  await assertClearance();
  await expect.poll(async () => (await getPositions()).categories).toBeLessThan(openedTop);
  await page.locator('.nh-merchant-operational-status').evaluate(el => { (el as HTMLElement).style.display = ''; });
  await assertClearance();

  await page.locator('.dropdown-trigger').click();
  await expect(page.locator('#top-right-menu')).toBeVisible();
  await page.keyboard.press('Escape');
  await page.evaluate(() => {
    document.body.classList.add('scrolled');
    window.dispatchEvent(new Event('scroll'));
  });
  await assertClearance();
  await page.setViewportSize({ width: 768, height: 800 });
  await assertClearance();
  await page.locator('.header-merchant-search-cancel').click();
  await expect.poll(async () => (await getPositions()).categories).toBe(112);
  await expect(categories).not.toHaveClass(/nh-search-positioned-category/);
});
