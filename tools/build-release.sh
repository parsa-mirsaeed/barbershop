#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
rm -rf dist
mkdir -p dist
python3 tools/scan-secrets.py
python3 tools/validate.py
(
  cd theme
  zip -qr ../dist/persian-barbershop-theme.zip persian-barbershop -x '*.DS_Store'
)
(
  cd plugin
  zip -qr ../dist/barbershop-core-plugin.zip barbershop-core -x '*.DS_Store'
)
echo "Created dist/persian-barbershop-theme.zip and dist/barbershop-core-plugin.zip"
