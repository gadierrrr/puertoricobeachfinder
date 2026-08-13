# Codebase map

This is the maintained navigation guide for the framework-free PHP application. Production must expose only `public/`; everything else is server code, source material, runtime data, or CLI tooling.

## Directory ownership

- `public/` — web document root
  - `public/*.php` — page entrypoints and route targets
  - `public/api/` — JSON and HTMX-style endpoints
  - `public/admin/` — authenticated admin UI
  - `public/auth/` — OAuth handlers
  - `public/errors/` — standalone error pages
  - `public/guides/` — guide index, legacy guide files, and CMS router
  - `public/assets/` — served CSS, JavaScript, fonts, icons, and design tokens
  - `public/images/` — published beach and thumbnail images
- `inc/` — bootstrap, database, auth, i18n, referrals, email, guide CMS, security, and domain helpers
- `components/` — reusable public UI, including collection, beach, chat, legal, and redesign partials
- `templates/` — shared page templates and redesign page variants
- `migrations/` — ordered schema/data migrations run through `scripts/migrate.php`
- `scripts/` — CLI-only builds, migrations, backups, checks, imports, synchronization, and content tooling
- `config/` — lint/configuration data used by tooling
- `assets/` — source and reference assets; this is not the served asset directory
- `deploy/` — Nginx and deployment configuration
- `docs/` — maintained runbooks plus historical reports
- `data/` — SQLite and runtime/cache data; never web-served
- `uploads/` — user uploads outside the document root, normally exposed through an Nginx alias
- `logs/`, `backups/`, `audit-results/`, `test-results/` — local or operational artifacts, not source
- root `guides/` — legacy/reference backups; live guide files are under `public/guides/`

## Bootstrap and request flow

The root `bootstrap.php` defines `APP_ROOT` and `PUBLIC_ROOT`, then loads `inc/bootstrap.php`. Public PHP entrypoints should begin with:

```php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
```

Use `APP_ROOT` and `PUBLIC_ROOT` for cross-directory filesystem includes. `__DIR__` is appropriate within one subsystem. Avoid fragile paths such as `../../inc/...`.

Public links, canonicals, navigation, templates, and sitemaps use clean URLs rather than `.php` filenames. `scripts/dev-router.php` mirrors production routing for the PHP built-in server. Locale behavior is centralized in `inc/i18n.php`, `inc/locale_routes.php`, and `inc/lang/`.

`public/guides/cms-router.php` resolves CMS-backed guide slugs and falls back to the legacy files in `public/guides/`. Changes to guide routing must preserve both paths.

## Main application surfaces

- Discovery: homepage, beach detail, municipality, proximity, tag, collection, comparison, quiz, and map/list pages under `public/`
- Accounts: login/OAuth, profile, favorites, custom lists, preferences, unsubscribe, and account deletion
- Community: reviews, helpful votes, check-ins, photos, chat, moderation, and push subscriptions
- Growth: send-list and send-quiz-result capture, referrals, retention, badges, and personalization
- Monetization: local listings, referral redirects/impressions, Viator hydration/reporting, and advertising
- Content: guide CMS, legacy guides, locale content, SEO overrides, structured data, feeds, robots, and sitemap
- Operations: admin pages for beaches, reviews, users, email, referrals, advertising, listings, design settings, chat moderation, and place-ID audits

## API groupings

- Account and favorites: `public/api/account/`, `public/api/favorites/`, `public/api/toggle-favorite.php`
- Admin: `public/api/admin/`
- Chat: `public/api/chat/`
- Health: `public/api/health/` for analytics, email, and Viator
- Quiz and lead capture: `public/api/quiz/`, `public/api/send-list.php`, `public/api/send-quiz-results.php`
- Reviews and contributions: `public/api/reviews/`, `public/api/checkin.php`, `public/api/photos.php`
- Referrals and ads: `public/api/referrals/`, `public/api/ads/`, `public/api/advertise-lead.php`
- Webhooks: `public/api/webhooks/`

Endpoints often return an HTML fragment when `HX-Request` is present and JSON otherwise. Follow the neighboring endpoint's authentication, CSRF, rate-limit, and response conventions.

## Frontend assets

Edit custom CSS in `public/assets/css/partials/`; `scripts/build-css.sh` creates `public/assets/css/styles.css`. Tailwind source is `public/assets/css/tailwind-input.css`, and the generated bundle is `public/assets/css/tailwind.min.css`.

Vanilla JavaScript source and committed minified bundles live in `public/assets/js/`. Run:

```bash
npm run build:css
npm run build:tailwind
npm run build:js
npm run check:design
```

Do not edit generated bundles without also updating their source. Shared head, CSS, analytics, CSP nonce, nav, and script loading belongs in existing components, primarily `components/header.php`, `components/footer.php`, and `components/page-shell.php`.

## Core service locations

- Environment and feature flags: `inc/env.php`, `.env.example`
- Database access: `inc/db.php`
- Authentication and sessions: `inc/auth.php`, `inc/session.php`
- Locale routing and strings: `inc/i18n.php`, `inc/locale_routes.php`, `inc/lang/`
- Email: `inc/email.php`, `inc/email_provider_resend.php`
- Analytics client: `public/assets/js/analytics.js`
- Referrals and Viator: `inc/referrals.php`, `inc/referral_reporting.php`, `inc/viator.php`, `inc/tours.php` (beach + guide tour sections; catalog sync via `scripts/sync-viator-catalog.php`)
- Advertising: `inc/advertising.php`, `public/admin/advertising.php`, `public/api/ads/`
- Guide CMS: `inc/guide_cms.php`, `public/guides/cms-router.php`
- Security headers: `inc/security_headers.php`

## Common change playbooks

### Add a public page

1. Add the entrypoint under `public/` and require the root bootstrap.
2. Reuse `components/page-shell.php` or existing header/footer components.
3. Add clean-route and localized-route mappings where needed.
4. Update sitemap, canonical/hreflang behavior, and HTTP smoke coverage when the page is indexable.

### Add an API endpoint

1. Add the endpoint to the closest `public/api/` subtree.
2. Require bootstrap and reuse existing auth, CSRF, validation, and rate-limit helpers.
3. Match the neighboring HTMX/JSON response contract.
4. Add a health or webhook runbook update when the endpoint changes operations.

### Add a migration

1. Choose the next unused numeric prefix; do not reuse a prefix that exists in another branch or local worktree.
2. Make schema changes idempotent when practical.
3. Run `php scripts/migrate.php --dry-run`, apply to a disposable database, then run `--check`.
4. Document any required production ordering, backfill, or rollback behavior.

### Change email, analytics, referrals, or Viator

Update the code, `.env.example`, the relevant health endpoint, and its runbook together:

- `docs/email-resend.md`
- `docs/analytics-umami.md`
- `docs/viator-api.md`

## Deployment boundaries

- Nginx `root` points to `/var/www/beach-finder/public`.
- `/uploads/` is an alias to the top-level uploads directory with PHP execution disabled.
- SQLite stays under `data/` and is configured through `DB_PATH`.
- `./deploy.sh` lints PHP, installs/builds frontend dependencies, verifies committed generated assets, applies migrations, and runs smoke checks.
- Back up and verify restore before risky migrations. Use `scripts/backup-db.php` and `scripts/restore-smoke-test.php`.

See `docs/README.md` for the documentation inventory and maintenance rules.
