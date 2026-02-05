# AGENTS.md

## Architecture (1 minute)

This repo is a PHP + SQLite site with **no framework**. The production web server should serve **only** the `public/` directory as the document root. Everything outside `public/` is server-side code or CLI-only utilities.

## Where to find things

- **Web entrypoints (URLs):** `public/`
  - Pages: `public/*.php`
  - Guides: `public/guides/`
  - API endpoints: `public/api/`
  - Auth handlers: `public/auth/`
  - Admin: `public/admin/`
  - Static assets (disk): `public/assets/` (served at `/assets/...`)
  - Images: `public/images/`
- **Core app code:** `inc/` (DB, helpers, auth, validation, bootstrapping)
- **Reusable UI:** `components/`
- **Templates:** `templates/`
- **Database + runtime files:** `data/` (never web-served)
- **User uploads:** `uploads/` (kept **outside** `public/`, served by Nginx alias at `/uploads/`)
- **CLI scripts:** `scripts/` (never web-served)
- **Migrations:** `migrations/` (run via `scripts/migrate.php`)
- **Deploy configs:** `deploy/`
- **Docs:** `docs/`

## Bootstrap / include conventions (important)

- Repo root has `bootstrap.php` which defines:
  - `APP_ROOT` (repo root absolute path)
  - `PUBLIC_ROOT` (`APP_ROOT . '/public'`)
  - then loads `inc/bootstrap.php`
- **All public PHP entrypoints** should start with:
  - `require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';`
- Use filesystem paths via `APP_ROOT` / `PUBLIC_ROOT` (avoid fragile `__DIR__` relative traversal).

## Common commands

```bash
# Install deps
npm ci

# Build assets into public/assets
npm run build

# Design-system lint (HTML/CSS conventions + CSS partial checks)
node scripts/lint-design-system.js

# Initialize/reset DB (reads .env DB_PATH)
php scripts/init-db.php

# Migrations
php scripts/migrate.php --dry-run
php scripts/migrate.php
php scripts/migrate.php --check
```

## Local dev (PHP built-in server)

Use the public docroot:

```bash
php -S localhost:8082 -t public scripts/dev-router.php
```

## Notes for LLMs/agents

- `public/llms-full.txt` can be large; only open it if you specifically need the full content.
- Never put secrets into `public/` (or any committed file). CI runs secret scanning.
