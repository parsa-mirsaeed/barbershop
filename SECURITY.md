# Security policy

## Reporting

Do not open a public issue containing credentials, customer information, exploit details, or production logs. Use the repository owner's private security-reporting channel when one is configured.

## Repository rules

- Never commit `.env`, `wp-config.php`, database dumps, exports, backups, private keys, certificates, payment credentials, SMTP credentials, access tokens, customer records, or raw gateway callbacks.
- Treat example passwords as documentation only. Generate unique values for every environment.
- Keep local services bound to localhost unless deliberate network exposure is protected separately.
- Use hosted payment pages and provider-side verification.
- Use documented consent for every identifiable portfolio image or testimonial.

## Deployment baseline

Use supported versions, HTTPS, MFA, least-privilege roles, a managed firewall or WAF, rate limiting, encrypted backups, tested restores, integrity monitoring, and authenticated email delivery.
