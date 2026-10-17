#!/usr/bin/env bash
set -euo pipefail
[[ $# -eq 1 ]] || { echo 'Usage: run-farazsms-security-tests.sh original-vendor.zip' >&2; exit 2; }
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(dirname "$HERE")"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
"$HERE/build-farazsms-secured.sh" "$1" "$TMP/secure.zip"
unzip -q "$TMP/secure.zip" -d "$TMP"
export MONTIX_FARAZ_SOURCE_DIR="$TMP/faraz-sms"
for name in security-reset.php security-otp.php security-identity.php security-updates.php; do
  php "$ROOT/tests/$name"
done
php -l "$ROOT/plugin/barbershop-core/includes/farazsms-bridge.php"
echo 'PASS: rebuild, PHP lint, OTP, reset, identity and updater security tests' 
