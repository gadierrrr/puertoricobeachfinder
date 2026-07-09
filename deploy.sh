#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Deploy aborted: tracked working tree changes are present."
  exit 1
fi

echo "[1/9] PHP syntax lint"
find . -type f -name '*.php' \
  -not -path './.git/*' \
  -not -path './node_modules/*' \
  -print0 | xargs -0 -n1 php -l > /tmp/php-lint.log

echo "[2/9] Install Node dependencies"
npm ci

echo "[3/9] Build frontend assets"
npm run build

echo "[4/9] Validate generated assets are committed"
git diff --exit-code -- \
  public/assets/css/styles.css \
  public/assets/css/tailwind.min.css \
  public/assets/js/app.min.js \
  public/assets/js/chat.min.js \
  public/assets/js/collection-explorer.min.js

echo "[5/9] Design and route validation"
npm run check:design
php scripts/test-locale-routing.php

echo "[6/9] Preview migrations"
php scripts/migrate.php --dry-run

echo "[7/9] Create and restore-test database backup"
php scripts/backup-db.php
php scripts/restore-smoke-test.php

echo "[8/9] Run migrations"
php scripts/migrate.php

echo "[9/9] Smoke checks"
php scripts/migrate.php --check
php scripts/test-beach-images.php
GOOGLE_KEY_PREFIX="AI""za"
if grep -R "$GOOGLE_KEY_PREFIX" . --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=audit-results --exclude-dir=data --exclude='*.md'; then
  echo "Found Google API key pattern in active code."
  exit 1
fi

echo "Deploy checks passed."
