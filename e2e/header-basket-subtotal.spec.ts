import { expect, test } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';

type Cart = {
  updateUI(): void;
  addItem(merchantId: number, product: object, receipt?: object | null, quantity?: number): void;
  changeQuantity(key: string, amount: number): void;
  updateItemCustomization(key: string, receipt: object): void;
  removeItem(key: string): void;
  clear(): void;
};

declare global {
  interface Window {
    testCart: Cart;
  }
}

const templates = [
  'html/views/components/header/header_right.php',
  'html/apps/neighborhub/views/components/header/header_right.php',
];

for (const template of templates) {
  test(`header subtotal follows the active merchant basket in ${template}`, async ({ page }) => {
    const source = readFileSync(path.resolve(template), 'utf8');
    const markup = source.match(/<a href="#" data-target="nh-shopping-cart-sidenav"[\s\S]*?<\/a>/)![0]
      .replace(/<\?=[\s\S]*?\?>/, 'waves-effect waves-light shopping-cart-sidenav-trigger accent-4 shadow-lift round-header-action');
    await page.route('https://cart-test.local/', route => route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><body></body></html>' }));
    await page.goto('https://cart-test.local/');
    await page.setContent(`
      <header class="header"><nav><div class="nav-wrapper">
        <ul class="header-left"><li><div style="display:flex;align-items:center;gap:8px;margin-left:5px">
          <a class="header-sidenav-trigger" href="#"><img width="40" height="40"></a>
          <a class="header-ce-logo-home-link hide-on-small-only" href="/">CloudEats</a>
          <button class="header-merchant-search-trigger">S</button>
        </div></li></ul>
        <ul class="header-right"><li><span class="header-merchant-brand"><a href="#" class="header-merchant-action merchant-sidenav-trigger"><img></a></span></li><li>${markup}</li><li><a href="#" class="user-badge"><i class="material-icons">account_circle</i></a></li></ul>
      </div></nav></header>
      <div id="nh-shopping-cart-sidenav"></div>
      <input id="nh-cart-address-string"><input id="nh-cart-lat"><input id="nh-cart-lon">
      <span class="nh-cart-subtotal-display"></span>
    `);
    for (const stylesheet of ['html/css/materialize.min.css', 'html/css/style.css', 'html/apps/neighborhub/css/neighborhub.css']) {
      await page.addStyleTag({ path: path.resolve(stylesheet) });
    }
    await page.evaluate(() => localStorage.setItem('mb_neighborhub_cart', JSON.stringify({
      items: {
        prod_1_: { product_id: 1, merchant_id: 7, unit_price: 10.25, base_price: 10.25, quantity: 2 },
        prod_2_: { product_id: 2, merchant_id: 8, unit_price: 50, quantity: 1 },
      },
    })));
    await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
    const cartScript = readFileSync(path.resolve('html/apps/neighborhub/js/multiTenantShoppingCart.js'), 'utf8');
    await page.addScriptTag({ content: `${cartScript}
      window.testCart = new ShoppingCart({ id: 7, delivery_assignment_mode: 'disabled' });
      window.testCart.trackUsage = function() {};
      window.testCart.updateUI();` });

    const cart = page.locator('.nh-header-cart');
    const subtotal = cart.locator('.nh-cart-subtotal-preview');
    await expect(subtotal).toHaveText('$20.50');
    await expect(subtotal).toBeVisible();
    await expect(page.locator('.nh-cart-subtotal-display')).toHaveText('$20.50');
    await expect(cart).toHaveAttribute('aria-label', 'Open basket, 2 items, subtotal $20.50');
    await expect(cart.locator('.nh-cart-count-badge')).toHaveText('2');

    for (const width of [320, 375, 768, 1024]) {
      await page.setViewportSize({ width, height: 800 });
      const positions = await page.locator('header .nav-wrapper').evaluate(wrapper => {
        const left = wrapper.querySelector('.header-left > li > div')!.getBoundingClientRect();
        const right = wrapper.querySelector('.header-right')!.getBoundingClientRect();
        const button = wrapper.querySelector('.nh-header-cart')!.getBoundingClientRect();
        const merchant = wrapper.querySelector('.merchant-sidenav-trigger')!.getBoundingClientRect();
        return { left: left.right, right: right.left, merchantRight: merchant.right, buttonLeft: button.left, buttonRight: button.right, viewport: window.innerWidth };
      });
      expect(positions.left).toBeLessThanOrEqual(positions.right);
      expect(positions.merchantRight).toBeLessThanOrEqual(positions.buttonLeft);
      expect(positions.buttonRight).toBeLessThanOrEqual(positions.viewport);
      const badge = await cart.locator('.nh-cart-count-badge').boundingBox();
      const amount = await subtotal.boundingBox();
      expect(badge).not.toBeNull();
      expect(amount).not.toBeNull();
      expect(badge!.y + badge!.height).toBeLessThanOrEqual(amount!.y);
    }
    await cart.evaluate(element => element.classList.add('hide-on-large-only'));
    await expect(cart).toBeHidden();
    await page.setViewportSize({ width: 375, height: 800 });
    await expect(cart).toBeVisible();
    await cart.evaluate(element => element.classList.remove('hide-on-large-only'));

    await page.evaluate(() => window.testCart.changeQuantity('prod_1_', 1));
    await expect(subtotal).toHaveText('$30.75');
    await page.evaluate(() => window.testCart.updateItemCustomization('prod_1_', { choices: [], final_price: 12.5 }));
    await expect(subtotal).toHaveText('$37.50');
    await page.evaluate(() => window.testCart.clear());
    await expect(subtotal).toBeHidden();
    await expect(cart).toHaveAttribute('aria-label', 'Open basket, empty');
    await expect(cart.locator('.nh-cart-count-badge')).toBeHidden();
    expect(await page.evaluate(() => JSON.parse(localStorage.getItem('mb_neighborhub_cart')!).items.prod_2_.quantity)).toBe(1);
    await page.evaluate(() => window.testCart.addItem(7, { id: 3, name: 'Free', price: 0 }));
    await expect(subtotal).toHaveText('$0.00');
    await expect(subtotal).toBeVisible();
    await expect(cart).toHaveAttribute('aria-label', 'Open basket, 1 item, subtotal $0.00');
    await page.evaluate(() => window.testCart.removeItem('prod_3_'));
    await expect(subtotal).toBeHidden();
  });
}
