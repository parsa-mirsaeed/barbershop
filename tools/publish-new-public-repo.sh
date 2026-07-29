#!/usr/bin/env bash
set -euo pipefail

REPO_NAME="${1:-persian-barbershop-wordpress}"

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI is required: https://cli.github.com/" >&2
  exit 1
fi

gh auth status >/dev/null

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Working tree must be clean before publication." >&2
  exit 1
fi

python3 tools/scan-secrets.py
python3 tools/validate.py
find theme plugin -name '*.php' -print0 | xargs -0 -n1 php -l
node --check plugin/barbershop-core/assets/admin.js
node --check plugin/barbershop-core/assets/frontend.js

gh repo create "$REPO_NAME" \
  --public \
  --description "Generalized Persian RTL WordPress barbershop theme, privacy-aware portfolio plugin, and safe local Docker stack" \
  --source=. \
  --remote=origin \
  --push

echo "Published: $(gh repo view --json url --jq .url)"
