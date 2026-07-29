# Deployment guidance

The Docker configuration is for local development. A production deployment should use managed secrets, HTTPS, restricted administration, persistent storage, encrypted backups, monitoring, and a supported update process.

## Before launch

- Replace all bracketed placeholders.
- Confirm image and review publication rights.
- Install WooCommerce only when needed.
- Select a maintained payment plugin for the contracted provider and test request, redirect, callback, verification, cancellation, duplicate callback, amount mismatch, timeout, refund, and reconciliation behavior.
- Configure authenticated transactional email.
- Publish privacy, terms, returns, shipping, and contact pages appropriate to the jurisdiction.
- Test keyboard navigation, zoom, contrast, reduced motion, mobile layout, checkout, email, backups, and restoration.

## Secrets

Production values belong in the host's secret manager or protected configuration—not Git, theme files, block content, screenshots, CI output, or support logs.
