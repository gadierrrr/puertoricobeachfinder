#!/bin/bash
# Restore from local tarball or GitHub release
# Dry-run by default -- must pass --confirm to execute
#
# Usage:
#   bash scripts/backup-restore.sh --from-file=backups/full/backup.tar.gz
#   bash scripts/backup-restore.sh --from-file=backup.tar.gz.gpg
#   bash scripts/backup-restore.sh --from-release=backup/2026-03-06
#   bash scripts/backup-restore.sh --from-release=latest
#   bash scripts/backup-restore.sh --from-release=latest --confirm
#   bash scripts/backup-restore.sh --from-release=latest --confirm --skip-uploads

set -euo pipefail
cd "$(dirname "$0")/.."

APP_ROOT=$(pwd)
REPO="gadierrrr/puertoricobeachfinder"
STAGING_DIR=""
CONFIRM=false
SKIP_UPLOADS=false
FROM_FILE=""
FROM_RELEASE=""
DB_PATH="data/beach-finder.db"
PRE_RESTORE_DIR="backups/pre-restore"

# --- Cleanup trap ---
cleanup() {
    if [[ -n "$STAGING_DIR" && -d "$STAGING_DIR" ]]; then
        rm -rf "$STAGING_DIR"
    fi
}
trap cleanup EXIT

# --- Parse arguments ---
for arg in "$@"; do
    case "$arg" in
        --from-file=*)
            FROM_FILE="${arg#--from-file=}"
            ;;
        --from-release=*)
            FROM_RELEASE="${arg#--from-release=}"
            ;;
        --confirm)
            CONFIRM=true
            ;;
        --skip-uploads)
            SKIP_UPLOADS=true
            ;;
        --help)
            cat <<'EOF'
Usage: bash scripts/backup-restore.sh [OPTIONS]

Source (pick one):
  --from-file=PATH       Local .tar.gz or .tar.gz.gpg
  --from-release=TAG     GitHub release tag (e.g. backup/2026-03-06 or "latest")

Options:
  --confirm              Actually restore (dry-run by default)
  --skip-uploads         Skip restoring uploads/ directory
  --help                 Show this help

Requires BACKUP_ENCRYPTION_KEY in .env for encrypted files or GitHub downloads.
EOF
            exit 0
            ;;
        *)
            echo "{\"ok\":false,\"error\":\"Unknown argument: $arg\"}" >&2
            exit 1
            ;;
    esac
done

if [[ -z "$FROM_FILE" && -z "$FROM_RELEASE" ]]; then
    echo '{"ok":false,"error":"Must specify --from-file=PATH or --from-release=TAG"}' >&2
    exit 1
fi

if [[ -n "$FROM_FILE" && -n "$FROM_RELEASE" ]]; then
    echo '{"ok":false,"error":"Cannot specify both --from-file and --from-release"}' >&2
    exit 1
fi

# --- Load encryption key ---
BACKUP_ENCRYPTION_KEY=""
if [[ -f ".env" ]]; then
    BACKUP_ENCRYPTION_KEY=$(grep -E '^BACKUP_ENCRYPTION_KEY=' .env | cut -d= -f2- | sed 's/^["'\'']\|["'\'']*$//g' || true)
fi

# --- Step 1: Obtain the tarball ---
STAGING_DIR=$(mktemp -d "/tmp/beach-finder-restore-XXXXXX")
TARBALL_PATH=""

if [[ -n "$FROM_RELEASE" ]]; then
    # Download from GitHub
    if [[ -z "$BACKUP_ENCRYPTION_KEY" ]]; then
        echo '{"ok":false,"error":"BACKUP_ENCRYPTION_KEY required for GitHub release downloads"}' >&2
        exit 1
    fi

    if [[ "$FROM_RELEASE" = "latest" ]]; then
        # Find the most recent backup/* release
        FROM_RELEASE=$(gh release list --repo "$REPO" --limit 100 --json tagName -q '.[].tagName' 2>/dev/null \
            | grep '^backup/' | sort -r | head -1 || true)
        if [[ -z "$FROM_RELEASE" ]]; then
            echo '{"ok":false,"error":"No backup releases found on GitHub"}' >&2
            exit 1
        fi
        echo "Resolved latest release: $FROM_RELEASE" >&2
    fi

    echo "Downloading from release: $FROM_RELEASE ..." >&2
    gh release download "$FROM_RELEASE" --repo "$REPO" --dir "$STAGING_DIR" --pattern "*.gpg" 2>&1 >&2 || {
        echo "{\"ok\":false,\"error\":\"Failed to download release $FROM_RELEASE\"}" >&2
        exit 1
    }

    GPG_FILE=$(find "$STAGING_DIR" -name "*.gpg" -type f | head -1)
    if [[ -z "$GPG_FILE" || ! -f "$GPG_FILE" ]]; then
        echo '{"ok":false,"error":"No .gpg file found in downloaded release"}' >&2
        exit 1
    fi

    echo "Decrypting ..." >&2
    TARBALL_PATH="${GPG_FILE%.gpg}"
    gpg --batch --yes --decrypt \
        --passphrase-fd 3 --output "$TARBALL_PATH" "$GPG_FILE" \
        3<<< "$BACKUP_ENCRYPTION_KEY"
    rm -f "$GPG_FILE"

elif [[ -n "$FROM_FILE" ]]; then
    # Resolve relative paths
    if [[ ! "$FROM_FILE" = /* ]]; then
        FROM_FILE="${APP_ROOT}/${FROM_FILE}"
    fi

    if [[ ! -f "$FROM_FILE" ]]; then
        echo "{\"ok\":false,\"error\":\"File not found: $FROM_FILE\"}" >&2
        exit 1
    fi

    # Auto-detect encrypted files
    if [[ "$FROM_FILE" = *.gpg ]]; then
        if [[ -z "$BACKUP_ENCRYPTION_KEY" ]]; then
            echo '{"ok":false,"error":"BACKUP_ENCRYPTION_KEY required for .gpg files"}' >&2
            exit 1
        fi
        echo "Decrypting ..." >&2
        TARBALL_PATH="${STAGING_DIR}/$(basename "${FROM_FILE%.gpg}")"
        gpg --batch --yes --decrypt \
            --passphrase-fd 3 --output "$TARBALL_PATH" "$FROM_FILE" \
            3<<< "$BACKUP_ENCRYPTION_KEY"
    else
        TARBALL_PATH="$FROM_FILE"
    fi
fi

# --- Step 2: Extract tarball ---
EXTRACT_DIR="${STAGING_DIR}/extracted"
mkdir -p "$EXTRACT_DIR"
tar -xzf "$TARBALL_PATH" -C "$EXTRACT_DIR"

# --- Step 3: Verify SHA256SUMS ---
if [[ ! -f "${EXTRACT_DIR}/SHA256SUMS" ]]; then
    echo '{"ok":false,"error":"SHA256SUMS manifest not found in backup"}' >&2
    exit 1
fi

echo "Verifying checksums ..." >&2
(cd "$EXTRACT_DIR" && sha256sum -c SHA256SUMS) >&2 || {
    echo '{"ok":false,"error":"Checksum verification failed"}' >&2
    exit 1
}

# --- Step 4: Verify DB via smoke test ---
if [[ ! -f "${EXTRACT_DIR}/beach-finder.db" ]]; then
    echo '{"ok":false,"error":"beach-finder.db not found in backup"}' >&2
    exit 1
fi

echo "Running DB smoke test ..." >&2
SMOKE_OUTPUT=$(php scripts/restore-smoke-test.php --backup="${EXTRACT_DIR}/beach-finder.db" --keep-restored 2>&1) || {
    echo "{\"ok\":false,\"error\":\"DB smoke test failed\",\"detail\":$(echo "$SMOKE_OUTPUT" | python3 -c 'import sys,json; print(json.dumps(sys.stdin.read()))')}" >&2
    exit 1
}

SMOKE_OK=$(echo "$SMOKE_OUTPUT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('ok', False))" 2>/dev/null || echo "false")
if [[ "$SMOKE_OK" != "True" ]]; then
    echo "{\"ok\":false,\"error\":\"DB smoke test did not pass\",\"detail\":$(echo "$SMOKE_OUTPUT" | python3 -c 'import sys,json; print(json.dumps(sys.stdin.read()))')}" >&2
    exit 1
fi

# --- Inventory what will be restored ---
HAS_DB=true
HAS_ENV=false
HAS_GSC=false
HAS_UPLOADS=false
UPLOADS_COUNT=0

[[ -f "${EXTRACT_DIR}/env.bak" ]] && HAS_ENV=true
[[ -f "${EXTRACT_DIR}/gsc-tokens.json" ]] && HAS_GSC=true
if [[ -d "${EXTRACT_DIR}/uploads" ]]; then
    HAS_UPLOADS=true
    UPLOADS_COUNT=$(find "${EXTRACT_DIR}/uploads" -type f | wc -l)
fi

echo "" >&2
echo "=== Restore Summary ===" >&2
echo "  Database:     YES (beach-finder.db)" >&2
echo "  .env config:  $HAS_ENV" >&2
echo "  GSC tokens:   $HAS_GSC" >&2
echo "  Uploads:      $HAS_UPLOADS ($UPLOADS_COUNT files)$([ "$SKIP_UPLOADS" = true ] && echo ' [SKIPPED]')" >&2
echo "" >&2

if [[ "$CONFIRM" = false ]]; then
    echo "DRY RUN -- pass --confirm to execute restore" >&2
    cat <<OUTEOF
{
  "ok": true,
  "dry_run": true,
  "has_db": $HAS_DB,
  "has_env": $HAS_ENV,
  "has_gsc_tokens": $HAS_GSC,
  "has_uploads": $HAS_UPLOADS,
  "uploads_count": $UPLOADS_COUNT,
  "skip_uploads": $SKIP_UPLOADS,
  "message": "Dry run complete. Pass --confirm to execute."
}
OUTEOF
    exit 0
fi

# === CONFIRMED RESTORE ===

# --- Step 5: Snapshot current live data ---
SNAPSHOT_TS=$(date -u +%Y%m%d_%H%M%S)
SNAPSHOT_DIR="${PRE_RESTORE_DIR}/${SNAPSHOT_TS}"
mkdir -p "$SNAPSHOT_DIR"
echo "Snapshotting current state to $SNAPSHOT_DIR ..." >&2

if [[ -f "$DB_PATH" ]]; then
    cp "$DB_PATH" "${SNAPSHOT_DIR}/beach-finder.db"
    [[ -f "${DB_PATH}-wal" ]] && cp "${DB_PATH}-wal" "${SNAPSHOT_DIR}/beach-finder.db-wal"
    [[ -f "${DB_PATH}-shm" ]] && cp "${DB_PATH}-shm" "${SNAPSHOT_DIR}/beach-finder.db-shm"
fi
[[ -f ".env" ]] && cp ".env" "${SNAPSHOT_DIR}/env.bak"
[[ -f "data/gsc-tokens.json" ]] && cp "data/gsc-tokens.json" "${SNAPSHOT_DIR}/gsc-tokens.json"

# --- Step 6: Restore database ---
echo "Stopping php8.3-fpm ..." >&2
systemctl stop php8.3-fpm

echo "Restoring database ..." >&2
cp "${EXTRACT_DIR}/beach-finder.db" "$DB_PATH"
rm -f "${DB_PATH}-wal" "${DB_PATH}-shm"
chown www-data:www-data "$DB_PATH"
chmod 664 "$DB_PATH"

echo "Starting php8.3-fpm ..." >&2
systemctl start php8.3-fpm

# --- Step 7: Restore config files ---
if [[ "$HAS_ENV" = true ]]; then
    echo "Restoring .env ..." >&2
    cp "${EXTRACT_DIR}/env.bak" ".env"
    chmod 600 ".env"
fi

if [[ "$HAS_GSC" = true ]]; then
    echo "Restoring gsc-tokens.json ..." >&2
    cp "${EXTRACT_DIR}/gsc-tokens.json" "data/gsc-tokens.json"
    chmod 600 "data/gsc-tokens.json"
fi

# --- Step 8: Restore uploads ---
if [[ "$HAS_UPLOADS" = true && "$SKIP_UPLOADS" = false ]]; then
    echo "Restoring uploads/ ($UPLOADS_COUNT files) ..." >&2
    rsync -a --delete "${EXTRACT_DIR}/uploads/" "uploads/"
    chown -R www-data:www-data "uploads/"
fi

# --- Step 9: Post-restore verification ---
echo "Running post-restore smoke test ..." >&2
POST_SMOKE=$(php scripts/restore-smoke-test.php 2>&1) || true
POST_OK=$(echo "$POST_SMOKE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('ok', False))" 2>/dev/null || echo "false")

echo "HTTP health check ..." >&2
HTTP_STATUS=$(curl -sk -o /dev/null -w '%{http_code}' -H "Host: www.puertoricobeachfinder.com" https://127.0.0.1/ 2>/dev/null || echo "000")

echo "" >&2
echo "=== Restore Complete ===" >&2
echo "  DB smoke test: $POST_OK" >&2
echo "  HTTP status:   $HTTP_STATUS" >&2
echo "  Pre-restore snapshot: $SNAPSHOT_DIR" >&2
echo "" >&2

cat <<OUTEOF
{
  "ok": true,
  "dry_run": false,
  "restored_db": true,
  "restored_env": $HAS_ENV,
  "restored_gsc_tokens": $HAS_GSC,
  "restored_uploads": $([ "$HAS_UPLOADS" = true ] && [ "$SKIP_UPLOADS" = false ] && echo true || echo false),
  "uploads_count": $UPLOADS_COUNT,
  "pre_restore_snapshot": "$SNAPSHOT_DIR",
  "post_restore_smoke_test": "$POST_OK",
  "http_health_check": "$HTTP_STATUS"
}
OUTEOF
