# Responsive storefront and operations plan

This document is the implementation contract for mobile, tablet, desktop, zoom, touch, and orientation support across the Persian Barbershop storefront, WooCommerce purchase flow, customer account, and barber dashboard.

## 1. Outcome and measurable acceptance criteria

The implementation is complete only when all of the following are true:

1. Every public page and dashboard section remains usable from **320 CSS px** upward without page-level horizontal scrolling. A data table may use a clearly bounded local scroll region only when the table's two-dimensional relationship is essential.
2. No content, action, field, price, label, notice, payment method, product image, or navigation control is clipped at supported widths.
3. All primary touch controls use a project baseline of **at least 44 by 44 CSS px**. This is intentionally stronger than WCAG 2.2 AA's 24 by 24 CSS px minimum and aligns with the common iOS control-size recommendation.
4. Portrait and landscape orientations are both supported; no layout or script locks orientation.
5. Text can be enlarged to 200% and user text-spacing overrides can be applied without losing content or functionality.
6. Sticky/fixed header, mobile dock, WordPress admin bar, and save controls do not entirely hide the keyboard-focused element.
7. Product discovery, add-to-cart, cart editing, checkout, payment selection, account login/registration, orders, and address editing remain operable with touch and keyboard.
8. Mobile inputs use readable sizing and do not trigger avoidable iOS form zoom.
9. Reduced-motion preferences remove non-essential animation and hover-only affordances are never required.
10. CI runs responsive browser checks at phone, tablet, landscape, and desktop sizes and blocks regressions.

## 2. Standards and official references

The work follows these primary references:

- WCAG 2.2, **1.4.10 Reflow**: content must work at a width equivalent to 320 CSS px without two-dimensional page scrolling.  
  https://www.w3.org/WAI/WCAG22/Understanding/reflow.html
- WCAG 2.2, **1.4.4 Resize Text**: text must resize to 200% without loss of content or functionality.  
  https://www.w3.org/WAI/WCAG22/Understanding/resize-text
- WCAG 2.2, **1.4.12 Text Spacing**: user spacing overrides must not clip or overlap content.  
  https://www.w3.org/WAI/WCAG22/Understanding/text-spacing
- WCAG 2.2, **1.3.4 Orientation**: operation cannot be restricted to portrait or landscape unless essential.  
  https://www.w3.org/WAI/WCAG21/Understanding/orientation
- WCAG 2.2, **2.4.11 Focus Not Obscured (Minimum)**: authored sticky/fixed content cannot entirely hide focused controls.  
  https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum
- WCAG 2.2, **2.5.8 Target Size (Minimum)**: targets are at least 24 by 24 CSS px or meet spacing exceptions. The project uses 44 px for primary controls.  
  https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum
- Apple Human Interface Guidelines, Accessibility: iOS/iPadOS default controls are 44 by 44 points and controls need sufficient spacing.  
  https://developer.apple.com/design/human-interface-guidelines/accessibility
- WordPress block-theme responsive styles: WordPress defines mobile and tablet responsive scopes around 480 px and 782 px; project CSS follows these platform boundaries and adds content-driven intermediate widths.  
  https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/
- WordPress Navigation block structural classes used by the mobile overlay.  
  https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/core-block-navigation/
- Playwright mobile emulation: viewport, screen size, user agent, and touch behavior are emulated in automated checks.  
  https://playwright.dev/python/docs/emulation

## 3. Viewport and interaction test matrix

Automated checks cover the following deterministic CSS viewports:

| Class | Viewport | Purpose |
|---|---:|---|
| WCAG minimum | 320 × 800 | Reflow boundary and longest Persian labels |
| Small Android | 360 × 800 | Narrow touch commerce flow |
| Common iPhone | 390 × 844 | Mobile navigation, safe-area dock, forms |
| Large phone | 412 × 915 | Two-column product decision and long notices |
| Tablet portrait | 768 × 1024 | WordPress tablet breakpoint and stacked commerce |
| Tablet landscape | 1024 × 768 | Orientation support and intermediate grids |
| Laptop | 1280 × 720 | Short-height landscape/focus visibility |
| Desktop | 1440 × 900 | Full navigation and maximum content width |

Manual acceptance additionally covers one current iOS Safari device and one current Android Chrome device on the same local network.

## 4. Breakpoint strategy

Breakpoints are chosen by content failure rather than named devices:

- **Above 1200 px:** full desktop grids and full header actions.
- **961–1200 px:** reduced navigation spacing and three/two-column content where needed.
- **783–960 px:** tablet layout; hero, account, single-product, checkout, and contact structures stack before they become cramped.
- **481–782 px:** WordPress tablet/mobile navigation boundary; two-column product cards where readable, one-column operational panels, mobile dock enabled.
- **Up to 480 px:** one-column commerce cards, full-width actions, compact spacing, 320 px reflow guarantee.
- **Short landscape (`max-height: 520px`):** compact header/dock while retaining 44 px targets.

No content depends on a single exact device width.

## 5. Surface-by-surface implementation map

### 5.1 Global shell and typography

**Files**

- `plugin/barbershop-core/assets/runtime-storefront.css`
- `theme/persian-barbershop/style.css` (existing base layer)

**Actions**

- Apply `min-width: 0` to flex/grid descendants that can otherwise force overflow.
- Bound images, SVG, video, iframe, preformatted content, and long URLs.
- Use fluid spacing with `clamp()` while retaining rem-based minimums for zoom.
- Add safe-area padding for notched devices.
- Add `scroll-margin`/`scroll-padding` so the sticky header and mobile dock do not obscure focused or anchored content.
- Avoid fixed content heights; use `min-height` only for targets and intentional media ratios.

### 5.2 Header and WordPress Navigation overlay

**Files/classes**

- `theme/persian-barbershop/parts/header.html`
- `.site-header-shell`, `.pbs-header-inner`, `.pbs-brand-lockup`, `.pbs-header-navigation`, `.wp-block-navigation__responsive-container*`

**Actions**

- Keep full desktop navigation above the WordPress mobile boundary.
- Use a 44+ px menu-open and menu-close target.
- Make overlay links full-width, readable, and vertically spaced.
- Constrain logos and long site titles.
- Account for the WordPress admin bar at 32/46 px.
- Prevent the overlay from sitting below the bottom dock.

### 5.3 Hero and trust strip

**Files/classes**

- `theme/persian-barbershop/inc/pattern-content/hero.php`
- `.pbs-hero*`, `.pbs-trust-strip`, `.pbs-trust-item`

**Actions**

- Stack the 55/45 columns before tablet widths become cramped.
- Replace oversized phone typography with a rem-led fluid scale.
- Make hero actions full-width on narrow phones.
- Center and cap the hero media width; remove perspective transforms on touch/small screens.
- Convert proof/trust items to one column with borders that match the new flow.

### 5.4 Categories, services, reviews, gallery, articles, and contact

**Files/classes**

- `inc/pattern-content/store.php`, `services.php`, `reviews.php`, `gallery.php`, `latest-articles.php`, `contact.php`
- `.bsc-category-grid`, `.pbs-card-grid`, `.pbs-gallery-grid`, `.wp-block-post-template`, `.pbs-contact-panel`, `.bsc-ba-grid`

**Actions**

- Use 4/3/2/1 or 3/2/1 grid reductions according to available card width.
- Remove unnecessary minimum card heights on phones.
- Reduce category icon size without reducing target size.
- Ensure captions and before/after controls wrap and remain touch operable.
- Stack contact media/details and remove inline padding pressure.
- Keep article excerpts and Persian/English URLs wrapping safely.

### 5.5 Product archive and featured products

**Files/classes**

- `theme/persian-barbershop/assets/css/woocommerce.css`
- `plugin/barbershop-core/assets/runtime-storefront.css`
- `.woocommerce ul.products`, `li.product`, `.pbs-products-section`

**Actions**

- Bound desktop cards so a single product cannot stretch across the page.
- Use two columns only when each card remains readable; use one column at the 320–480 px boundary.
- Keep image aspect ratio stable and titles/prices/actions fully visible.
- Make add-to-cart and added-to-cart actions at least 44 px.

### 5.6 Single product

**Classes**

- `.woocommerce div.product`, `.images`, `.summary`, `.woocommerce-tabs`, related products

**Actions**

- Remove floats and stack gallery/summary by tablet width.
- Keep quantity and add-to-cart controls full-width on narrow phones.
- Allow tabs to wrap or scroll locally without page overflow.
- Constrain gallery thumbnails and zoom trigger.

### 5.7 Cart

**Classes**

- `.woocommerce-cart-form`, `.shop_table_responsive`, `.coupon`, `.cart_totals`, `.bsc-cart-payment-methods`

**Actions**

- Convert rows to labelled mobile blocks when WooCommerce responsive markup is available.
- Stack coupon/update controls and make the checkout CTA full-width.
- Keep product thumbnail, quantity, remove action, price, and totals readable.
- Keep enabled payment-method badges wrapping inside the card.

### 5.8 Checkout and gateways

**Classes**

- `.woocommerce-checkout`, `.col2-set`, `#order_review`, `#payment`, `.payment_methods`, Select2
- defensive `.wc-block-checkout*` support

**Actions**

- Stack billing, shipping, order review, and payment sections.
- Prevent third-party gateway logos/text from creating overflow.
- Use 16 px minimum input text on phones to avoid avoidable Safari zoom.
- Make the final order button full-width and keep validation notices visible.
- Disable mobile sticky order summaries that could obscure fields.

### 5.9 Account, authentication, orders, and addresses

**Classes**

- `#customer_login`, `.woocommerce-MyAccount-navigation`, `.woocommerce-MyAccount-content`, order tables

**Actions**

- Stack login/register panels before 900 px.
- Use a horizontally scrollable, snap-aligned account navigation on tablet/phone while preserving 44 px targets.
- Convert responsive order rows to labelled blocks and keep action buttons full-width on narrow phones.
- Ensure address and password forms use one-column rows where necessary.

### 5.10 Mobile bottom dock

**Classes**

- `.bsc-mobile-dock`, `.bsc-mobile-dock__icon`, cart count

**Actions**

- Display only through the WordPress mobile boundary.
- Use four equal columns, safe-area bottom padding, bounded SVGs, and 44 px targets.
- Add body bottom padding and focus scroll margins so content is never hidden behind the dock.
- Compact labels only in short landscape; icons and accessible names remain.

### 5.11 Barber dashboard and WooCommerce administration

**Files**

- `plugin/barbershop-core/assets/runtime-admin.css`
- existing `admin.css`, `barber-dashboard.css`, `qa-admin.css`

**Actions**

- Collapse hero, stat, status, setup, field, media, and category grids progressively.
- Remove minimum widths that forced the narrow WordPress content column to overflow.
- Use single-column labels/fields on phones and full-width media actions.
- Keep the save bar sticky inside its own form, above safe-area/keyboard edges, with full-width controls on mobile.
- Give all form controls and dashboard actions 44 px minimum height.
- Bound WooCommerce admin tables to local horizontal scrolling where the data relationship requires it.

## 6. Local-network mobile testing design

The local Compose service remains loopback-only by default. Mobile exposure is opt-in through:

- `WP_BIND_ADDRESS` in `compose.yaml`, defaulting to `127.0.0.1`.
- `tools/mobile-test.sh`, which detects the computer's private LAN address, recreates the WordPress port binding on `0.0.0.0`, updates WordPress `home` and `siteurl`, and prints the phone URL.
- `tools/mobile-test.sh --local`, which restores loopback binding and the local URL.

Security constraints:

- Use only on a trusted private Wi-Fi/LAN.
- Never forward the development port on the router.
- Keep the operating-system firewall enabled and permit only the local subnet if a rule is required.
- Restore loopback mode after testing.

## 7. Automated QA and CI gates

**Files**

- `tests/test_ui.py`
- `tests/test_responsive.py`
- `.github/workflows/quality.yml`

**Checks**

- Horizontal overflow at every matrix viewport.
- Phone/mobile dock visibility and desktop absence.
- Target dimensions for navigation, product, cart, form, and dock controls.
- Hero, category, product, services, gallery, article, review, contact, and footer sections remain inside the viewport.
- Text-spacing override does not produce clipping or page overflow.
- Portrait and landscape both render.
- Reduced-motion preference is respected.
- Responsive CSS, LAN binding, restore command, and documentation tokens are statically validated.
- Docker smoke test verifies the responsive stylesheet is served on storefront and cart pages.

## 8. Definition of done

- [x] Standards and viewport contract documented.
- [x] Global responsive regression layer implemented.
- [x] Header/navigation and mobile dock hardened.
- [x] Every home-page section covered.
- [x] Product archive and single-product layouts covered.
- [x] Cart, checkout, payment, account, orders, and forms covered.
- [x] Barber dashboard and relevant admin surfaces covered.
- [x] LAN mobile-test helper implemented with loopback restore.
- [x] Multi-viewport Playwright/static tests added.
- [ ] Final GitHub Actions run green on the implementation commit.
- [ ] Manual iOS Safari and Android Chrome acceptance on the owner's real LAN devices.
