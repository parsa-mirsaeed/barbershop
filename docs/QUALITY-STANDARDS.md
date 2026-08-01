# Quality and engineering standards

This project uses published standards as design and verification targets. The repository does not claim external certification; production acceptance still requires testing in the real hosting, payment, email, analytics, and content environment.

## Accessibility — WCAG 2.2 AA

Target: [W3C Web Content Accessibility Guidelines 2.2](https://www.w3.org/TR/WCAG22/) at Level AA and the [WordPress Accessibility Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/).

Implemented gates:

- semantic landmarks, one primary heading, meaningful Persian labels, and a skip link;
- keyboard-visible focus with at least 44px interactive targets;
- RTL layouts that remain usable at 200% zoom and mobile widths;
- no information conveyed only through color;
- motion disabled or reduced with `prefers-reduced-motion`;
- product/category images support alternative text while decorative SVGs are hidden from assistive technology;
- authentication, account, cart and barber-dashboard controls retain visible labels, focus states and mobile usability.

## Secure development — OWASP and WordPress/WooCommerce

Verification guide: [OWASP ASVS 5.0.0](https://owasp.org/www-project-application-security-verification-standard/), [WordPress Hardening](https://developer.wordpress.org/advanced-administration/security/hardening/), and [WooCommerce security guidance](https://woocommerce.com/document/woocommerce-security-faq/).

Implemented baseline:

- nonce and capability checks for every custom write;
- output escaping and restricted HTML allowlists;
- no payment-card collection or storage in the custom theme/plugin;
- generic login errors, REST user-enumeration restriction, private-page no-cache headers, optional XML-RPC disabling, and conservative browser headers;
- an explicit customer password of at least 12 characters and no silent generated-password flow;
- a dedicated least-privilege barber role rather than routine use of the administrator account;
- staff login redirects to the operational dashboard and staff access to the customer account surface is blocked;
- customer roles remain subject to WooCommerce administration-access restrictions;
- Wordfence installed and activated by the installer, with administrator/barber 2FA and firewall optimization left as explicit operator steps;
- credentials stay outside Git, database is not published, production uses HTTPS, and the WordPress file editor is disabled.

WordPress uses one authenticated session across the public and administrative paths of a single hostname. The project separates roles and destinations rather than claiming cookie isolation. Customer-session QA while staff is signed in must use a private window or separate browser profile. A stronger physical separation would require a separate hostname/application architecture and a separate security review.

ASVS is used as a review checklist, not a claim that the whole deployment is ASVS-certified.

## WordPress engineering

Target: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and supported WooCommerce extension patterns.

Repository gates include:

- PHP syntax checks on 7.4, 8.2, and 8.3;
- backend unit tests for normalization, translations, account-menu privacy, and category blueprints;
- static checks for barber capabilities, dashboard-owned placeholders, explicit-password registration and staff/customer routing;
- WordPress/WooCommerce Docker installation smoke test;
- a logged-out HTTP test proving the storefront is public rather than in Coming Soon mode;
- runtime checks that raw product/category/before-after shortcodes never reach the rendered homepage;
- runtime checks for Persian commerce page titles, the barber role, WooCommerce/Wordfence activation and removal of WordPress seed content;
- script syntax, secret scanning, block-structure validation, release-package build, and Compose validation;
- compatibility declarations for WooCommerce HPOS and Cart/Checkout blocks.

## E-commerce release acceptance

Every release is expected to satisfy these operational invariants before merge:

1. A logged-out visitor receives the real storefront and not a maintenance/Coming Soon shell.
2. The cart and customer-account routes are reachable without administrator authentication.
3. Registration presents an explicit password field and does not collect an address or city.
4. Staff accounts are routed to the barber dashboard, while customer accounts cannot enter administration.
5. Products, categories, icons, orders, brand media and consent-gated before/after content are editable without code changes.
6. Placeholder shortcodes, seed posts and untranslated high-frequency account/cart strings do not appear in the rendered experience.
7. Payment-card details are handled only by the maintained gateway and are never stored by the project.

## Performance — Core Web Vitals

Target: [Core Web Vitals](https://web.dev/articles/vitals) in the “good” range at the 75th percentile after production measurement:

- LCP ≤ 2.5 seconds;
- INP ≤ 200 milliseconds;
- CLS ≤ 0.1.

Design choices supporting that target include a self-hosted variable Vazirmatn file, font preloading, deferred small JavaScript files, explicit image dimensions, lazy loading below the hero, no heavy front-end framework, reduced WordPress emoji assets, responsive layouts, and versioned static-asset caching in Caddy. Role capability synchronization is version-gated to avoid database writes on normal public requests. Real results depend on hosting, product images, third-party payment/analytics scripts, cache/CDN configuration, and traffic.

## Visual QA

The supplied navy/emerald/white product-care reference is the active visual direction. Automated browser checks cover desktop and mobile overflow, RTL, typography, touch targets, persistent mobile navigation, focus visibility, content structure, reduced motion, authentication cards, account navigation and barber-dashboard media controls. The theme screenshot is generated from the same CSS and reusable components used by the storefront fixture.
