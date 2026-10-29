#!/usr/bin/env bash
# Run from your workstation using its existing GitHub and SSH logins.
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
exec python3 "$ROOT_DIR/scripts/release.py" "$@"
