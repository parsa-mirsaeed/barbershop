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
  if command -v python3 >/dev/null 2>&1; then
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
    return
  fi
  if command -v ss >/dev/null 2>&1; then ! ss -ltnH "sport = :$port" | grep -q .; return; fi
  if command -v lsof >/dev/null 2>&1; then ! lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; return; fi
  return 0
}

if [[ ! -f .env ]]; then
  cp .env.example .env
  DB_PASSWORD_VALUE="$(random_secret)"
  DB_ROOT_PASSWORD_VALUE="$(random_secret)"
  WP_ADMIN_PASSWORD_VALUE="$(random_secret)"
  python3 - "$DB_PASSWORD_VALUE" "$DB_ROOT_PASSWORD_VALUE" "$WP_ADMIN_PASSWORD_VALUE" <<'PY'
from pathlib import Path
import sys
p = Path('.env')
s = p.read_text()
s = s.replace('replace-with-a-random-local-password', sys.argv[1])
s = s.replace('replace-with-another-random-local-password', sys.argv[2])
s = s.replace('replace-with-a-strong-admin-password', sys.argv[3])
p.write_text(s)
PY
  echo "Created .env with random local passwords. Review WP_TITLE and WP_ADMIN_EMAIL when needed."
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

for required_name in DB_PASSWORD DB_ROOT_PASSWORD WP_ADMIN_PASSWORD; do
  required_value="${!required_name:-}"
  if [[ -z "$required_value" || "$required_value" == replace-with-* ]]; then
    echo "Set a non-example value for $required_name in .env." >&2
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
wp language core install fa_IR --activate >/dev/null 2>&1 || echo "Persian language pack could not be installed; continuing with the current locale."
wp rewrite structure '/%postname%/' --hard
install_plugin woocommerce
install_plugin wordfence
wp theme activate persian-barbershop
wp plugin activate barbershop-core
wp bsc setup
wp bsc font install
wp plugin auto-updates enable woocommerce wordfence >/dev/null 2>&1 || true
wp rewrite flush --hard

printf '\nInstallation complete: %s\nAdmin: %s/wp-admin/\n' "$WP_URL" "$WP_URL"
printf 'Security next step: open Wordfence, finish firewall optimization, configure alerts, and enable 2FA for administrator accounts.\n'