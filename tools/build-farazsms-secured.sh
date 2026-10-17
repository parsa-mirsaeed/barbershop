#!/usr/bin/env bash
# Rebuild the Montix security-reviewed FarazSMS 3.22.273 from the exact merchant-supplied ZIP.
set -euo pipefail
if [[ $# -ne 2 ]]; then
  echo "Usage: $0 faraz-sms-3.22.273.zip patched-farazsms.zip" >&2
  exit 2
fi
ORIGINAL="$(realpath "$1")"
OUT="$(realpath -m "$2")"
EXPECTED='bad42bdb6497411f2fde7e9525efd43af112c5c5e171faf5c098d99203b03b9d'
ACTUAL="$(sha256sum "$ORIGINAL" | awk '{print $1}')"
if [[ "$ACTUAL" != "$EXPECTED" ]]; then
  echo "Source ZIP mismatch; do not apply this security patch to other FarazSMS versions." >&2
  exit 1
fi
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PATCH_FILE="$HERE/../vendor-patches/farazsms-3.22.273-montix.2.patch"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
unzip -q "$ORIGINAL" -d "$TMP"
(cd "$TMP" && patch --batch --forward -p1 < "$PATCH_FILE")
find "$TMP/faraz-sms" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
(cd "$TMP" && zip -q -r "$OUT" faraz-sms -x '*/.DS_Store')
echo "Rebuilt vetted FarazSMS fork: $OUT"
sha256sum "$OUT"
