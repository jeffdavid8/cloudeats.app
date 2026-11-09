import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import { readFileSync } from 'fs';
import path from 'path';

const root = path.resolve('html/apps/neighborhub/views/storefront');
const fixture = path.resolve('tests/fixtures/merchant-hero-render-worker.php');
const layouts = ['hero-centered', 'hero-split', 'hero-minimal', 'hero-full'];
const defaultCss = readFileSync(path.join(root, 'themes', 'default.php'), 'utf8').match(/<style>([\s\S]*?)<\/style>/)![1]
  .replace(/<\?php[\s\S]*?\?>/g, '')
  .replace(/<\?=[\s\S]*?\?>/g, '#ffffff');

for (const theme of ['default', 'pizza-king', 'bakery', 'diner']) {
  for (const width of [400, 1024]) {
    test(`${theme} hero layouts render and preview at ${width}px`, async ({ page }) => {
      await page.setViewportSize({ width, height: 800 });
      const html = execFileSync('php', [fixture, 'hero-centered'], { encoding: 'utf8' });
      await page.setContent(`<div class="container" style="width:70%;margin:auto">${html}</div>`);
      await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
      await page.addStyleTag({ content: defaultCss });
      if (theme !== 'default') {
        const themeCss = readFileSync(path.join(root, 'themes', `${theme}.php`), 'utf8')
          .match(/<style>([\s\S]*?)<\/style>/)![1].replace(/<\?=[\s\S]*?\?>/g, '');
        await page.addStyleTag({ content: themeCss });
      }
      await page.addScriptTag({ path: path.resolve('html/js/jquery-2.1.1.min.js') });
      await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchantStorefront.js') });
      await page.evaluate(() => {
        document.body.dataset.storefrontPreview = 'true';
        const storefront = Object.create((0, eval)('MerchantStorefront').prototype);
        storefront.bindDraftPreviewMessages();
        storefront.initHeroParallax();
      });

      for (const layout of layouts) {
        const rendered = execFileSync('php', [fixture, layout], { encoding: 'utf8' });
        expect(rendered).toContain(`data-storefront-layout="${layout}"`);
        await page.evaluate((value) => window.dispatchEvent(new MessageEvent('message', {
          origin: window.location.origin,
          data: { type: 'merchant-storefront-draft', configuration: {
            layout: value, assets: { hero_id: 1 }, asset_urls: { 1: '/hero.jpg' },
          } },
        })), layout);
        const hero = page.locator('.merchant-storefront-hero');
        await expect(hero).toHaveAttribute('data-storefront-layout', layout);
        await expect(hero).toHaveCSS('flex-direction',
          layout === 'hero-centered' || layout === 'hero-full' ? 'column' : 'row');
        await expect(hero.locator('img')).toHaveCSS('width',
          layout === 'hero-full' ? '250px' : layout === 'hero-minimal' ? '48px' : layout === 'hero-split' && width <= 600 ? '56px' : '72px');
        await expect(hero).toHaveCSS('text-align',
          layout === 'hero-centered' || layout === 'hero-full' ? 'center' : 'left');
        if (layout === 'hero-full') {
          const metrics = await hero.evaluate(element => {
            const rect = element.getBoundingClientRect();
            const style = getComputedStyle(element);
            return { left: rect.left, width: rect.width, height: rect.height, background: style.backgroundImage,
              size: style.backgroundSize, justify: style.justifyContent, align: style.alignItems };
          });
          expect(Math.abs(metrics.left)).toBeLessThan(1);
          expect(Math.abs(metrics.width - width)).toBeLessThan(1);
          expect(metrics.height).toBeGreaterThanOrEqual(600);
          expect(metrics.height).toBeLessThanOrEqual(601);
          expect(metrics.background).toContain('/hero.jpg');
          await expect(hero.locator('.merchant-storefront-hero-background')).toHaveCSS('background-image', /hero\.jpg/);
          expect(metrics.size.split(',').map(value => value.trim()).every(value => value === 'cover')).toBe(true);
          expect(metrics.justify).toBe('center');
          expect(metrics.align).toBe('center');
        }
        for (const logoStyle of ['original', 'circle', 'rectangle', 'default']) {
          await page.evaluate(({ layout, logoStyle }) => window.dispatchEvent(new MessageEvent('message', {
            origin: window.location.origin,
            data: { type: 'merchant-storefront-draft', configuration: {
              layout, logo_style: logoStyle, assets: {}, asset_urls: {},
            } },
          })), { layout, logoStyle });
          await expect(hero).toHaveAttribute('data-storefront-logo-style', logoStyle);
          const logo = hero.locator('.merchant-storefront-hero-logo');
          const size = layout === 'hero-full' ? 250 : layout === 'hero-minimal' ? 48 : layout === 'hero-split' && width <= 600 ? 56 : 72;
          if (logoStyle === 'original') {
            await expect(logo).toHaveCSS('border-top-width', '0px');
            await expect(logo).toHaveCSS('border-top-left-radius', '0px');
            await expect(logo).toHaveCSS('object-fit', 'contain');
            for (const [imageWidth, imageHeight] of [[400, 200], [200, 400]]) {
              await logo.evaluate(async (element, dimensions) => {
                const image = element as HTMLImageElement;
                image.src = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="${dimensions[0]}" height="${dimensions[1]}"></svg>`)}`;
                await image.decode();
              }, [imageWidth, imageHeight]);
              const bounds = await logo.boundingBox();
              expect(bounds).not.toBeNull();
              expect(bounds!.width).toBeLessThanOrEqual(size);
              expect(bounds!.height).toBeLessThanOrEqual(size);
              expect(bounds!.width / bounds!.height).toBeCloseTo(imageWidth / imageHeight, 1);
            }
          } else if (logoStyle === 'circle' || logoStyle === 'rectangle') {
            await expect(logo).toHaveCSS('object-fit', 'cover');
            if (logoStyle === 'circle') {
              await expect(logo).toHaveCSS('border-top-left-radius', '50%');
            } else {
              const radius = await logo.evaluate(element => parseFloat(getComputedStyle(element).borderTopLeftRadius));
              expect(radius).toBeGreaterThan(0);
              expect(radius).toBeLessThan(10);
            }
            const bounds = await logo.boundingBox();
            expect(bounds).not.toBeNull();
            expect(bounds!.width).toBeCloseTo(size, 0);
            expect(bounds!.height).toBeCloseTo(size * (logoStyle === 'circle' ? 1 : .7), 0);
          }
        }
      }
      await page.evaluate(() => window.dispatchEvent(new MessageEvent('message', {
        origin: window.location.origin,
        data: { type: 'merchant-storefront-draft', configuration: {
          layout: 'hero-full', assets: {}, asset_urls: {},
        } },
      })));
      const hero = page.locator('.merchant-storefront-hero');
      await expect(hero).toHaveCSS('background-image', /logo\.png/);
      await expect(hero.locator('.merchant-storefront-hero-background')).toHaveCSS('background-image', /logo\.png/);
      await page.evaluate(() => window.dispatchEvent(new MessageEvent('message', {
        origin: window.location.origin,
        data: { type: 'merchant-storefront-draft', configuration: {
          layout: 'hero-centered', assets: {}, asset_urls: {},
        } },
      })));
      await expect(hero).toHaveAttribute('data-storefront-layout', 'hero-centered');
      await expect(hero.locator('.merchant-storefront-hero-background')).toBeHidden();
      expect(await hero.evaluate(element => (element as HTMLElement).style.backgroundImage)).toBe('');
    });

  }
}

for (const width of [400, 1024]) {
  test(`full hero scroll reaction and reduced motion at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 800 });
    const html = execFileSync('php', [fixture, 'hero-full', 'hero-image'], { encoding: 'utf8' });
    await page.setContent(`<div class="container" style="width:70%;margin:auto">${html}</div><div style="height:2000px"></div>`);
    await page.addStyleTag({ path: path.resolve('html/css/materialize.min.css') });
    await page.addStyleTag({ content: defaultCss });
    await page.addScriptTag({ path: path.resolve('html/apps/neighborhub/js/merchantStorefront.js') });
    await page.evaluate(() => {
      const storefront = Object.create((0, eval)('MerchantStorefront').prototype);
      storefront.initHeroParallax();
    });
    const hero = page.locator('.merchant-storefront-hero');
    const layer = hero.locator('.merchant-storefront-hero-background');
    await expect.poll(() => layer.evaluate(element => getComputedStyle(element).transform)).not.toBe('none');
    const initial = await layer.evaluate(element => element.getBoundingClientRect().top);
    await page.evaluate(() => window.scrollTo(0, 200));
    await expect.poll(async () => initial - await layer.evaluate(element => element.getBoundingClientRect().top))
      .toBeGreaterThan(150);
    const movement = initial - await layer.evaluate(element => element.getBoundingClientRect().top);
    expect(movement).toBeLessThan(170);
    const bounds = await hero.evaluate(element => {
      const heroRect = element.getBoundingClientRect();
      const layerRect = element.querySelector('.merchant-storefront-hero-background')!.getBoundingClientRect();
      return { top: layerRect.top - heroRect.top, bottom: layerRect.bottom - heroRect.bottom };
    });
    expect(bounds.top).toBeLessThanOrEqual(0);
    expect(bounds.bottom).toBeGreaterThanOrEqual(0);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await expect(layer).toHaveCSS('transform', 'none');
    await expect.poll(() => hero.evaluate(element => (element as HTMLElement).style.getPropertyValue('--merchant-hero-parallax'))).toBe('');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    await expect.poll(() => hero.evaluate(element => (element as HTMLElement).style.getPropertyValue('--merchant-hero-parallax'))).not.toBe('');
    await hero.evaluate(element => element.setAttribute('data-storefront-layout', 'hero-centered'));
    await expect(layer).toBeHidden();
    await expect.poll(() => hero.evaluate(element => (element as HTMLElement).style.getPropertyValue('--merchant-hero-parallax'))).toBe('');
  });
}

test('full hero renders the selected image or falls back to the profile image', async () => {
  const withImage = execFileSync('php', [fixture, 'hero-full', 'hero-image'], { encoding: 'utf8' });
  const fallback = execFileSync('php', [fixture, 'hero-full'], { encoding: 'utf8' });
  expect(withImage).toContain("url('/hero.jpg')");
  expect(fallback).toContain("url('/logo.png')");
});

test('invalid saved layout falls back to centered', async () => {
  const html = execFileSync('php', [fixture, 'unknown'], { encoding: 'utf8' });
  expect(html).toContain('data-storefront-layout="hero-centered"');
});

test('saved logo styles render with a safe default', async () => {
  for (const style of ['default', 'original', 'circle', 'rectangle']) {
    const html = execFileSync('php', [fixture, 'hero-full', 'hero-image', style], { encoding: 'utf8' });
    expect(html).toContain(`data-storefront-logo-style="${style}"`);
  }
  const invalid = execFileSync('php', [fixture, 'hero-full', 'hero-image', 'invalid'], { encoding: 'utf8' });
  expect(invalid).toContain('data-storefront-logo-style="default"');
});
