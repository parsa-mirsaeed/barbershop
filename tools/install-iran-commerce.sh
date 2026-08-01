#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

command -v docker >/dev/null 2>&1 || { echo "Docker is required." >&2; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "Docker Compose v2 is required." >&2; exit 1; }

docker compose ps --status running -q wordpress | grep -q . || {
  echo "WordPress is not running. Run ./tools/install.sh first." >&2
  exit 1
}

wp() { docker compose run --rm wpcli --allow-root "$@"; }
install_plugin() {
  local slug="$1"
  wp plugin is-installed "$slug" >/dev/null 2>&1 || wp plugin install "$slug"
  wp plugin activate "$slug"
}

echo "Installing the maintained free Iranian commerce add-ons."
install_plugin persian-woocommerce-sms
install_plugin persian-woocommerce-shipping

# Gateland is already part of the main installer, but keep this command safe for
# older installations where it may be missing.
install_plugin gateland

wp language plugin install persian-woocommerce-sms fa_IR >/dev/null 2>&1 || true
wp language plugin install persian-woocommerce-shipping fa_IR >/dev/null 2>&1 || true
wp plugin auto-updates enable persian-woocommerce-sms persian-woocommerce-shipping gateland >/dev/null 2>&1 || true

cat <<'EOF'

Iranian commerce add-ons are active.

Required SMS setup:
  1. Open the Persian WooCommerce SMS settings in wp-admin.
  2. Select your SMS panel and enter its API credentials.
  3. Enable the administrator notification for a new/processing order.
  4. Enter the barber's mobile number and send a test message.
  5. Use a service/pattern template approved by the SMS provider for reliable delivery.

Shipping setup:
  1. Open WooCommerce > Settings > Shipping.
  2. Add a shipping zone for Iran.
  3. Add only the methods you really use: post, Tipax, or local courier.
  4. Configure origin, weights, prices, cities, and optional Tapin connection.

The storefront already supports password login with mobile number or email.
OTP login is intentionally not enabled by this installer because it requires a
separate provider, rate limiting, resend limits, and verified gateway support.
See docs/SMS-AND-IRAN-COMMERCE.fa.md before enabling OTP.
EOF
