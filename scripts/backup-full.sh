#!/bin/bash
# Full backup: DB snapshot + uploads + config files
# Creates a compressed tarball with SHA256 manifest
# Usage: bash scripts/backup-full.sh [--keep-days=N] [--skip-uploads] [--help]

set -euo pipefail
cd "$(dirname "$0")/.."

# --- Defaults ---
KEEP_DAYS=7
SKIP_UPLOADS=false
BACKUP_DIR="backups/full"
STAGING_DIR=""

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
        --keep-days=*)
            KEEP_DAYS="${arg#--keep-days=}"
            if ! [[ "$KEEP_DAYS" =~ ^[0-9]+$ ]]; then
                echo '{"ok":false,"error":"--keep-days must be a non-negative integer"}' >&2
                exit 1
            fi
            ;;
        --skip-uploads)
            SKIP_UPLOADS=true
            ;;
        --help)
            cat <<'EOF'
Usage: bash scripts/backup-full.sh [OPTIONS]

Options:
  --keep-days=N      Local retention days (default: 7)
  --skip-uploads     Exclude uploads/ directory
  --help             Show this help

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

TIMESTAMP=$(date -u +%Y%m%d_%H%M%S)
TARBALL_NAME="beach-finder-full-${TIMESTAMP}.tar.gz"
TARBALL_PATH="${BACKUP_DIR}/${TARBALL_NAME}"

# --- Step 1: Run backup-db.php for a verified SQLite snapshot ---
DB_OUTPUT=$(php scripts/backup-db.php --keep-days=0 2>&1) || {
    echo "{\"ok\":false,\"error\":\"backup-db.php failed\",\"detail\":$(echo "$DB_OUTPUT" | python3 -c 'import sys,json; print(json.dumps(sys.stdin.read()))')}" >&2
    exit 1
}

DB_BACKUP_PATH=$(echo "$DB_OUTPUT" | python3 -c "import sys,json; print(json.load(sys.stdin)['backup_path'])" 2>/dev/null) || {
    echo "{\"ok\":false,\"error\":\"Could not parse backup_path from backup-db.php output\"}" >&2
    exit 1
}

if [[ ! -f "$DB_BACKUP_PATH" ]]; then
    echo "{\"ok\":false,\"error\":\"DB backup file not found: $DB_BACKUP_PATH\"}" >&2
    exit 1
fi

# --- Step 2: Create staging directory ---
STAGING_DIR=$(mktemp -d "/tmp/beach-finder-backup-XXXXXX")

# --- Step 3: Stage components ---
# Database
cp "$DB_BACKUP_PATH" "${STAGING_DIR}/beach-finder.db"
DB_SIZE=$(stat -c%s "${STAGING_DIR}/beach-finder.db")

# Config files
CONFIG_SIZE=0
if [[ -f ".env" ]]; then
    cp ".env" "${STAGING_DIR}/env.bak"
    CONFIG_SIZE=$((CONFIG_SIZE + $(stat -c%s "${STAGING_DIR}/env.bak")))
fi

if [[ -f "data/gsc-tokens.json" ]]; then
    cp "data/gsc-tokens.json" "${STAGING_DIR}/gsc-tokens.json"
    CONFIG_SIZE=$((CONFIG_SIZE + $(stat -c%s "${STAGING_DIR}/gsc-tokens.json")))
fi

# Uploads
UPLOADS_SIZE=0
if [[ "$SKIP_UPLOADS" = false && -d "uploads" ]]; then
    cp -a "uploads" "${STAGING_DIR}/uploads"
    UPLOADS_SIZE=$(du -sb "${STAGING_DIR}/uploads" | cut -f1)
fi

# --- Step 4: Generate SHA256SUMS manifest ---
(cd "$STAGING_DIR" && find . -type f ! -name SHA256SUMS -print0 | sort -z | xargs -0 sha256sum > SHA256SUMS)

# --- Step 5: Create tarball ---
mkdir -p "$BACKUP_DIR"
tar -czf "$TARBALL_PATH" -C "$STAGING_DIR" .

TARBALL_SIZE=$(stat -c%s "$TARBALL_PATH")

# --- Step 6: Write sidecar .sha256 ---
TARBALL_SHA256=$(sha256sum "$TARBALL_PATH" | cut -d' ' -f1)
echo "${TARBALL_SHA256}  ${TARBALL_NAME}" > "${TARBALL_PATH}.sha256"

# --- Step 7: Write metadata JSON ---
cat > "${TARBALL_PATH}.json" <<METAEOF
{
  "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "tarball": "${TARBALL_NAME}",
  "sha256": "${TARBALL_SHA256}",
  "bytes": ${TARBALL_SIZE},
  "components": {
    "database_bytes": ${DB_SIZE},
    "config_bytes": ${CONFIG_SIZE},
    "uploads_bytes": ${UPLOADS_SIZE},
    "uploads_included": $([ "$SKIP_UPLOADS" = false ] && echo true || echo false)
  },
  "db_backup_source": "${DB_BACKUP_PATH}"
}
METAEOF

# --- Step 8: Clean up DB snapshot (we have it in the tarball now) ---
rm -f "$DB_BACKUP_PATH" "${DB_BACKUP_PATH}.json"

# --- Step 9: Prune old local backups ---
PRUNED=()
if [[ "$KEEP_DAYS" -gt 0 ]]; then
    CUTOFF=$(date -u -d "${KEEP_DAYS} days ago" +%s 2>/dev/null || date -u -v-${KEEP_DAYS}d +%s)
    for f in "${BACKUP_DIR}"/beach-finder-full-*.tar.gz; do
        [[ -f "$f" ]] || continue
        [[ "$f" = "$TARBALL_PATH" ]] && continue
        FILE_MTIME=$(stat -c%Y "$f" 2>/dev/null || stat -f%m "$f")
        if [[ "$FILE_MTIME" -lt "$CUTOFF" ]]; then
            rm -f "$f" "${f}.sha256" "${f}.json"
            PRUNED+=("$(basename "$f")")
        fi
    done
fi

# --- Step 10: Output JSON summary ---
PRUNED_JSON="[]"
if [[ ${#PRUNED[@]} -gt 0 ]]; then
    PRUNED_JSON=$(printf '%s\n' "${PRUNED[@]}" | python3 -c "import sys,json; print(json.dumps([l.strip() for l in sys.stdin]))")
fi

cat <<OUTEOF
{
  "ok": true,
  "tarball_path": "${TARBALL_PATH}",
  "tarball_name": "${TARBALL_NAME}",
  "sha256": "${TARBALL_SHA256}",
  "bytes": ${TARBALL_SIZE},
  "components": {
    "database_bytes": ${DB_SIZE},
    "config_bytes": ${CONFIG_SIZE},
    "uploads_bytes": ${UPLOADS_SIZE},
    "uploads_included": $([ "$SKIP_UPLOADS" = false ] && echo true || echo false)
  },
  "keep_days": ${KEEP_DAYS},
  "pruned": ${PRUNED_JSON}
}
OUTEOF
