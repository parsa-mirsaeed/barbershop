#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

OUT="dist/shared-hosting"
rm -rf "$OUT"
mkdir -p "$OUT/mu-plugins"

python3 tools/scan-secrets.py
python3 tools/validate.py

VERSION="$(awk -F': ' '/^ \* Version:/ {print $2; exit}' plugin/barbershop-core/barbershop-core.php | tr -d '\r')"
[[ -n "$VERSION" ]] || { echo "Could not determine Barbershop Core version." >&2; exit 1; }

(
  cd theme
  zip -qr "../$OUT/persian-barbershop-theme.zip" persian-barbershop -x '*.DS_Store' '*/.git/*' '*/debug.log'
)
(
  cd plugin
  zip -qr "../$OUT/barbershop-core-plugin.zip" barbershop-core -x '*.DS_Store' '*/.git/*' '*/debug.log'
)

cp hosting/mu-plugins/barbershop-hosting-guard.php "$OUT/mu-plugins/"
cp hosting/.user.ini.recommended "$OUT/"
cp hosting/wp-config.production.snippet.txt "$OUT/"
cp docs/SHARED-HOSTING-PRODUCTION.md "$OUT/README-SHARED-HOSTING.md"
cp tools/install-shared-hosting.sh "$OUT/"
cp tools/shared-hosting-audit.sh "$OUT/"
chmod 0755 "$OUT/install-shared-hosting.sh" "$OUT/shared-hosting-audit.sh"

GIT_SHA="unknown"
if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  GIT_SHA="$(git rev-parse HEAD)"
fi

python3 - "$OUT" "$VERSION" "$GIT_SHA" <<'PY'
import json, pathlib, sys, datetime
out = pathlib.Path(sys.argv[1])
manifest = {
    "schema": 1,
    "package": "barbershop-shared-hosting",
    "barbershop_core_version": sys.argv[2],
    "git_sha": sys.argv[3],
    "built_utc": datetime.datetime.now(datetime.timezone.utc).replace(microsecond=0).isoformat(),
    "deployment": "shared-hosting",
    "requires": {
        "wordpress": "6.6+",
        "php_min": "7.4",
        "php_production_recommended": "8.2+",
        "critical_extensions": ["pdo_mysql", "mysqli", "curl", "mbstring", "openssl", "fileinfo"],
    },
    "third_party_plugins_bundled": False,
    "installers": {
        "manual": "README-SHARED-HOSTING.md",
        "wp_cli_optional": "install-shared-hosting.sh",
        "external_audit": "shared-hosting-audit.sh"
    }
}
(out / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
PY

(
  cd "$OUT"
  sha256sum persian-barbershop-theme.zip barbershop-core-plugin.zip mu-plugins/barbershop-hosting-guard.php .user.ini.recommended wp-config.production.snippet.txt README-SHARED-HOSTING.md install-shared-hosting.sh shared-hosting-audit.sh manifest.json > SHA256SUMS
)

# Refuse to ship obvious secrets or development artifacts.
if grep -RIE --exclude='*.zip' --exclude='SHA256SUMS' '(DB_PASSWORD=|DB_ROOT_PASSWORD=|WP_ADMIN_PASSWORD=|BEGIN (RSA|OPENSSH|EC) PRIVATE KEY|api[_-]?key[[:space:]]*=)' "$OUT" >/dev/null; then
  echo "Potential secret found in shared-hosting release." >&2
  exit 1
fi

BUNDLE="dist/barbershop-shared-hosting-${VERSION}.zip"
rm -f "$BUNDLE"
(
  cd "$OUT"
  zip -qr "../$(basename "$BUNDLE")" .
)

echo "Shared-hosting release created:"
echo "  $OUT/persian-barbershop-theme.zip"
echo "  $OUT/barbershop-core-plugin.zip"
echo "  $BUNDLE"
echo "Verify SHA256SUMS before uploading to production."
