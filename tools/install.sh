#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

RESET_LOCAL=0
case "${1:-}" in
  "") ;;
  --reset|--fresh) RESET_LOCAL=1 ;;
  --help|-h)
    cat <<'EOF'
Usage: ./tools/install.sh [--reset]

  --reset, --fresh  Delete only this project's local Docker volumes and reinstall.
                    Use this when an old clone left database/WordPress volumes
                    whose credentials no longer match the current .env file.
EOF
    exit 0
    ;;
  *)
    echo "Unknown option: $1" >&2
    echo "Usage: ./tools/install.sh [--reset]" >&2
    exit 2
    ;;
esac

command -v docker >/dev/null 2>&1 || { echo "Docker is required." >&2; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "Docker Compose v2 is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required by the local installer." >&2; exit 1; }

random_secret() {
  if command -v openssl >/dev/null 2>&1; then openssl rand -hex 32; else python3 -c 'import secrets; print(secrets.token_hex(32))'; fi
}

set_env_value() {
  local name="$1"
  local value="$2"
  python3 - "$name" "$value" <<'PY'
from pathlib import Path
import re
import sys
path = Path('.env')
name, value = sys.argv[1], sys.argv[2]
text = path.read_text()
line = f'{name}={value}'
pattern = re.compile(rf'^{re.escape(name)}=.*$', re.MULTILINE)
path.write_text(pattern.sub(line, text) if pattern.search(text) else text.rstrip() + '\n' + line + '\n')
PY
}

port_is_free() {
  local port="$1"
  python3 - "$port" <<'PY'
import socket, sys
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
try:
    sock.bind(('127.0.0.1', int(sys.argv[1])))
except OSError:
    raise SystemExit(1)
finally:
    sock.close()
PY
}

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "Created .env from .env.example."
fi

# Existing clones may already have an .env file containing example values.
# Replace only missing, empty, or example secrets; preserve user-supplied values.
DB_PASSWORD_VALUE="$(random_secret)"
DB_ROOT_PASSWORD_VALUE="$(random_secret)"
WP_ADMIN_PASSWORD_VALUE="$(random_secret)"
generated_secret_names="$(python3 - "$DB_PASSWORD_VALUE" "$DB_ROOT_PASSWORD_VALUE" "$WP_ADMIN_PASSWORD_VALUE" <<'PY'
from pathlib import Path
import re
import sys

path = Path('.env')
text = path.read_text()
replacements = {
    'DB_PASSWORD': sys.argv[1],
    'DB_ROOT_PASSWORD': sys.argv[2],
    'WP_ADMIN_PASSWORD': sys.argv[3],
}
changed = []

for name, replacement in replacements.items():
    pattern = re.compile(rf'^{re.escape(name)}=(.*)$', re.MULTILINE)
    match = pattern.search(text)
    if match:
        value = match.group(1).strip().strip('"').strip("'")
        if not value or value.startswith('replace-with-'):
            text = text[:match.start()] + f'{name}={replacement}' + text[match.end():]
            changed.append(name)
    else:
        text = text.rstrip() + f'\n{name}={replacement}\n'
        changed.append(name)

path.write_text(text)
print(' '.join(changed))
PY
)"

if [[ -n "$generated_secret_names" ]]; then
  echo "Generated secure local values for: $generated_secret_names"
  echo "Review WP_TITLE and WP_ADMIN_EMAIL in .env when needed."
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

for required_name in DB_PASSWORD DB_ROOT_PASSWORD WP_ADMIN_PASSWORD; do
  required_value="${!required_name:-}"
  if [[ -z "$required_value" || "$required_value" == replace-with-* ]]; then
    echo "Unable to generate a valid value for $required_name in .env." >&2
    exit 1
  fi
done

if (( RESET_LOCAL )); then
  echo "Resetting this project's local Docker data before installation."
  echo "This removes the local WordPress and MariaDB volumes for this Compose project only."
  docker compose down -v --remove-orphans || true
fi

requested_port="${WP_PORT:-8080}"
if ! [[ "$requested_port" =~ ^[0-9]+$ ]] || (( requested_port < 1 || requested_port > 65535 )); then
  echo "WP_PORT must be an integer from 1 to 65535." >&2
  exit 1
fi

wordpress_running="$(docker compose ps --status running -q wordpress 2>/dev/null || true)"
if [[ -z "$wordpress_running" ]] && ! port_is_free "$requested_port"; then
  if [[ -n "${WP_URL:-}" ]]; then
    echo "Port $requested_port is already occupied and WP_URL is explicitly set to '$WP_URL'." >&2
    echo "Choose matching WP_PORT and WP_URL values in .env, or stop the service using that port." >&2
    exit 1
  fi
  fallback_port=""
  for candidate in $(seq $((requested_port + 1)) $((requested_port + 100))); do
    if (( candidate <= 65535 )) && port_is_free "$candidate"; then fallback_port="$candidate"; break; fi
  done
  if [[ -z "$fallback_port" ]]; then
    echo "Port $requested_port is occupied and no free port was found in the next 100 ports." >&2
    echo "Set WP_PORT manually in .env." >&2
    exit 1
  fi
  echo "Port $requested_port is already occupied. Trying local development port $fallback_port instead."
  echo "Updated WP_PORT in .env. Production does not use this fallback; Caddy still requires ports 80 and 443."
  set_env_value WP_PORT "$fallback_port"
  WP_PORT="$fallback_port"
fi

WP_URL="${WP_URL:-http://127.0.0.1:${WP_PORT:-8080}}"

wp() { docker compose run --rm wpcli --allow-root "$@"; }
install_plugin() {
  local slug="$1"
  wp plugin is-installed "$slug" >/dev/null 2>&1 || wp plugin install "$slug"
  wp plugin activate "$slug"
}

# Rebuild both PHP runtimes so payment plugins that use PDO (including
# Gateland) cannot crash wp-admin, checkout, or WP-CLI with a missing driver.
echo "Building the WordPress and WP-CLI payment-compatible PHP images."
docker compose build wordpress wpcli
docker compose up -d database wordpress

printf 'Waiting for MariaDB credentials'
database_ready=0
for _ in $(seq 1 60); do
  if docker compose exec -T database mariadb --protocol=TCP -h 127.0.0.1 \
    -u"${DB_USER:-wordpress}" "-p${DB_PASSWORD}" "${DB_NAME:-wordpress}" \
    -Nse 'SELECT 1' >/dev/null 2>&1; then
    database_ready=1
    echo
    break
  fi
  printf '.'
  sleep 3
done

if (( ! database_ready )); then
  echo >&2
  echo "The database container started, but the credentials in .env do not match its existing data." >&2
  echo "This commonly happens after re-cloning the project while old Docker volumes remain." >&2
  echo >&2
  echo "For a clean local reinstall that deletes only this project's Docker data, run:" >&2
  echo "  ./tools/install.sh --reset" >&2
  echo >&2
  echo "To preserve old local data, restore the previous .env file instead, then rerun the installer." >&2
  docker compose ps >&2 || true
  exit 1
fi

printf 'Waiting for WordPress files'
for _ in $(seq 1 60); do
  if wp core version >/dev/null 2>&1; then echo; break; fi
  printf '.'; sleep 3
done
wp core version >/dev/null

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="$WP_URL" --title="${WP_TITLE:-[نام برند]}" --admin_user="${WP_ADMIN_USER:-siteadmin}" --admin_password="${WP_ADMIN_PASSWORD:?Set WP_ADMIN_PASSWORD}" --admin_email="${WP_ADMIN_EMAIL:-admin@example.test}" --skip-email
fi

wp option update home "$WP_URL"
wp option update siteurl "$WP_URL"
wp language core install fa_IR --activate >/dev/null 2>&1 || echo "Persian core language pack could not be installed; continuing with the current locale."
wp rewrite structure '/%postname%/' --hard
install_plugin woocommerce
install_plugin zarinpal-woocommerce-payment-gateway
install_plugin gateland
install_plugin wordfence
wp language plugin install woocommerce fa_IR >/dev/null 2>&1 || echo "Persian WooCommerce language pack was unavailable; built-in Persian fallbacks remain active."
wp language plugin install zarinpal-woocommerce-payment-gateway fa_IR >/dev/null 2>&1 || true
wp language plugin install gateland fa_IR >/dev/null 2>&1 || true
wp language plugin install wordfence fa_IR >/dev/null 2>&1 || true
wp theme activate persian-barbershop
wp plugin activate barbershop-core
wp bsc setup
wp bsc font install
wp plugin auto-updates enable woocommerce zarinpal-woocommerce-payment-gateway gateland wordfence >/dev/null 2>&1 || true
wp rewrite flush --hard

printf '\nInstallation complete: %s\nAdmin: %s/wp-admin/\nBarber dashboard: %s/wp-admin/admin.php?page=bsc-store-setup\n' "$WP_URL" "$WP_URL" "$WP_URL"
printf 'Customer login and registration: %s/my-account/\n' "$WP_URL"
printf 'Payment setup: configure ZarinPal or Gateland under WooCommerce > Settings > Payments before accepting live orders.\n'
printf 'Security next step: open Wordfence, finish firewall optimization, configure alerts, and enable 2FA for administrator and barber accounts.\n'
