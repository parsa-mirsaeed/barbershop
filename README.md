# Barbershop WordPress

A generalized, Persian-first RTL WordPress block theme with WooCommerce styling, a privacy-aware before/after portfolio plugin, and a safe local Docker development stack.

This repository is intentionally free of personal names, real contact information, customer data, merchant credentials, database dumps, production configuration, and private project history.

## Included

- `theme/persian-barbershop` — dark charcoal, warm-white, and muted-gold block theme.
- `plugin/barbershop-core` — managed before/after entries, publication-consent checks, and configurable new-order notification recipient.
- `compose.yaml` — local-only WordPress and MariaDB environment using values from `.env`.
- `tools/validate.py` — structure and policy checks.
- `tools/scan-secrets.py` — conservative repository secret scanner.

## Safe local setup

1. Copy the environment template:

   ```bash
   cp .env.example .env
   ```

2. Generate unique local passwords instead of reusing the examples:

   ```bash
   openssl rand -hex 32
   ```

3. Put the generated values in `.env`.
4. Start the stack:

   ```bash
   docker compose up -d
   ```

5. Open `http://127.0.0.1:8080` unless `WP_PORT` was changed.
6. Complete WordPress setup, activate **Persian Barbershop**, then activate **Barbershop Core**.
7. Install WooCommerce only when storefront functionality is required.

The port binds to `127.0.0.1` by default. This stack is a development baseline, not a production hosting recipe.

## Required customization

Replace every bracketed placeholder before deployment:

- `[نام کسب‌وکار]`
- `[نام آرایشگر]`
- `[شماره تماس]`
- `[نشانی]`
- `[ساعت پاسخ‌گویی]`
- `[توضیح خدمت]`
- `[نظر واقعی مشتری با اجازه انتشار]`

Use only permission-cleared portfolio images and genuine reviews with publication consent.

## Validation

```bash
python3 tools/scan-secrets.py
python3 tools/validate.py
find theme plugin -name '*.php' -print0 | xargs -0 -n1 php -l
node --check plugin/barbershop-core/assets/admin.js
node --check plugin/barbershop-core/assets/frontend.js
```

## Production notes

- Keep all credentials outside Git and rotate any value that was accidentally committed.
- Use HTTPS, MFA, least privilege, encrypted backups, tested restores, secure cookies, rate limiting, and authenticated transactional email.
- Use a maintained hosted-payment gateway plugin. Never collect card numbers, security codes, PINs, expiry dates, or gateway secrets in this theme or plugin.
- Keep WordPress core, WooCommerce, plugins, PHP, the database, and the host patched.
- Review privacy, consumer, tax, retention, accessibility, and payment obligations for the deployment jurisdiction.

## License

GPL-2.0-or-later. See `LICENSE`.
