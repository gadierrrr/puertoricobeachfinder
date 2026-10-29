#!/usr/bin/env bash
# Sent over SSH by release.py after verifying CI for the exact commit.
set -Eeuo pipefail
sha="${1:?Expected commit SHA}"
root="${2:?Expected application directory}"
mode="${3:?Expected check or deploy}"
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid commit SHA' >&2; exit 1; }
[[ "$mode" == check || "$mode" == deploy ]] || { echo 'Invalid mode' >&2; exit 1; }
cd "$root"

clean_tree() {
  git diff --quiet && git diff --cached --quiet || { echo 'Tracked server files have local changes; stopping.' >&2; return 1; }
  [[ "$(git symbolic-ref --short HEAD)" == main ]] || { echo 'Server must be on main.' >&2; return 1; }
}

reload_php() {
  if [[ "$(id -u)" == 0 ]]; then
    systemctl reload php8.3-fpm || return
  else
    sudo -n systemctl reload php8.3-fpm || return
  fi
  systemctl is-active --quiet php8.3-fpm
}

smoke() {
  local page
  # Hit the local origin over TLS, avoiding a cached response from the CDN.
  for page in / /es /best-beaches; do
    curl --fail --silent --show-error --connect-timeout 5 --max-time 20 --retry 2 \
      --resolve www.puertoricobeachfinder.com:443:127.0.0.1 \
      "https://www.puertoricobeachfinder.com${page}" | \
      php -r '$h=stream_get_contents(STDIN); if (stripos($h,"<html")===false) {fwrite(STDERR,"Expected an HTML page\n"); exit(1);}' || return
  done
  curl --fail --silent --show-error --connect-timeout 5 --max-time 20 --retry 2 \
    --resolve www.puertoricobeachfinder.com:443:127.0.0.1 \
    'https://www.puertoricobeachfinder.com/api/health/analytics.php' | \
    php -r '$d=json_decode(stream_get_contents(STDIN),true); if (($d["ok"]??false)!==true) {fwrite(STDERR,"Analytics health failed\n"); exit(1);}' || return
  echo 'Live English, Spanish, collection and analytics checks passed.'
}

clean_tree
if [[ "$mode" == check ]]; then
  printf 'Server: %s\nTarget: %s\n' "$(git rev-parse HEAD)" "$sha"
  php scripts/migrate.php --check
  smoke
  exit 0
fi

# Serialize releases, including backup, update, reload and recovery.
exec 9>.git/beach-release.lock
flock -n 9 || { echo 'Another release is running.' >&2; exit 1; }
clean_tree
git fetch origin main
[[ "$(git rev-parse origin/main)" == "$sha" ]] || { echo 'Main changed since CI was checked; rerun the release command.' >&2; exit 1; }
previous="$(git rev-parse HEAD)"
git merge-base --is-ancestor "$previous" "$sha" || { echo 'Release must fast-forward the server.' >&2; exit 1; }
if [[ "$previous" == "$sha" ]]; then
  php scripts/migrate.php --check
  smoke
  echo 'Already deployed; no update, backup or reload needed.'
  exit 0
fi

mkdir -p backups/releases
journal="backups/releases/$(date -u +%Y%m%dT%H%M%SZ)-${sha:0:12}"
mkdir "$journal"
printf 'previous=%s\ntarget=%s\n' "$previous" "$sha" > "$journal/release.txt"
echo "Recovery record: $root/$journal/release.txt"
echo 'Creating and restore-testing one database backup…'
php scripts/backup-db.php > "$journal/backup.json"
backup="$(php -r '$d=json_decode(file_get_contents($argv[1]),true); if (($d["ok"]??false)!==true || empty($d["backup_path"])) exit(1); echo $d["backup_path"];' "$journal/backup.json")"
printf 'database_backup=%s\n' "$backup" >> "$journal/release.txt"
php scripts/restore-smoke-test.php "--backup=$backup"

updated=0
migration_started=0
recover() {
  local status="${1:-$?}"
  trap - ERR INT TERM
  # A terminated release must not be reported as successful.
  [[ "$status" != 0 ]] || status=1
  printf 'status=failed\n' >> "$journal/release.txt"
  if [[ "$updated" == 1 && "$migration_started" == 0 ]]; then
    echo "Release failed; restoring previous code $previous." >&2
    if git reset --keep "$previous" && reload_php && smoke; then
      echo 'Previous code restored; database untouched.' >&2
      printf 'recovery=previous_code_restored\n' >> "$journal/release.txt"
    else
      echo 'Automatic code recovery failed; inspect the recovery record.' >&2
    fi
  elif [[ "$migration_started" == 1 ]]; then
    echo 'Migration was attempted. Manual recovery is required to avoid overwriting newer database writes.' >&2
  fi
  echo "Recovery record: $root/$journal/release.txt" >&2
  exit "$status"
}
trap recover ERR INT TERM
# Mark before updating so an interrupted update also enters recovery.
updated=1
git merge --ff-only "$sha"
if php scripts/migrate.php --check; then
  echo 'No migrations needed.'
else
  migration_status=$?
  if [[ "$migration_status" != 1 ]]; then recover "$migration_status"; fi
  migration_started=1
  printf 'migration_started=yes\n' >> "$journal/release.txt"
  php scripts/migrate.php
  php scripts/migrate.php --check
fi
reload_php
smoke
printf 'status=deployed\n' >> "$journal/release.txt"
trap - ERR INT TERM
echo "Deployed $sha"
