# Shared Hosting Production Plan

This branch keeps the Docker/VPS deployment intact and adds a separate production path for cPanel/DirectAdmin-style hosting such as ParsPack Linux Hosting, Cloud Hosting, or WordPress Hosting.

## Goals

1. Do not require root, Docker, Caddy, or a private MariaDB container.
2. Preserve the same Persian storefront, barber dashboard, WooCommerce flows, ZarinPal/Gateland, SMS, shipping, Wordfence, Vazirmatn, and mobile behavior.
3. Fail safely when a host is missing a critical PHP extension instead of taking wp-admin/storefront down.
4. Package only project-owned theme/plugin code; install third-party plugins from WordPress/provider sources so updates remain normal.
5. Keep secrets out of Git and release ZIP files.
6. Provide repeatable install, update, rollback, backup, and remote-audit procedures.

## Production acceptance gates

Do not accept real orders until every CRITICAL gate is green.

### Host/PHP

- PHP 8.2+ recommended (plugin remains compatible with 7.4 for portability).
- memory_limit >= 256M, 512M recommended.
- Required PHP extensions: pdo_mysql, mysqli, curl, mbstring, openssl, fileinfo.
- Recommended: intl, sodium, zip, GD or Imagick.
- HTTPS enabled for the canonical WordPress home/site URL.
- WordPress uploads writable without using 777 permissions.
- At least 5 GB free space preferred after installation; monitor the hosting quota separately because `disk_free_space()` may report the shared filesystem rather than the account quota.

### WordPress security

Add the values from `hosting/wp-config.production.snippet.txt` above the stop-editing line in wp-config.php.

Required operational controls:

- `WP_ENVIRONMENT_TYPE=production`
- `FORCE_SSL_ADMIN=true`
- `DISALLOW_FILE_EDIT=true`
- `WP_DEBUG=false`
- `WP_DEBUG_DISPLAY=false`
- unique WordPress salts/keys
- Administrator and Barber accounts protected with Wordfence 2FA
- Wordfence firewall optimized after the host confirms compatibility
- no customer/payment secrets committed to Git or stored in public files

Do not enable `DISALLOW_FILE_MODS` unless updates are managed by another controlled process.

### Gateland / PDO safety

Historical local logs showed Gateland throwing `PDOException: could not find driver` when `pdo_mysql` was missing. The shared-host bundle therefore contains an MU plugin guard in:

`hosting/mu-plugins/barbershop-hosting-guard.php`

Install it **before Gateland**:

1. Create `wp-content/mu-plugins/` if absent.
2. Upload `barbershop-hosting-guard.php` into that directory.
3. Confirm it appears under Plugins -> Must-Use.
4. Open Tools -> Hosting Health and verify PDO MySQL is green.
5. Only then install/activate Gateland.

If PDO MySQL is unavailable, keep Gateland disabled and use another compatible gateway (for example the direct ZarinPal plugin) until the host enables the extension.

## Installation order

1. Create a fresh WordPress site from the hosting panel.
2. Set PHP 8.2+ and apply the supported values from `.user.ini.recommended` using the provider PHP editor.
3. Enable SSL and force the site URLs to HTTPS.
4. Apply the wp-config hardening snippet.
5. Install the MU hosting guard.
6. Upload/activate `persian-barbershop-theme.zip`.
7. Upload/activate `barbershop-core-plugin.zip`.
8. Install/activate WooCommerce.
9. Open Tools -> Hosting Health and resolve every red item.
10. Install Wordfence and enable 2FA/firewall protection.
11. Install payment plugins. Activate Gateland only after PDO MySQL passes.
12. Install Persian WooCommerce SMS and shipping plugins if used.
13. Run the Barbershop setup/dashboard and configure logo, placeholders, categories, products, contact information, legal/business details, and the barber account.
14. Configure payment credentials in test/sandbox mode first.
15. Configure SMS provider credentials and the barber purchase notification.
16. Configure shipping zones/rates.
17. Configure cache exclusions and real cron.
18. Make an external backup and test a restore before going live.
19. Run `tools/shared-hosting-audit.sh https://example.com` from a trusted local machine.

## Cache rules

WooCommerce/private transaction pages must not be full-page cached. Confirm exclusions for at least:

- `/cart/`
- `/checkout/`
- `/my-account/`
- `?wc-ajax=*`
- `?wc-api=*`
- provider payment callback endpoints
- logged-in/customer-session cookies

The project sends no-cache headers for account/cart/checkout as a second layer, but provider/LiteSpeed/CDN configuration must also respect these pages.

## Cron and Action Scheduler

WooCommerce relies on scheduled actions for cleanup, emails and integrations. Prefer a real hosting-panel cron every 5 minutes.

Example (adjust PHP path and document root to the provider):

`*/5 * * * * /usr/local/bin/php -q /home/ACCOUNT/public_html/wp-cron.php >/dev/null 2>&1`

If the provider does not expose PHP CLI, use its documented HTTP cron mechanism instead. Only set `DISABLE_WP_CRON=true` after the real cron is confirmed working.

## Backups

Provider backups are useful but are not the only copy.

Minimum policy:

- database backup daily
- `wp-content/uploads` and project configuration backup daily/weekly
- encrypted copy stored outside the same hosting account/provider
- suggested retention: 7 daily, 4 weekly, several monthly
- restore test after initial deployment and periodically afterward
- backup before WordPress/core/plugin/theme upgrades

Do not place downloadable SQL files inside `public_html`.

## Updates and rollback

### Staging first

Where the host supports a staging subdomain/site, update there first. Otherwise create a backup and use a short maintenance window.

Recommended update order:

1. backup DB + files
2. WordPress security/minor update
3. WooCommerce
4. payment/SMS/shipping plugins
5. Barbershop Core
6. Persian Barbershop theme
7. purge page/object/CDN cache
8. run Hosting Health
9. test login, registration, product, cart, checkout, payment callback, order status, barber notification, mobile menu and account pages
10. run external shared-hosting audit

Rollback by restoring the pre-update backup or the previous known-good plugin/theme ZIP plus database snapshot when a database migration was involved.

## Permissions

Follow provider ownership defaults. Do not recursively set 777.

Typical WordPress values are directories 755 and files 644, while `wp-config.php` should be as restrictive as the host supports without breaking PHP access. Provider ACLs/suEXEC rules take precedence.

## Payment/customer information

The store should never collect raw card number, CVV2, expiration date, or banking password. Customers should be redirected/handed off to the payment provider and the return/callback must be verified server-side before an order is marked paid.

Test at minimum:

- successful payment
- rejected payment
- user cancellation
- duplicate callback
- wrong/modified amount or order reference
- delayed callback
- gateway timeout/unavailability
- refund/reconciliation process

## SMS

Store SMS API credentials only in the plugin/provider settings or protected environment mechanisms supplied by the host. Do not commit them to Git or include them in support screenshots.

For purchase notifications, trigger the barber message after the order reaches the chosen trusted state (commonly successful payment/processing rather than an unverified browser return).

## Release contents

`make hosting-release` creates `dist/shared-hosting/` containing:

- theme ZIP
- Barbershop Core ZIP
- MU hosting guard
- recommended PHP settings
- wp-config production snippet
- this deployment guide
- manifest JSON
- SHA256 checksums
- one combined bundle ZIP

Third-party WordPress plugins are intentionally not bundled.

## Remote audit

Run after DNS/SSL is live:

`make hosting-audit URL=https://shop.example.com`

The audit checks public HTTPS behavior, security headers, REST user enumeration, private-page cache headers, and whether a public debug.log appears exposed. It does not receive or print gateway/SMS/database secrets.

## Separation from Docker/VPS

Docker remains the reproducible local-development and VPS deployment environment. Shared hosting uses provider Apache/LiteSpeed/PHP/MySQL and therefore must not run `tools/deploy.sh` or `compose.production.yaml` on the hosting account.
