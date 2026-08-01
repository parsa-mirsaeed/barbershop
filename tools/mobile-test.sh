#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

usage() {
  cat <<'EOF'
Usage:
  bash tools/mobile-test.sh [LAN_IP]
  bash tools/mobile-test.sh --local
  bash tools/mobile-test.sh --status

Commands:
  [LAN_IP]   Expose the local WordPress port on the trusted private LAN. When
             omitted, the script detects this computer's private IPv4 address.
  --local    Restore loopback-only access and a 127.0.0.1 WordPress URL.
  --status   Show the current published port and WordPress home URL.

Use LAN mode only on a trusted private Wi-Fi/Ethernet network. Never configure
router port forwarding for this development port. Run --local after testing.
EOF
}

case "${1:-}" in
  --help|-h)
    usage
    exit 0
    ;;
esac

[[ -f .env ]] || {
  echo "Missing .env. Run ./tools/install.sh first." >&2
  exit 1
}
command -v docker >/dev/null 2>&1 || { echo "Docker is required." >&2; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "Docker Compose v2 is required." >&2; exit 1; }

set -a
# shellcheck disable=SC1091
source .env
set +a
WP_PORT="${WP_PORT:-8080}"

if ! [[ "$WP_PORT" =~ ^[0-9]+$ ]] || (( WP_PORT < 1 || WP_PORT > 65535 )); then
  echo "WP_PORT must be an integer from 1 to 65535." >&2
  exit 1
fi

wp() {
  docker compose run --rm wpcli --allow-root "$@"
}

wait_for_wordpress() {
  printf 'Waiting for WordPress'
  for _ in $(seq 1 60); do
    if wp core version >/dev/null 2>&1; then
      echo
      return 0
    fi
    printf '.'
    sleep 2
  done
  echo >&2
  echo "WordPress did not become ready." >&2
  docker compose ps >&2 || true
  return 1
}

private_ipv4() {
  python3 - "$1" <<'PY'
import ipaddress
import sys

try:
    address = ipaddress.ip_address(sys.argv[1])
except ValueError:
    raise SystemExit(1)

# Limit automatic exposure to conventional private LAN ranges. A caller can
# choose the correct interface explicitly, but public and loopback addresses
# are rejected in every case.
allowed = (
    ipaddress.ip_network("10.0.0.0/8"),
    ipaddress.ip_network("172.16.0.0/12"),
    ipaddress.ip_network("192.168.0.0/16"),
)
raise SystemExit(0 if address.version == 4 and any(address in net for net in allowed) else 1)
PY
}

detect_lan_ip() {
  local candidate=""
  if command -v ip >/dev/null 2>&1; then
    candidate="$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{for (i=1; i<=NF; i++) if ($i == "src") {print $(i+1); exit}}' || true)"
  fi
  if [[ -z "$candidate" ]] && command -v hostname >/dev/null 2>&1; then
    while read -r possible; do
      if private_ipv4 "$possible"; then
        candidate="$possible"
        break
      fi
    done < <(hostname -I 2>/dev/null | tr ' ' '\n' || true)
  fi
  printf '%s' "$candidate"
}

show_status() {
  echo "Published WordPress port:"
  docker compose port wordpress 80 2>/dev/null || echo "  WordPress container is not running."
  echo "WordPress home URL:"
  wp option get home 2>/dev/null || echo "  WordPress is not ready."
}

if [[ "${1:-}" == "--status" ]]; then
  show_status
  exit 0
fi

if [[ "${1:-}" == "--local" ]]; then
  export WP_BIND_ADDRESS=127.0.0.1
  local_url="http://127.0.0.1:${WP_PORT}"
  echo "Restoring loopback-only access on ${local_url}"
  docker compose up -d database wordpress
  docker compose up -d --force-recreate --no-deps wordpress
  wait_for_wordpress
  wp option update home "$local_url" >/dev/null
  wp option update siteurl "$local_url" >/dev/null
  wp cache flush >/dev/null 2>&1 || true
  echo "Local-only mode restored: ${local_url}"
  exit 0
fi

lan_ip="${1:-$(detect_lan_ip)}"
if [[ -z "$lan_ip" ]]; then
  echo "Could not detect a private LAN IPv4 address." >&2
  echo "Find it with 'ip -4 address' and pass it explicitly, for example:" >&2
  echo "  bash tools/mobile-test.sh 192.168.1.25" >&2
  exit 1
fi
if ! private_ipv4 "$lan_ip"; then
  echo "Refusing to expose WordPress on non-private or invalid address: $lan_ip" >&2
  echo "Use an address in 10/8, 172.16/12, or 192.168/16." >&2
  exit 1
fi

export WP_BIND_ADDRESS=0.0.0.0
mobile_url="http://${lan_ip}:${WP_PORT}"

echo "Enabling trusted-LAN mobile testing on ${mobile_url}"
docker compose up -d database wordpress
docker compose up -d --force-recreate --no-deps wordpress
wait_for_wordpress
wp option update home "$mobile_url" >/dev/null
wp option update siteurl "$mobile_url" >/dev/null
wp cache flush >/dev/null 2>&1 || true

if command -v curl >/dev/null 2>&1; then
  if ! curl -fsS --max-time 10 "$mobile_url/" >/dev/null; then
    echo "Warning: WordPress is running, but ${mobile_url} did not answer from this computer." >&2
    echo "Check the selected interface and the operating-system firewall." >&2
  fi
fi

cat <<EOF

Mobile test mode is ready.

1. Connect the phone and this computer to the same trusted Wi-Fi/LAN.
2. Open this address on the phone:
   ${mobile_url}
3. Test storefront, product, cart, checkout, account, and wp-admin flows.

Security:
- Do not enable router port forwarding for port ${WP_PORT}.
- Keep the firewall enabled; if prompted, allow only the private/local network.
- Restore loopback-only mode when finished:
  bash tools/mobile-test.sh --local
EOF
