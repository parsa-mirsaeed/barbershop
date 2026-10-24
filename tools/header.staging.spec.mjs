/**
 * Run only against a real WordPress staging installation of this branch.
 * Set MONTIX_STAGING_URL and, if staging uses HTTP Basic, both BASIC credentials.
 * No mock DOM is used; tests fail if the deployed template is overridden in Site Editor.
 */
import { test, expect } from '@playwright/test';

const site = process.env.MONTIX_STAGING_URL;
if (!site) throw new Error('MONTIX_STAGING_URL is required for real-browser acceptance');
if (!new URL(site).hostname.startsWith('staging.')) {
  throw new Error('Refusing to run storefront interaction tests against a non-staging host');
}
const user = process.env.MONTIX_STAGE_BASIC_USER;
const password = process.env.MONTIX_STAGE_BASIC_PASSWORD;
test.use({
  ...(user && password ? { httpCredentials: { username: user, password } } : {}),
  screenshot: 'only-on-failure',
  trace: 'retain-on-failure'
});

for (const width of [320, 360, 390, 768, 900, 1024, 1280, 1440, 1768]) {
  test(`responsive header at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(site, { waitUntil: 'domcontentloaded' });
    const header = page.locator('.site-header-shell');
    await expect(header).toBeVisible();
    await expect(header.locator('.pbs-header-actions .bsc-account-link')).toHaveCount(1);
    await expect(header.locator('.pbs-header-actions .bsc-cart-link')).toHaveCount(1);
    await expect(header.locator('form.pbs-header-product-search')).toHaveCount(1);
    await expect(header.locator('.pbs-brand-lockup .wp-block-site-logo')).toBeVisible();
    const height = await header.evaluate((element) => element.getBoundingClientRect().height);
    expect(height, 'Unexpected blank second header row').toBeLessThan(width <= 781 ? 102 : 125);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
      'Page must not scroll horizontally').toBe(true);
    if (width <= 781) {
      const toggle = header.locator('.pbs-search-toggle');
      await expect(toggle).toBeVisible();
      const panel = header.locator('#pbs-header-search-panel');
      await expect(panel).toBeHidden();
      await toggle.click();
      await expect(toggle).toHaveAttribute('aria-expanded', 'true');
      await expect(panel).toBeVisible();
      await panel.locator('input[type="search"]').fill('تیغ');
      expect(await panel.locator('input[type="search"]').inputValue()).toBe('تیغ');
      await page.keyboard.press('Escape');
      await expect(panel).toBeHidden();
      await expect(toggle).toBeFocused();
    }
  });
}

test('Persian product-only search submits unmodified Unicode', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 800 });
  await page.goto(site, { waitUntil: 'domcontentloaded' });
  await page.locator('.pbs-search-toggle').click();
  const field = page.locator('.pbs-header-product-search input[type="search"]');
  await field.fill('تیغ');
  await Promise.all([
    page.waitForURL((url) => url.searchParams.get('s') === 'تیغ'),
    field.press('Enter'),
  ]);
  const url = new URL(page.url());
  expect(url.searchParams.get('s')).toBe('تیغ');
  expect(url.searchParams.get('post_type')).toBe('product');
});
