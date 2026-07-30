#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
ENV_FILE="${1:-.env.production}"
[[ -f "$ENV_FILE" ]] || { echo "Copy .env.production.example to $ENV_FILE and replace every value." >&2; exit 1; }
command -v docker >/dev/null 2>&1 || { echo "Docker is required." >&2; exit 1; }
set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a
[[ "${SITE_DOMAIN:-}" != "shop.example.com" && -n "${SITE_DOMAIN:-}" ]] || { echo "Set a real SITE_DOMAIN." >&2; exit 1; }
[[ "${DB_PASSWORD:-}" != replace-* && "${DB_ROOT_PASSWORD:-}" != replace-* && "${WP_ADMIN_PASSWORD:-}" != replace-* ]] || { echo "Replace all example passwords." >&2; exit 1; }
COMPOSE=(docker compose --env-file "$ENV_FILE" -f compose.production.yaml)
wp() { "${COMPOSE[@]}" run --rm wpcli --allow-root "$@"; }
install_plugin() {
  local slug="$1"
  wp plugin is-installed "$slug" >/dev/null 2>&1 || wp plugin install "$slug"
  wp plugin activate "$slug"
}
"${COMPOSE[@]}" up -d database wordpress caddy
for _ in $(seq 1 60); do wp core version >/dev/null 2>&1 && break; sleep 3; done
wp core version >/dev/null
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="https://${SITE_DOMAIN}" --title="${WP_TITLE:-[نام برند]}" --admin_user="${WP_ADMIN_USER:-siteadmin}" --admin_password="${WP_ADMIN_PASSWORD:?Set WP_ADMIN_PASSWORD}" --admin_email="${WP_ADMIN_EMAIL:?Set WP_ADMIN_EMAIL}" --skip-email
fi
wp option update home "https://${SITE_DOMAIN}"
wp option update siteurl "https://${SITE_DOMAIN}"
wp language core install fa_IR --activate >/dev/null 2>&1 || echo "Persian core language pack could not be installed; continuing with the current locale."
install_plugin woocommerce
install_plugin wordfence
wp language plugin install woocommerce fa_IR >/dev/null 2>&1 || echo "Persian WooCommerce language pack was unavailable; built-in Persian fallbacks remain active."
wp language plugin install wordfence fa_IR >/dev/null 2>&1 || true
wp theme activate persian-barbershop
wp plugin activate barbershop-core
wp bsc setup
wp bsc font install
wp plugin auto-updates enable woocommerce wordfence >/dev/null 2>&1 || true
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard
printf 'Deployment started at https://%s\n' "$SITE_DOMAIN"
printf 'Barber dashboard: https://%s/wp-admin/admin.php?page=bsc-store-setup\n' "$SITE_DOMAIN"
printf 'Required post-deploy step: finish Wordfence firewall optimization, alerts, and administrator/barber 2FA; then configure encrypted backups and a tested restore procedure.\n'
