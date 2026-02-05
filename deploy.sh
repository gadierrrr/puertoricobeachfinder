#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

echo "[1/6] PHP syntax lint"
find . -type f -name '*.php' \
  -not -path './.git/*' \
  -not -path './node_modules/*' \
  -print0 | xargs -0 -n1 php -l > /tmp/php-lint.log

echo "[2/6] Install Node dependencies"
npm ci

echo "[3/6] Build frontend assets"
npm run build

echo "[4/6] Validate generated assets are committed"
git diff --exit-code -- public/assets/css/tailwind.min.css public/assets/js/app.min.js public/assets/js/collection-explorer.min.js

echo "[5/6] Run migrations"
php scripts/migrate.php


echo "[6/6] Smoke checks"
php scripts/migrate.php --check
GOOGLE_KEY_PREFIX="AI""za"
if grep -R "$GOOGLE_KEY_PREFIX" . --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=audit-results --exclude-dir=data --exclude='*.md'; then
  echo "Found Google API key pattern in active code."
  exit 1
fi

echo "Deploy checks passed."
