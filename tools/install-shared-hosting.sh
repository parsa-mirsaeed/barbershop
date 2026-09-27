#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUNDLE_DIR="${BUNDLE_DIR:-$SCRIPT_DIR}"
WP_PATH="$(pwd)"
IRAN_COMMERCE=0
SKIP_GATELAND=0

usage() {
  cat <<'EOF'
Usage: install-shared-hosting.sh [--path /home/account/public_html] [--iran-commerce] [--skip-gateland]

Run from the extracted shared-hosting bundle on a host that provides user-level
SSH and WP-CLI. WordPress must already be installed. No root access is used.
EOF
}

while (($#)); do
  case "$1" in
    --path) WP_PATH="${2:?Missing path}"; shift 2 ;;
    --iran-commerce) IRAN_COMMERCE=1; shift ;;
    --skip-gateland) SKIP_GATELAND=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage >&2; exit 2 ;;
  esac
done

command -v wp >/dev/null 2>&1 || { echo "WP-CLI is not available. Use the cPanel/DirectAdmin manual installation in README-SHARED-HOSTING.md." >&2; exit 2; }

wp_cmd=(wp --path="$WP_PATH")
"${wp_cmd[@]}" core is-installed >/dev/null 2>&1 || { echo "WordPress is not installed at $WP_PATH" >&2; exit 1; }

THEME_ZIP="$BUNDLE_DIR/persian-barbershop-theme.zip"
CORE_ZIP="$BUNDLE_DIR/barbershop-core-plugin.zip"
GUARD="$BUNDLE_DIR/mu-plugins/barbershop-hosting-guard.php"
for file in "$THEME_ZIP" "$CORE_ZIP" "$GUARD"; do
  [[ -f "$file" ]] || { echo "Missing release file: $file" >&2; exit 1; }
done

HOME_URL="$("${wp_cmd[@]}" option get home 2>/dev/null || true)"
case "$HOME_URL" in
  https://*) ;;
  *) echo "WARNING: WordPress home URL is not HTTPS yet: ${HOME_URL:-unknown}" >&2 ;;
esac

MU_DIR="$("${wp_cmd[@]}" eval 'echo WPMU_PLUGIN_DIR;' 2>/dev/null)"
[[ -n "$MU_DIR" ]] || { echo "Could not resolve WPMU_PLUGIN_DIR." >&2; exit 1; }
mkdir -p "$MU_DIR"
cp "$GUARD" "$MU_DIR/barbershop-hosting-guard.php"
chmod 0644 "$MU_DIR/barbershop-hosting-guard.php" 2>/dev/null || true

echo "Installed early shared-hosting MU guard."

install_plugin() {
  local slug="$1"
  if "${wp_cmd[@]}" plugin is-installed "$slug" >/dev/null 2>&1; then
    "${wp_cmd[@]}" plugin update "$slug" >/dev/null 2>&1 || true
    "${wp_cmd[@]}" plugin activate "$slug"
  else
    "${wp_cmd[@]}" plugin install "$slug" --activate
  fi
}

"${wp_cmd[@]}" theme install "$THEME_ZIP" --force --activate
"${wp_cmd[@]}" plugin install "$CORE_ZIP" --force --activate
install_plugin woocommerce
install_plugin wordfence
install_plugin zarinpal-woocommerce-payment-gateway

if (( IRAN_COMMERCE )); then
  install_plugin persian-woocommerce-sms
  install_plugin persian-woocommerce-shipping
fi

if (( ! SKIP_GATELAND )); then
  if "${wp_cmd[@]}" eval 'exit(extension_loaded("pdo_mysql") ? 0 : 1);' >/dev/null 2>&1; then
    install_plugin gateland
  else
    echo "Gateland NOT activated: PHP pdo_mysql is missing. Ask the host to enable it first." >&2
  fi
fi

"${wp_cmd[@]}" language core install fa_IR --activate >/dev/null 2>&1 || true
"${wp_cmd[@]}" language plugin install woocommerce fa_IR >/dev/null 2>&1 || true
"${wp_cmd[@]}" bsc setup
"${wp_cmd[@]}" bsc font install || true
if "${wp_cmd[@]}" plugin is-active gateland >/dev/null 2>&1; then
  "${wp_cmd[@]}" bsc gateland repair || true
fi
"${wp_cmd[@]}" rewrite structure '/%postname%/' --hard
"${wp_cmd[@]}" rewrite flush --hard

cat <<EOF

Shared-hosting application install/update completed.
WordPress: ${HOME_URL:-$WP_PATH}

Next mandatory production steps:
1. Apply wp-config.production.snippet.txt and supported .user.ini values.
2. Open Tools -> Hosting Health and resolve every CRITICAL/red check.
3. Configure Wordfence WAF + 2FA for Administrator and Barber.
4. Configure/test payment gateway credentials before live mode.
5. Configure SMS/shipping credentials if installed.
6. Configure real cron and cache exclusions.
7. Create an encrypted off-account backup and perform a restore test.
8. Run shared-hosting-audit.sh from another machine after DNS/SSL is live.
EOF

"${wp_cmd[@]}" bsc hosting audit || {
  echo "Hosting Health contains critical failures. Do not accept live orders yet." >&2
  exit 1
}
