# Deployment guidance

## Supported paths

- `compose.yaml` + `tools/install.sh`: localhost development/demo.
- `compose.production.yaml` + Caddy + `tools/deploy.sh`: generic single-server HTTPS baseline.
- `tools/build-release.sh`: theme/plugin ZIPs for a managed WordPress host.

## Production prerequisites

- DNS points to the server; only required ports are exposed.
- Unique random database/admin credentials are stored outside Git.
- Host, PHP, database, WordPress, WooCommerce, gateway and Wordfence are supported and patched.
- Administrator 2FA, least privilege, alert routing and an incident owner are configured.
- Encrypted off-server backups exist and a restore has been tested.
- Authenticated transactional email and delivery monitoring are configured.
- A maintained WooCommerce/Zarinpal plugin performs server-side amount/order/callback verification.
- Privacy, terms, returns, shipping, retention and contact pages match the operating jurisdiction.

## Launch verification

1. Replace every placeholder, neutral logo and generic image.
2. Confirm category hierarchy/icons and every product's price, stock, dimensions, media and tax/shipping data.
3. Test registration, recovery, login throttling and administrator 2FA.
4. Test desktop/mobile cart access and 200% zoom/keyboard/reduced-motion behavior.
5. Test checkout success, failure, cancellation, timeout, duplicate callback, amount mismatch, refund and reconciliation.
6. Confirm no card data enters WordPress, logs, analytics or support messages.
7. Confirm order/admin email delivery and backup/restore.
8. Measure production Core Web Vitals after real product images and third-party scripts are enabled.
9. Run CI and a low-value live transaction before launch.

The Docker example is not a substitute for managed operations, penetration testing, legal review or continuous monitoring.
