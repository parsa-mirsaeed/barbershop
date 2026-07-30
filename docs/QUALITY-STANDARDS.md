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
- product/category images support alternative text while decorative SVGs are hidden from assistive technology.

## Secure development — OWASP and WordPress/WooCommerce

Verification guide: [OWASP ASVS 5.0.0](https://owasp.org/www-project-application-security-verification-standard/), [WordPress Hardening](https://developer.wordpress.org/advanced-administration/security/hardening/), and [WooCommerce security guidance](https://woocommerce.com/document/woocommerce-security-faq/).

Implemented baseline:

- nonce and capability checks for every custom write;
- output escaping and restricted HTML allowlists;
- no payment-card collection or storage in the custom theme/plugin;
- generic login errors, REST user-enumeration restriction, private-page no-cache headers, optional XML-RPC disabling, and conservative browser headers;
- Wordfence installed and activated by the installer, with administrator 2FA and firewall optimization left as explicit operator steps;
- credentials stay outside Git, database is not published, production uses HTTPS, and the WordPress file editor is disabled.

ASVS is used as a review checklist, not a claim that the whole deployment is ASVS-certified.

## WordPress engineering

Target: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and supported WooCommerce extension patterns.

Repository gates include:

- PHP syntax checks on 7.4, 8.2, and 8.3;
- backend unit tests for normalization, translations, account-menu privacy, and category blueprints;
- WordPress/WooCommerce Docker installation smoke test;
- script syntax, secret scanning, block-structure validation, release-package build, and Compose validation;
- compatibility declarations for WooCommerce HPOS and Cart/Checkout blocks.

## Performance — Core Web Vitals

Target: [Core Web Vitals](https://web.dev/articles/vitals) in the “good” range at the 75th percentile after production measurement:

- LCP ≤ 2.5 seconds;
- INP ≤ 200 milliseconds;
- CLS ≤ 0.1.

Design choices supporting that target include a self-hosted variable Vazirmatn file, font preloading, deferred small JavaScript files, explicit image dimensions, lazy loading below the hero, no heavy front-end framework, reduced WordPress emoji assets, responsive layouts, and versioned static-asset caching in Caddy. Real results depend on hosting, product images, third-party payment/analytics scripts, cache/CDN configuration, and traffic.

## Visual QA

The supplied navy/emerald/white product-care reference is the active visual direction. Automated browser checks cover desktop and mobile overflow, RTL, typography, touch targets, persistent mobile navigation, focus visibility, content structure, and reduced motion. The theme screenshot is generated from the same CSS and reusable components used by the storefront fixture.
