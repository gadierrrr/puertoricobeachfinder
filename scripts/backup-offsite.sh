#!/bin/bash
# Offsite backup: encrypt latest full backup and upload to GitHub Releases
# Usage: bash scripts/backup-offsite.sh [--keep-days=N] [--help]

set -euo pipefail
cd "$(dirname "$0")/.."

# --- Defaults ---
KEEP_DAYS=30
BACKUP_DIR="backups/full"
GPG_FILE=""
REPO="gadierrrr/puertoricobeachfinder"

# --- Cleanup trap ---
cleanup() {
    if [[ -n "$GPG_FILE" && -f "$GPG_FILE" ]]; then
        rm -f "$GPG_FILE"
    fi
}
trap cleanup EXIT

# --- Parse arguments ---
for arg in "$@"; do
    case "$arg" in
        --keep-days=*)
            KEEP_DAYS="${arg#--keep-days=}"
            if ! [[ "$KEEP_DAYS" =~ ^[0-9]+$ ]]; then
                echo '{"ok":false,"error":"--keep-days must be a non-negative integer"}' >&2
                exit 1
            fi
            ;;
        --help)
            cat <<'EOF'
Usage: bash scripts/backup-offsite.sh [OPTIONS]

Options:
  --keep-days=N      GitHub release retention days (default: 30)
  --help             Show this help

Requires:
  - BACKUP_ENCRYPTION_KEY in .env
  - gh CLI authenticated
  - gpg installed

Output: JSON summary to stdout
EOF
            exit 0
            ;;
        *)
            echo "{\"ok\":false,\"error\":\"Unknown argument: $arg\"}" >&2
            exit 1
            ;;
    esac
done

# --- Step 1: Load encryption key from .env ---
if [[ -f ".env" ]]; then
    BACKUP_ENCRYPTION_KEY=$(grep -E '^BACKUP_ENCRYPTION_KEY=' .env | cut -d= -f2- | sed 's/^["'\'']\|["'\'']*$//g' || true)
fi
BACKUP_ENCRYPTION_KEY="${BACKUP_ENCRYPTION_KEY:-${BACKUP_ENCRYPTION_KEY_ENV:-}}"

if [[ -z "$BACKUP_ENCRYPTION_KEY" ]]; then
    echo '{"ok":false,"error":"BACKUP_ENCRYPTION_KEY not found in .env"}' >&2
    exit 1
fi

# --- Step 2: Find most recent full backup ---
LATEST_TARBALL=""
LATEST_MTIME=0
for f in "${BACKUP_DIR}"/beach-finder-full-*.tar.gz; do
    [[ -f "$f" ]] || continue
    MTIME=$(stat -c%Y "$f" 2>/dev/null || stat -f%m "$f")
    if [[ "$MTIME" -gt "$LATEST_MTIME" ]]; then
        LATEST_MTIME=$MTIME
        LATEST_TARBALL="$f"
    fi
done

if [[ -z "$LATEST_TARBALL" ]]; then
    echo '{"ok":false,"error":"No full backup found in '"$BACKUP_DIR"'"}' >&2
    exit 1
fi

# Verify backup was created within last 24 hours
NOW=$(date +%s)
AGE_HOURS=$(( (NOW - LATEST_MTIME) / 3600 ))
if [[ "$AGE_HOURS" -gt 24 ]]; then
    echo "{\"ok\":false,\"error\":\"Latest backup is ${AGE_HOURS}h old (max 24h). Run backup-full.sh first.\"}" >&2
    exit 1
fi

TARBALL_NAME=$(basename "$LATEST_TARBALL")
TARBALL_SIZE=$(stat -c%s "$LATEST_TARBALL")

# --- Step 3: Encrypt with GPG symmetric ---
GPG_FILE="${LATEST_TARBALL}.gpg"
gpg --batch --yes --symmetric --cipher-algo AES256 --compress-algo none \
    --passphrase-fd 3 --output "$GPG_FILE" "$LATEST_TARBALL" \
    3<<< "$BACKUP_ENCRYPTION_KEY"

GPG_SIZE=$(stat -c%s "$GPG_FILE")
GPG_SHA256=$(sha256sum "$GPG_FILE" | cut -d' ' -f1)

# --- Step 4: Create GitHub release ---
TAG_DATE=$(date -u +%Y-%m-%d)
TAG_NAME="backup/${TAG_DATE}"
RELEASE_TITLE="Backup ${TAG_DATE}"

# Delete existing release for today if re-running
gh release delete "$TAG_NAME" --repo "$REPO" --cleanup-tag --yes 2>/dev/null || true

gh release create "$TAG_NAME" \
    --repo "$REPO" \
    --title "$RELEASE_TITLE" \
    --notes "Automated encrypted backup. Source: ${TARBALL_NAME} (${TARBALL_SIZE} bytes)" \
    --prerelease \
    "$GPG_FILE"

# --- Step 5: Verify upload ---
ASSET_COUNT=$(gh release view "$TAG_NAME" --repo "$REPO" --json assets -q '.assets | length' 2>/dev/null || echo "0")
if [[ "$ASSET_COUNT" -lt 1 ]]; then
    echo "{\"ok\":false,\"error\":\"Upload verification failed: no assets found on release $TAG_NAME\"}" >&2
    exit 1
fi

# --- Step 6: Clean up local .gpg file (handled by trap, but be explicit) ---
rm -f "$GPG_FILE"
GPG_FILE=""

# --- Step 7: Prune old GitHub backup releases ---
PRUNED=()
if [[ "$KEEP_DAYS" -gt 0 ]]; then
    CUTOFF_DATE=$(date -u -d "${KEEP_DAYS} days ago" +%Y-%m-%d 2>/dev/null || date -u -v-${KEEP_DAYS}d +%Y-%m-%d)
    # List all backup/* tags
    BACKUP_TAGS=$(gh release list --repo "$REPO" --limit 100 --json tagName -q '.[].tagName' 2>/dev/null | grep '^backup/' || true)
    for tag in $BACKUP_TAGS; do
        # Extract date from tag: backup/YYYY-MM-DD -> YYYY-MM-DD
        TAG_DATE_PART="${tag#backup/}"
        if [[ "$TAG_DATE_PART" < "$CUTOFF_DATE" ]]; then
            gh release delete "$tag" --repo "$REPO" --cleanup-tag --yes 2>/dev/null || true
            PRUNED+=("$tag")
        fi
    done
fi

# --- Step 8: Output JSON summary ---
PRUNED_JSON="[]"
if [[ ${#PRUNED[@]} -gt 0 ]]; then
    PRUNED_JSON=$(printf '%s\n' "${PRUNED[@]}" | python3 -c "import sys,json; print(json.dumps([l.strip() for l in sys.stdin]))")
fi

cat <<OUTEOF
{
  "ok": true,
  "release_tag": "${TAG_NAME}",
  "source_tarball": "${TARBALL_NAME}",
  "source_bytes": ${TARBALL_SIZE},
  "encrypted_bytes": ${GPG_SIZE},
  "encrypted_sha256": "${GPG_SHA256}",
  "keep_days": ${KEEP_DAYS},
  "pruned_releases": ${PRUNED_JSON}
}
OUTEOF
