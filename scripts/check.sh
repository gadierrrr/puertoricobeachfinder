#!/bin/bash
#
# Local verification harness — run before reporting a change done:  npm run check
#
# Hard checks (fail the run): PHP lint sweep, duplicate global functions, route
# smoke test. Warn-only checks (surface but don't fail): design-system lint and
# migration status, which can be noisy independent of the current change.
#
set -uo pipefail
cd "$(dirname "$0")/.."

fail=0

echo "== 1/5 PHP lint sweep =="
lint_fail=0
while IFS= read -r -d '' f; do
  if ! out=$(php -l "$f" 2>&1); then
    echo "$out"
    lint_fail=1
  fi
done < <(find inc components public scripts templates -name '*.php' -not -path '*/vendor/*' -print0 2>/dev/null)
if [ "$lint_fail" -ne 0 ]; then
  echo "✗ PHP syntax errors found"
  fail=1
else
  echo "✓ No PHP syntax errors"
fi

echo ""
echo "== 2/5 Duplicate global function check =="
php scripts/check-duplicate-functions.php || fail=1

echo ""
echo "== 3/5 Route smoke test =="
php scripts/smoke-routes.php || fail=1

echo ""
echo "== 4/5 Design-system lint (warn-only) =="
if node scripts/lint-design-system.js; then
  echo "✓ Design-system lint clean"
else
  echo "⚠ Design-system lint reported issues (not failing the run)"
fi

echo ""
echo "== 5/5 Migration status (warn-only) =="
if php scripts/migrate.php --check >/dev/null 2>&1; then
  echo "✓ No pending migrations"
else
  echo "⚠ Pending migrations exist — run: php scripts/migrate.php (not failing the run)"
fi

echo ""
if [ "$fail" -ne 0 ]; then
  echo "✗ check FAILED"
  exit 1
fi
echo "✓ All hard checks passed"
