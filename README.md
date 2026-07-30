# Barbershop WordPress Storefront

A generalized Persian-first RTL WordPress and WooCommerce storefront for a modern barbershop. It follows the supplied premium product-care direction with deep navy (`#071A3D`), emerald (`#0E8F6A`), true white, large editable icons, product-led imagery, smooth restrained motion, and a fully editable neutral identity.

## What is implemented

- **Short customer account:** first name, last name, email, a customer-chosen password, and optional phone. Address and city are not requested during registration.
- **Dedicated barber role:** a least-privilege `barber` role can manage the operational store without receiving full administrator access.
- **Barber dashboard:** brand name, logo, hero/contact/gallery images, contact details, services, reviews, category names/order/icons, products, orders, and consent-gated before/after entries are managed from one Persian dashboard—without the visual Site Editor.
- **Separated staff/customer paths:** staff log in through `/wp-admin/` and are routed to the barber dashboard; customers use the storefront account page. Staff are prevented from using the customer account interface.
- **Obvious cart access:** desktop header cart with live count and a four-item mobile bottom dock.
- **Editable taxonomy:** hair/beard styling, hair care, skin care, and professional tools, with editable subcategories, order, descriptions, and media-library icons.
- **Modern Persian WooCommerce:** redesigned product archive, product detail, cart, checkout, account, authentication, forms, messages, buttons, empty states, labels, and mobile layouts.
- **Vazirmatn:** the installer downloads the open-source variable font once and serves it locally; no runtime Google Fonts dependency.
- **Layered security:** Wordfence Free is installed and activated, custom writes use nonce/capability checks, private commerce pages are not cached/indexed, user enumeration is restricted, browser/server headers are added, and production uses HTTPS.
- **One-command setup:** local Docker installation and generic Caddy/HTTPS production deployment.
- **Quality gates:** static policy checks, secret scan, PHP compatibility, backend unit tests, Playwright desktop/mobile UI tests, release build, Compose validation, and a complete guest-visible Docker WordPress/WooCommerce/Wordfence smoke test.

No real person, business, address, phone, customer, merchant credential, payment credential, or copied brand identity is included.

## Local installation

Requirements: Docker with Compose v2, Bash, and Python 3.

```bash
./tools/install.sh
```

The installer creates `.env` when missing and automatically replaces empty or example local passwords with cryptographically random values. It preserves any real values you already supplied, finds a free localhost port, starts Docker Compose, installs WordPress, Persian language files when available, WooCommerce, Wordfence, the theme and plugin, editable categories, local Vazirmatn, and clean permalinks. It also disables WooCommerce Coming Soon mode, creates the barber role, enables explicit customer passwords, localizes commerce page titles, and removes WordPress seed content.

Open the URL printed by the installer and review the generated administrator password in `.env`. Then open Wordfence and finish firewall optimization, alert email, and administrator/barber 2FA.

### Upgrade an existing local site

After pulling a newer branch version, rerun the installer without deleting data:

```bash
./tools/install.sh
```

Use `--reset` only when a clean local reinstall is intended.

### Re-cloned project or stale Docker volumes

`docker compose down` stops and removes containers, but it deliberately keeps the named WordPress and MariaDB volumes. If the project is later re-cloned and a new `.env` is generated, those old volumes can still contain database credentials from the previous `.env`.

For a clean local reinstall when no old local shop data is needed:

```bash
./tools/install.sh --reset
```

This fills any remaining example secrets, deletes only the Docker volumes belonging to this Compose project, and rebuilds the local site. To preserve the old database, restore the previous `.env` instead of using `--reset`.

Useful checks:

```bash
docker compose ps -a
docker compose logs --no-color database wordpress
```

## Production Docker deployment

```bash
cp .env.production.example .env.production
# replace every example value and point DNS to the server
./tools/deploy.sh
```

Caddy obtains HTTPS certificates. The repository is an engineering baseline, not a managed hosting/security service: production still needs encrypted off-server backups with tested restores, monitoring, authenticated email, a maintained Zarinpal/WooCommerce gateway, legal pages, and jurisdiction-specific review. See [deployment](docs/DEPLOYMENT.md) and [security hardening](docs/SECURITY-HARDENING.fa.md).

## Daily editing

Open **داشبورد آرایشگر** in WordPress admin:

- identity and content → brand name, logo, hero image, contact image, gallery placeholders, service text, reviews, footer and contact values;
- categories → primary category names, order and icons inline; subcategories and descriptions through the linked taxonomy screen;
- products → images, title, price, inventory, attributes and publishing checklist;
- orders → payment and fulfilment workflow;
- before/after → paired images, service label and mandatory publication-consent confirmation;
- security → Wordfence status and next actions.

Persian instructions: [STORE-MANAGEMENT.fa.md](docs/STORE-MANAGEMENT.fa.md).

## Authentication model

WordPress authentication is shared across the public and administrative areas of the same site. The project therefore separates **routes and roles**, not browser cookies: administrators and barbers are redirected away from the customer account screen to the staff dashboard, while customer accounts cannot access administration. Use a private/incognito window or a separate browser profile when testing the customer experience while a staff session is open.

## Tests

```bash
make test
```

The CI workflow also installs Chromium and runs a full Docker smoke test. It checks the logged-out homepage, explicit password registration, barber capabilities, Persian page names, Coming Soon state, raw-shortcode absence, WooCommerce/Wordfence activation and the locally hosted font. Build normal WordPress upload packages with:

```bash
make release
```

## Standards

The project targets WCAG 2.2 AA, WordPress Coding Standards, OWASP ASVS 5.0.0 as a verification guide, WordPress/WooCommerce hardening guidance, and Core Web Vitals “good” thresholds. These are test targets rather than a claim of external certification. See [QUALITY-STANDARDS.md](docs/QUALITY-STANDARDS.md).

## Required customization

Replace every placeholder, neutral image, product content, contact value, legal text, shipping setting, email configuration, payment configuration, and consent-cleared portfolio/review item before launch.

## License

GPL-2.0-or-later. See `LICENSE`.
