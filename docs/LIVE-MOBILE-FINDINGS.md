# Live mobile capture findings and repairs

Date: 2026-08-01

These findings come from the supplied Chrome device-emulation captures at 412 × 915 portrait and 915 × 412 landscape. They supersede the earlier fixture-only responsive acceptance because the fixture contained a viewport meta tag that the live WordPress output did not.

## Primary root cause

The live document did not output:

```html
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
```

Mobile browsers therefore used a desktop-sized layout viewport and scaled or clipped it into the physical phone viewport. The existing media queries were correct in isolation but were not evaluating against the real 412 CSS-pixel device width. This explains why the fixture and CI passed while the live screenshots still showed desktop header actions, wide columns, clipped text, and shifted full-width sections.

The runtime now emits the viewport contract at the start of `wp_head` and loads a final capture-driven CSS layer after all theme, WooCommerce, and component styles.

## Screenshot findings

### Portrait homepage: header and hero

Observed:

- desktop cart and account pills remained visible beside the mobile menu trigger;
- the logo was partially outside the visible area;
- the hero heading, paragraph, buttons, proof list, and media were cut on the right;
- a vertical strip of the page background remained visible on the left, showing that `alignfull` sections were shifted beyond the physical viewport.

Root causes:

- missing viewport meta prevented the `max-width: 781px` rules from activating correctly;
- saved block inline `flex-basis` values remained effective;
- full-width and wide block geometry relied on generated WordPress layout styles and was not explicitly normalized for an old saved page.

Repairs:

- hide desktop header actions on phone widths because the bottom dock already exposes account and cart;
- force the hero to one column and neutralize saved inline flex bases;
- constrain `alignfull` to its real parent width and give `alignwide` an explicit centered mobile width;
- use a smaller, wrapping Persian hero heading and full-width action buttons.

### Portrait bottom navigation

Observed:

- only part of the cart action was visible;
- the cart cell retained the green desktop pill appearance;
- the four dock actions did not read as four equal navigation cells.

Root cause:

The dock reused the normal `bsc-cart-link` and `bsc-cart-link--floating` classes. Their desktop background, border radius, fixed positioning, width, padding, and nowrap declarations leaked into the grid cell.

Repairs:

- reset every desktop/floating cart-link declaration inside `.bsc-mobile-dock`;
- force four equal, zero-minimum-width cells;
- keep only the dock active/hover background and position the count badge inside its own cell.

### Portrait trust, categories, gallery, articles, and reviews

Observed:

- headings were clipped;
- sections looked like nearly empty full screens;
- empty portfolio/article states used excessive vertical space;
- section content was shifted beyond the right edge.

Root causes:

- the desktop layout viewport and large desktop spacing were being scaled into the phone;
- full-width blocks were not explicitly bounded;
- non-hero sections had no final guard against inherited viewport-height sizing from saved/custom content.

Repairs:

- set non-hero sections to content-driven height and zero minimum height;
- reduce phone section padding and heading margins;
- center and bound empty-state copy;
- bound all section headings, cards, images, queries, and before/after media.

### Portrait customer account

Observed:

- login and registration remained wider than the phone;
- labels, fields, buttons, and the registration note were clipped on the right;
- the fixed dock overlapped lower form content.

Root causes:

- the missing viewport meta prevented the intended single-column account breakpoint;
- WooCommerce and saved block wrappers retained wide maximums and padding;
- the account forms needed a final explicit one-column grid and bottom safe-area clearance.

Repairs:

- force the WooCommerce/account wrappers to the physical viewport width;
- force `#customer_login` and logged-in account layouts to one column;
- make form rows, inputs, and buttons 100% wide with zero minimum width;
- preserve body and footer clearance for the safe-area-aware dock.

### Portrait footer

Observed:

- footer logo, description, quick links, and contact content were clipped and shifted;
- the dock covered the footer’s lower content.

Repairs:

- stack footer columns at phone widths;
- neutralize inline column flex bases;
- bound the brand lockup and add safe-area-aware footer bottom padding.

### Short landscape header and commerce pages

Observed:

- the header consumed a large fraction of the 412px height;
- navigation wrapped to a second row;
- full account/cart labels used unnecessary horizontal space;
- cart and hero content began below an oversized header.

Repairs:

- add a dedicated 782–1024px by 520px-high landscape mode;
- keep the navigation in one compact row;
- convert account/cart controls to 44px icon buttons with the cart count badge;
- shrink the logo and header height while preserving touch targets.

### Short landscape hero/contact media

Observed:

- stacked full-width images occupied most or all of the short viewport;
- the contact/gallery image appeared as a very large crop.

Root cause:

The general tablet rule stacked hero/contact columns at widths below 960px, which is correct in portrait but poor at 915 × 412 landscape.

Repairs:

- restore side-by-side hero and contact compositions only for short landscape;
- cap hero and contact media heights and use controlled aspect ratios;
- hide the secondary hero proof list in this constrained orientation.

## Files changed

- `plugin/barbershop-core/includes/mobile-live-fixes.php`
- `plugin/barbershop-core/assets/mobile-live-fixes.css`
- `plugin/barbershop-core/barbershop-core.php`
- `tests/test_live_mobile_round3.py`
- responsive version regression tests

## Acceptance tests

The new browser regressions exercise:

- 412 × 915 portrait homepage geometry;
- physical viewport width and page scroll width;
- hidden desktop header actions;
- one-column hero and footer;
- four equal dock cells with reset cart styles;
- 412px account login and registration containment;
- focused registration action above the fixed dock;
- 915 × 412 compact header;
- side-by-side short-landscape hero and contact media;
- zero viewport-height minimums for empty-content sections.
