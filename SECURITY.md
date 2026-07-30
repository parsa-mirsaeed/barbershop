# Security policy

## Reporting

Do not open a public issue containing credentials, customer information, exploit details, gateway callbacks, or production logs. Use the repository owner's private security-reporting channel when one is configured.

## Repository rules

Never commit `.env`, `wp-config.php`, database dumps, exports, backups, private keys, certificates, payment/SMTP credentials, access tokens, customer records, or raw gateway callbacks. Example values are documentation only and must be replaced per environment.

## Deployment baseline

Use supported software, HTTPS, administrator 2FA, least privilege, Wordfence or an equivalent maintained WAF/security layer, login rate limiting, encrypted off-server backups, tested restores, integrity/availability monitoring, and authenticated transactional email. Keep WooCommerce and the payment gateway maintained and test server-side payment verification.

The custom code must never collect or store card number, CVV, PIN/password, or expiry data. See `docs/SECURITY-HARDENING.fa.md` for the operational checklist.

## Supported versions

Security fixes are applied to the current `main` branch and the active storefront pull request. Do not deploy an archived release without checking its dependencies and security notices.
