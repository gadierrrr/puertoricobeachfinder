# Codebase map

This doc is a quick navigation guide to the repo after the `public/` docroot migration.

## Directory map

- `public/` — Web docroot (serve ONLY this directory)
  - `public/*.php` — public pages (home, beach detail, collections, etc.)
  - `public/api/` — HTMX/JSON endpoints
  - `public/admin/` — admin UI
  - `public/auth/` — auth/OAuth handlers
  - `public/guides/` — guide pages
  - `public/errors/` — friendly error pages
  - `public/assets/` — static assets (disk path). URL path is `/assets/...`
  - `public/images/` — static images (disk). Includes thumbnails and beach images.
- `inc/` — core PHP includes (bootstrap, db, helpers, auth, constants)
- `components/` — reusable UI components (header/footer/page shell, cards, hero sections)
- `templates/` — shared templates
- `scripts/` — CLI utilities (DB init/import, migrations runner, audits)
- `migrations/` — migration files (run via `php scripts/migrate.php`)
- `data/` — SQLite DB + logs/cache (never web-served)
- `uploads/` — runtime uploads (kept outside `public/`, served via Nginx alias at `/uploads/`)
- `deploy/` — deployment config templates (Nginx)
- `docs/` — developer documentation

## Bootstrap conventions

- `bootstrap.php` (repo root) defines `APP_ROOT` and `PUBLIC_ROOT` and loads `inc/bootstrap.php`.
- Public entrypoints should begin with:
  - `require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';`
- Prefer filesystem paths via:
  - `APP_ROOT . '/inc/...'`
  - `APP_ROOT . '/components/...'`
  - `PUBLIC_ROOT . '/assets/...'`

## Web entrypoints (high level)

- Pages: `public/index.php`, `public/beach.php`, `public/municipality.php`, `public/compare.php`, `public/favorites.php`, `public/profile.php`, `public/quiz.php`
- Guides index: `public/guides/index.php`
- APIs: `public/api/*.php` (+ `public/api/admin/`, `public/api/quiz/`, `public/api/reviews/`)
- Admin: `public/admin/*.php`

## Common change playbooks

### Add a new public page

1. Create `public/<page>.php`
2. Start with the bootstrap require:
   - `require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';`
3. Use `components/page-shell.php` for consistent header/footer:
   - set `$pageTitle`, optional `$pageDescription`, then include the shell.
4. Use `APP_ROOT` for includes; never assume `__DIR__` traversal.

### Add a new API endpoint

1. Create `public/api/<endpoint>.php`
2. Require bootstrap at the top.
3. Return HTML for HTMX requests (`HX-Request`) and JSON otherwise (existing endpoints show the pattern).

### Add a migration

1. Add a new file in `migrations/`
2. Run `php scripts/migrate.php --dry-run` to verify ordering
3. Apply with `php scripts/migrate.php`

### Update CSS

- Edit partials in `public/assets/css/partials/`
- Rebuild bundles:
  - `npm run build:css`
  - `npm run build:tailwind`

## Deployment notes

- Nginx `root` should be `/var/www/beach-finder/public`.
- `uploads/` is served via `alias /var/www/beach-finder/uploads/;` at URL `/uploads/` with PHP disabled.
- DB lives under `data/` (path configured via `DB_PATH` in `.env`).
