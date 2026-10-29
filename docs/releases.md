# Routine releases

From the repository on your workstation, run `./deploy.sh` after merging. To
combine merging an approved PR and deployment, run `./deploy.sh --pr NUMBER`.
`./deploy.sh --check` checks the current release without changing production code.

The command uses existing GitHub CLI and `beach-prod` SSH authentication. It
adds no CI credentials, server agents, recurring jobs or permission changes.
The target is `/var/www/beach-finder` and the service is `php8.3-fpm`.

CI builds and validates the committed frontend assets, tests both PHP versions,
and scans for secrets. PR updates get one CI workflow instead of duplicate push
and PR workflows; a merged main commit gets its own verification. Deployment
requires that exact main revision's latest CI run and all three required jobs
to succeed. Missing, failed, skipped or cancelled checks block deployment.

Production fetches tracked files from Git. Local environment files, databases,
uploads and private reports are never packaged or uploaded. Concurrent releases
are blocked with a server lock. Dirty tracked files, a non-main server branch,
a non-fast-forward update or an advanced main branch stop the release.

Each actual update takes one verified database backup before changing code.
Pending migrations run once, PHP reloads, and four origin HTTP checks run.
An already deployed revision skips backup, update and reload. The reported
elapsed time includes waiting for CI; an already green release does no builds.

## Recovery

The command prints its recovery record at
`/var/www/beach-finder/backups/releases/<timestamp>-<commit>/release.txt`.
This records the previous commit, target, exact database backup and outcome.
The neighboring `backup.json` contains the backup verification details.

On a failure before any migration attempt, the command restores the previous
code with `git reset --keep`, reloads PHP and checks health. It leaves runtime
data and untracked files alone. The command still exits unsuccessfully so a
failed release cannot appear successful merely because recovery succeeded.

If a migration was attempted, inspect the record and migration state first.
Recovery is manual because restoring an older database can discard new customer
activity and old code may not support the new schema. Pause writes and preserve
the current database before restoring the recorded backup if needed. Restore
the matching code, reload PHP and verify migrations and live health. Never
automatically restore an old SQLite file over a running site's database.

## Authorization

A request to ship routine changes covers their ordinary release steps; the agent
should not ask again for CI, backup, reload or smoke checks. The existing GitHub
review requirement remains in force. Neither the script nor an agent should
silently switch to `--admin` or change branch protections. Private report imports
are separate operations governed by the user's authorization for that data and
destination, not an implicit part of every release.
