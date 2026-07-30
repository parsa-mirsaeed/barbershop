# Barbershop WordPress Storefront

A generalized Persian-first RTL WordPress and WooCommerce storefront for a modern barbershop. It follows the supplied premium product-care direction with deep navy (`#071A3D`), emerald (`#0E8F6A`), true white, large editable icons, product-led imagery, smooth restrained motion, and a fully editable neutral identity.

## What is implemented

- **Short customer account:** first name, last name, email for login/recovery, and optional phone. Address and city are not requested during registration.
- **Obvious cart access:** desktop header cart with live count and a four-item mobile bottom dock.
- **Editable taxonomy:** hair/beard styling, hair care, skin care, and professional tools, with editable subcategories, order, descriptions, and media-library icons.
- **Modern Persian WooCommerce:** redesigned product archive, product detail, cart, checkout, account, forms, messages, buttons, empty states, labels, and mobile layouts.
- **Simple barber dashboard:** Persian operational overview with direct links to products, categories/icons, orders, appearance, settings, and security.
- **Vazirmatn:** the installer downloads the open-source variable font once and serves it locally; no runtime Google Fonts dependency.
- **Layered security:** Wordfence Free is installed and activated, custom writes use nonce/capability checks, private commerce pages are not cached/indexed, user enumeration is restricted, browser/server headers are added, and production uses HTTPS.
- **One-command setup:** local Docker installation and generic Caddy/HTTPS production deployment.
- **Quality gates:** static policy checks, secret scan, PHP compatibility, backend unit tests, Playwright desktop/mobile UI tests, release build, Compose validation, and a complete Docker WordPress/WooCommerce/Wordfence smoke test.

No real person, business, address, phone, customer, merchant credential, payment credential, or copied brand identity is included.

## Local installation

Requirements: Docker with Compose v2 and Bash.

```bash
./tools/install.sh
```

The installer creates `.env` with random local passwords, finds a free localhost port, installs WordPress, Persian language files when available, WooCommerce, Wordfence, the theme and plugin, editable categories, local Vazirmatn, and clean permalinks.

Open the URL printed by the installer and review the generated administrator password in `.env`. Then open Wordfence and finish firewall optimization, alert email, and administrator 2FA.

### Re-cloned project or stale Docker volumes

`docker compose down` stops and removes containers, but it deliberately keeps the named WordPress and MariaDB volumes. If the project is later re-cloned and a new `.env` is generated, those old volumes can still contain database credentials from the previous `.env`.

For a clean local reinstall when no old local shop data is needed:

```bash
./tools/install.sh --reset
```

This deletes only the Docker volumes belonging to this Compose project and rebuilds the local site. To preserve the old database, restore the previous `.env` instead of using `--reset`.

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

Open **مدیریت فروشگاه** in WordPress admin:

- appearance → logo, name, homepage, navigation, footer and contact placeholders;
- categories → names, subcategories, order, descriptions and large icons;
- products → images, title, price, inventory, attributes and publishing checklist;
- orders → payment and fulfilment workflow;
- security → Wordfence status and next actions.

Persian instructions: [STORE-MANAGEMENT.fa.md](docs/STORE-MANAGEMENT.fa.md).

## Tests

```bash
make test
```

The CI workflow also installs Chromium for Playwright and runs a full Docker smoke test. Build normal WordPress upload packages with:

```bash
make release
```

## Standards

The project targets WCAG 2.2 AA, WordPress Coding Standards, OWASP ASVS 5.0.0 as a verification guide, WordPress/WooCommerce hardening guidance, and Core Web Vitals “good” thresholds. These are test targets rather than a claim of external certification. See [QUALITY-STANDARDS.md](docs/QUALITY-STANDARDS.md).

## Required customization

Replace every bracketed placeholder, neutral logo, product content, contact value, legal text, shipping setting, email configuration, payment configuration, and consent-cleared portfolio/review item before launch.

## License

GPL-2.0-or-later. See `LICENSE`.
