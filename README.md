# Puerto Rico Beach Finder

A framework-free PHP + SQLite application for exploring Puerto Rico's beaches. It includes localized public pages, search and map discovery, collections, quizzes, guides, accounts, reviews and check-ins, custom lists, referrals, advertising, community chat, and an admin area.

Production must serve only `public/` as the document root. The database, configuration, uploads, logs, backups, source assets, and CLI tools intentionally live outside the public web root.

## Requirements

- PHP 8.3 or 8.4 with SQLite support
- Node.js 22 and npm (`.nvmrc` pins the expected major version)
- A valid Google Maps API key for application bootstrap
- Chrome or Chromium only for the optional synthetic browser probes

## Local setup

1. Copy environment template and configure values:

```bash
cp .env.example .env
```

2. Install frontend dependencies:

```bash
npm ci
```

3. Build frontend assets:

```bash
npm run build
```

4. Initialize database (first run only):

```bash
php scripts/init-db.php
```

5. Run migrations:

```bash
php scripts/migrate.php
```

## Local development

Run with the built-in PHP server using the `public/` docroot:

```bash
php -S localhost:8082 -t public scripts/dev-router.php
```

The current public rendering mode comes from `HOMEPAGE_DESIGN`. For a one-request local preview, append `?design=classic` or `?design=redesign`.

## Validation

Run the checks that match your change before opening a pull request:

```bash
# PHP syntax
find . -type f -name '*.php' \
  -not -path './.git/*' \
  -not -path './node_modules/*' \
  -print0 | xargs -0 -n1 php -l

# Frontend build and design-system rules
npm run build
npm run check:design

# Routing and shared page configuration
php scripts/test-locale-routing.php
php scripts/test-page-heroes.php

# Database state
php scripts/migrate.php --dry-run
php scripts/migrate.php --check
```

CI runs PHP 8.3 and 8.4, rebuilds committed assets, validates routing and design rules, initializes and migrates a temporary database, checks published images, performs backup/restore and HTTP smoke tests, and runs secret scanning.

## Environment configuration

Copy `.env.example`; it is the canonical inventory of supported local settings. Required bootstrap values are:

- `DB_PATH`
- `APP_URL`
- `APP_NAME`
- `GOOGLE_MAPS_API_KEY` — a valid `AIza...` key with Places API (New) enabled
- `APP_ENV` — `dev`, `staging`, or `prod`
- `APP_DEBUG` — `0` or `1`

Feature-specific groups are optional until that feature is enabled:

- Rendering: `HOMEPAGE_DESIGN` (`classic` or `redesign`)
- Google OAuth: `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` must be configured together
- Email: `EMAIL_PROVIDER`, `RESEND_API_KEY`, `RESEND_WEBHOOK_SECRET`
- Analytics: `GA_MEASUREMENT_ID`; legacy Umami settings use `UMAMI_ENABLED`, `UMAMI_SCRIPT_URL`, `UMAMI_WEBSITE_ID`, and `UMAMI_DOMAINS`
- AI moderation/content helpers: `ANTHROPIC_API_KEY`
- Retention and push: `APP_SECRET`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`
- Referrals: `REFERRAL_ALLOWED_HOSTS`
- Viator product hydration: `VIATOR_PID`, `VIATOR_API_KEY`, `VIATOR_API_BASE_URL`, `VIATOR_API_ENABLED`, `VIATOR_SYNC_TTL_HOURS`
- Backups: `BACKUP_DIR`, `BACKUP_KEEP_DAYS`

Never commit `.env`, API keys, OAuth secrets, webhook secrets, or production database files.

## Funnel and analytics

The client tracking wrapper routes events to Google Analytics 4 (primary), plus legacy Umami and PostHog when loaded:

- `/quiz` returns a `results_token` from `public/api/quiz/match.php` and can generate a shareable URL.
- `/quiz-results?token=...` renders stored quiz matches; tokenized pages are `noindex`.
- List pages post to `public/api/send-list.php`; quiz results post to `public/api/send-quiz-results.php`.
- Resend webhooks arrive at `public/api/webhooks/resend.php`.
- `public/assets/js/analytics.js` defines `window.bfTrack()`.
- Event naming and provider behavior are documented in `docs/analytics-umami.md`.

## Health checks

Email:

```bash
curl -sS -i https://www.puertoricobeachfinder.com/api/health/email
```

The endpoint returns `200` when Resend is configured and reachable, and `503` when configuration, authentication, or connectivity is unhealthy. See `docs/email-resend.md`.

Analytics:

```bash
curl -sS "https://www.puertoricobeachfinder.com/api/health/analytics.php?page_probe=1&network_probe=1"
php scripts/check-analytics-ga.php --url=https://www.puertoricobeachfinder.com
scripts/synthetic-analytics-probe.sh https://www.puertoricobeachfinder.com
```

See `docs/analytics-umami.md`.

Viator, when API hydration is enabled:

```bash
curl -sS https://www.puertoricobeachfinder.com/api/health/viator.php
```

See `docs/viator-api.md`.

## Documentation

- `docs/README.md` — documentation index and source-of-truth rules
- `AGENTS.md` — repository conventions and change guardrails
- `docs/codebase-map.md` — request paths, subsystems, and change playbooks
- `docs/design-system.md` — public UI contract and design lint rules
- `docs/analytics-umami.md` — GA4/Umami event instrumentation
- `docs/email-resend.md` — email operations
- `docs/viator-api.md` — Viator hydration, attribution, and reporting
- `scripts/SYSTEM-ARCHITECTURE.md` — content-generation subsystem

## Migration commands

```bash
# List pending automatic migrations
php scripts/migrate.php --dry-run

# Apply pending automatic migrations
php scripts/migrate.php

# Fail if pending migrations exist
php scripts/migrate.php --check

# One-time baseline for existing DBs
php scripts/migrate.php --baseline

# Include manual/data migrations (default excludes the manual set)
php scripts/migrate.php --include-manual
```

## Deploy command

Use the unified deploy script:

```bash
./deploy.sh
```

It runs PHP syntax lint, installs Node dependencies, builds frontend assets, verifies committed generated assets, applies migrations, and performs migration and secret-scan smoke checks.

## Rollback and backups

Create and verify a backup before deploying risky application or schema changes:

```bash
php scripts/backup-db.php
php scripts/restore-smoke-test.php
```

If a deploy fails after a migration, roll application code back to the previous commit, restore the matching database backup, and run `php scripts/migrate.php --check`.

Suggested daily automation:

```cron
0 3 * * * cd /var/www/beach-finder && php scripts/backup-db.php >> logs/backup-db.log 2>&1
20 3 * * * cd /var/www/beach-finder && php scripts/restore-smoke-test.php >> logs/restore-smoke.log 2>&1
```

## Security operations

- Secret scanning is enforced in CI and pre-commit with gitleaks.
- `docs/google-key-rotation.md` is the Google key rotation checklist.
- `docs/secret-history-cleanup.md` covers history rewriting after secret rotation.
- `deploy/nginx/beach-finder.conf` is the Nginx hardening template.
