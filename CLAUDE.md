# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Puerto Rico Beach Finder is a PHP-based web application that provides a searchable database of 468 beaches across Puerto Rico. It uses SQLite for data storage, HTMX for dynamic interactions, and Tailwind CSS for styling.

## Documentation Index

**CLAUDE.md (this file) is the canonical guide.** Other docs are either generated
references or topic deep-dives — read the relevant one before diving into code.

| Doc | What it covers |
|-----|----------------|
| `CLAUDE.md` (this file) | Canonical architecture, conventions, commands, gotchas |
| `README.md` | Setup, env vars, deploy flow, health checks, backups |
| `AGENTS.md` | Short agent orientation + guardrails (points back here) |
| `DESIGN-SYSTEM.md` | Design tokens, color system, dark mode, breakpoints |
| `docs/database-schema.md` | **Generated** — every table, column, FK, index |
| `docs/api-manifest.md` | **Generated** — index of all `public/api/` endpoints |
| `components/CATALOG.md` | **Generated** — every component + the variables it expects |
| `docs/helpers-index.md` | **Generated** — every function in `inc/`, with summaries |
| `docs/schema-quick-reference.md` | SEO **JSON-LD** schema markup (NOT the DB schema) |
| `docs/codebase-map.md` | Legacy navigation notes (superseded by this file) |
| `docs/analytics-umami.md` | Analytics + event instrumentation |
| `docs/email-resend.md` | Email delivery + webhook operations |
| `docs/mobile-homepage-bug-report.md` | Known bugs in the inactive Discovery redesign |
| `scripts/SYSTEM-ARCHITECTURE.md` | Content/generation system deep-dive |

> The four **Generated** docs are produced by `npm run docs:gen` — never hand-edit
> them. Regenerate after schema/endpoint/component/helper changes (the DB schema
> dump needs a local database).

## Common Commands

**Build all assets (CSS + Tailwind):**
```bash
npm run build
```

**Build custom CSS from partials:**
```bash
npm run build:css
```

**Build Tailwind CSS:**
```bash
npm run build:tailwind
```

**Watch Tailwind CSS during development:**
```bash
npm run dev
```

**Minify JavaScript:**
```bash
npm run build:js
```

**Initialize/reset database:**
```bash
php scripts/init-db.php
```

**Run database migrations:**
```bash
php scripts/migrate.php
```

**Verify a change before reporting it done:**
```bash
npm run check          # PHP lint sweep + duplicate-function guard + route smoke test
                       # (design-system lint + migration status run as warnings)
```

**Regenerate the auto-generated reference docs:**
```bash
npm run docs:gen       # docs/database-schema.md, docs/api-manifest.md,
                       # components/CATALOG.md, docs/helpers-index.md
```

**Run the app locally (PHP built-in server):**
```bash
php -S localhost:8082 -t public scripts/dev-router.php
```

## Architecture

### Technology Stack
- **Backend:** PHP 8.x (no framework, procedural)
- **Database:** SQLite3 with WAL mode (`data/beach-finder.db`)
- **Frontend:** HTMX for dynamic updates, vanilla JavaScript
- **CSS:** Tailwind CSS 3.x with custom "beach" color palette
- **Icons:** Lucide icons

### Directory Structure
- `inc/` - Core PHP includes (db.php, helpers.php, constants.php, auth.php)
- `components/` - Reusable PHP UI components (header, footer, beach-card, filters)
  - `components/beach/` - Beach detail page components (see Beach Detail Architecture below + `components/CATALOG.md`)
  - `components/chat/` - Chat system UI components (panel, inbox-item, message)
  - `components/collection/` - Collection/list page components
- `public/` - Web document root (ONLY this should be web-served)
  - `public/api/` - JSON/HTML API endpoints for HTMX requests
  - `public/admin/` - Admin panel for content management
  - `public/auth/` - Authentication handlers (Google OAuth)
  - `public/guides/` - Editorial/guide pages
  - `public/beaches-by-tag.php` - Dynamic tag/amenity landing pages (/beaches/swimming, etc.)
  - `public/beaches-near.php` - Dynamic proximity pages (/beaches-near-ponce, etc.)
  - `public/feed.xml.php` - RSS feed
  - `public/errors/` - Friendly error pages
- `data/` - SQLite database files
- `migrations/` - Database migration scripts
- `scripts/` - CLI tools + build scripts (never web-served)
- `public/assets/js/` - Frontend JavaScript (app.js, map.js, filters.js, geolocation.js, chat.js)
- `public/assets/css/` - Stylesheets
  - `styles.css` - Bundled custom CSS (generated from partials)
  - `tailwind-input.css` - Tailwind entry point
  - `tailwind.min.css` - Compiled Tailwind output
  - `partials/` - CSS source files (see CSS Architecture below)

### Key Patterns

**Database Access:** Use the helper functions in `inc/db.php`:
- `query($sql, $params)` - Returns array of rows
- `queryOne($sql, $params)` - Returns single row or null
- `execute($sql, $params)` - For INSERT/UPDATE/DELETE

**Batch Data Loading:** Use `attachBeachMetadata($beaches)` to efficiently load tags and amenities for multiple beaches (avoids N+1 queries).

**HTML Escaping:** Always use `h($string)` from `inc/helpers.php` for output escaping.

**HTMX Integration:** API endpoints return HTML when `HX-Request` header is present, JSON otherwise. Check with `isHtmx()` helper.

**Authentication:** Session-based with magic links or Google OAuth. Use `isAuthenticated()`, `currentUser()`, and `requireAuth()` helpers.

**Controlled Vocabularies:** Tags, amenities, municipalities, and condition scales are defined in `inc/constants.php`. Use validation helpers like `isValidTag()`, `isValidMunicipality()`.

**Shared Components:** Reusable UI components in `components/` directory:
- `hero-guide.php` - Used by all guide pages, requires `$pageTitle`, `$pageDescription`, optional `$breadcrumbs`
- `components/collection/explorer.php` - Shared collection explorer (hero + toolbar + results)
- `components/beach/*.php` - Beach detail page components (see Beach Detail Architecture below)
- Always use shared components for consistency; avoid creating inline variants of existing components

### Bootstrapping & Entrypoint Conventions

- Every public entrypoint starts with:
  `require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';`
  This defines `APP_ROOT` / `PUBLIC_ROOT` and loads env + error handling.
- For cross-directory includes prefer stable paths — `APP_ROOT . '/inc/...'`,
  `APP_ROOT . '/components/...'`, `PUBLIC_ROOT . '/assets/...'`. Avoid `../../` traversal.
- CLI scripts bootstrap by requiring `inc/db.php` (it pulls in the full bootstrap + env).
- Reuse shared components (`components/header.php`, `components/page-shell.php`) for the
  page head / CSS loading — never hand-roll head setup.

### Common Change Playbooks

- **Add a public page:** create `public/<page>.php`, start with the bootstrap require,
  use `components/page-shell.php` (set `$pageTitle`, optional `$pageDescription`).
- **Add an API endpoint:** create `public/api/<endpoint>.php`, require bootstrap, return
  HTML for `HX-Request` and JSON otherwise (see `docs/api-manifest.md` for examples).
- **Add a migration:** add `migrations/<n>-name.php`, run `php scripts/migrate.php --dry-run`,
  then `php scripts/migrate.php`; regenerate the schema doc with `npm run docs:gen`.
- **Update CSS:** edit `public/assets/css/partials/`, then `npm run build:css`.
- **After any change:** run `npm run check` before reporting it done.

### URL Conventions & Routing

**Always use clean URLs (no `.php` extensions) in links, nav, footer, sitemap, and templates.** Nginx handles extensionless routing (e.g., `/quiz` serves `quiz.php`, `/compare` serves `compare.php`). Using `.php` URLs causes an unnecessary 301 redirect hop.

```php
<!-- WRONG -->
<a href="/quiz.php">Quiz</a>
<a href="/compare.php?beaches=1,2">Compare</a>

<!-- CORRECT -->
<a href="/quiz">Quiz</a>
<a href="/compare?beaches=1,2">Compare</a>
```

**Soft 404 handling:** `public/index.php` catches unknown routes that fall through Nginx's catch-all. Any request reaching `index.php` where the path is not `/` returns HTTP 404 (with `public/errors/404.php`). Trailing-slash variants (e.g., `/best-beaches/`) get 301-redirected to the non-slash version. Real directories like `/guides/` are served by Nginx's directory index and never reach this logic.

### Beach Detail Page Architecture

The beach detail page (`public/beach.php`) handles data fetching, SEO, and component includes only. It composes **14 focused components** (shown below in include order). For the always-current list with the variables each component expects, see the generated `components/CATALOG.md`.

```
public/beach.php           — data fetching, SEO, component includes
components/beach/
  ├── hero.php             — Cover photo, gradient, breadcrumbs, title
  ├── info-bar.php         — Rating badge, tags, directions, share
  ├── section-nav.php      — Sticky horizontal tab navigation
  ├── at-a-glance.php      — AI-optimized summary block (key facts)
  ├── about.php            — Description + feature badges + visitor tips
  ├── extended-sections.php— Grouped sections: Plan Your Visit / About This Beach
  ├── photos.php           — Gallery + user photo uploads (conditional)
  ├── reviews.php          — User reviews (conditional, hidden when empty)
  ├── faq.php              — Beach FAQ (also emits FAQPage JSON-LD)
  ├── sidebar.php          — Weather, conditions, crowd, map, amenities, safety
  ├── related.php          — Planning guides + similar beaches
  ├── sticky-bar.php       — Mobile bottom action bar (directions/save/share)
  ├── modals.php           — All modal dialogs (share, lightbox, report, checkin, review, upload); largest component (~1,200 lines)
  └── scripts.php          — Weather widget loader + section nav observer
```

> Five untracked `components/beach/*` files NOT in this list (`facts-row`,
> `plan-row`, `local-tips`, `nearby-pills`, `conditions-getting-there`) belong to an
> **inactive redesign** — see "Inactive / Work-in-Progress Areas" below.

**To edit a section:** Open the specific component file. Each has a docblock listing its expected variables.

**Data flow:** `beach.php` fetches all data (beach record, tags, amenities, sections, reviews) at the top, then passes variables to components via PHP's shared scope (includes inherit parent scope).

**Key variables available to all components:**
- `$beach` — Full beach record with tags, amenities, gallery, features, tips
- `$lang` — Current language ('en' or 'es')
- `$reviews` — Array of published reviews
- `$extendedSections` — Content sections from beach_content_sections table
- `$crowdLevel`, `$sunTimes` — Pre-computed sidebar data

**Section ordering:** Extended sections are reordered in `extended-sections.php`:
1. "Plan Your Visit": best_time, what_to_bring
2. "About This Beach": history, nearby, local_tips
3. `getting_there` is skipped (users use GPS)

**Empty state handling:** Photos and reviews sections are only rendered when content exists. No empty placeholders.

### Inactive / Work-in-Progress Areas

A **map-first "Discovery" redesign** (homepage + beach detail) was started but never
shipped. The files are untracked, **not referenced by any live page**, and have 12+
known bugs (`docs/mobile-homepage-bug-report.md`). Each carries a `⚠️ INACTIVE / WIP`
header banner. **Do not build on these without reviving the effort.**

- `components/discovery/` — `shell.php`, `filter-bar.php`, `compact-card.php`
- `components/mobile-tabbar.php`
- `components/beach/` redesign wireframes — `facts-row.php`, `plan-row.php`, `local-tips.php`, `nearby-pills.php`, `conditions-getting-there.php`
- `public/assets/js/discovery.js` (action handlers unwired), `public/assets/js/review-filter.js`
- `public/assets/css/partials/_discovery.css`, `_beach-detail.css` (NOT in `scripts/build-css.sh`)

Local-only dev scripts (untracked, not part of the app): `scripts/seed-local-demo.php`
(seeds demo tags/amenities), `scripts/sync-from-prod.php` (pulls prod beaches into the local DB).

### Dynamic Pages (Programmatic SEO)

In addition to the 466 individual beach pages, the site has several types of programmatic landing pages:

**Tag/Amenity Pages** (`public/beaches-by-tag.php`):
- URLs: `/beaches/swimming`, `/beaches/with-parking`, etc.
- 15 tag pages + Spanish equivalents
- Each has unique title, description, intro, and FAQ schema

**Proximity Pages** (`public/beaches-near.php`):
- URLs: `/beaches-near-ponce`, `/beaches-near-rincon`, etc.
- 10 location pages using Haversine distance formula
- Shows beaches sorted by distance with km/mi badges

**Municipality Pages** (`public/municipality.php`):
- URLs: `/beaches-in-cabo-rojo`, `/beaches-in-isabela`, etc.
- 42 municipality pages (auto-generated from data)

**Collection Pages** (individual PHP files):
- `/best-beaches`, `/best-snorkeling-beaches`, etc.
- Use `inc/collection_query.php` + `inc/collection_contexts.php`

### Non-Beach Location Filtering

The `beaches` table has a `location_type` column (default: "beach"). Non-beach locations (boardwalks, parks, natural pools, reserves) are marked with their actual type and filtered out of all listings via:
```sql
WHERE (location_type = "beach" OR location_type IS NULL)
```
This filter is applied in: homepage, beaches API, collection queries, tag pages, proximity pages.

### Chat System

A real-time community chat built into the site as a floating panel (bottom-right FAB). Supports general discussion, per-beach threads, and DMs.

**Backend:**
- `inc/chat.php` — Channel management, message CRUD, inbox queries, access control
- `inc/chat_moderation.php` — Keyword blocklist, AI moderation, reports, mute/ban
- `public/api/chat/` — REST endpoints: `send.php`, `messages.php`, `inbox.php`, `poll.php`, `mark-read.php`, `unread.php`, `report.php`

**Frontend:**
- `components/chat/panel.php` — Floating panel with inbox + thread views (included via footer)
- `components/chat/inbox-item.php` — Conversation row in inbox
- `components/chat/message.php` — Individual message bubble
- `public/assets/js/chat.js` — CSP-compliant JS: inbox/thread switching, real-time polling, compose
- `public/assets/css/partials/_chat.css` — Dark ocean-themed panel styles

**Key details:**
- Panel uses dark ocean background (`--color-ocean-800/900`) matching the nav theme
- All text uses `text-white` / `text-white/40` classes — never use light backgrounds for chat elements
- Guest users see a "Sign in to join" CTA instead of the compose bar
- Real-time updates via polling (`/api/chat/poll.php`), not WebSockets
- Moderation: keyword blocklist + optional AI check before messages are published

**Database tables** (migration 031):
- `chat_channels` — Channel definitions (general, beach-specific, DM)
- `chat_messages` — Messages with channel_id, user_id, body
- `chat_participants` — Channel membership + last_read tracking
- `chat_reports` — User-reported messages
- `chat_blocklist` — Banned keywords/patterns

### Database Schema (Key Tables)
- `beaches` - Main beach records with coordinates, ratings, conditions
- `beach_tags` - Many-to-many: beach activities (surfing, snorkeling, etc.)
- `beach_amenities` - Many-to-many: facilities (restrooms, parking, etc.)
- `users` - User accounts (OAuth or magic link)
- `user_favorites` - User's saved beaches

### Dark Mode
Dark mode uses the selector strategy with `data-theme="dark"` attribute. Configured in `tailwind.config.js`.

### Tailwind Configuration

Custom extensions in `tailwind.config.js`:
- **Colors:** `brand-dark`, `brand-darker`, `brand-yellow`, `brand-text`, `brand-muted`
- **Shadows:** `shadow-card`, `shadow-glass`, `shadow-sunny`
- **Animation delays:** `delay-100` through `delay-600` (custom plugin)

## CSS Architecture

### Overview

Custom styles use a **partials-based architecture**. Source files live in `public/assets/css/partials/` and are bundled into `styles.css` for production.

```
public/assets/css/
├── partials/              # Source files (edit these)
│   ├── _variables.css     # CSS custom properties (colors, shadows, z-index)
│   ├── _base.css          # Typography, animations, glass utilities
│   ├── _loading.css       # Toasts, skeletons, spinners, HTMX states
│   ├── _cards.css         # Beach cards, score badges, prose classes
│   ├── _filters.css       # Filter chips, tag buttons, view toggle
│   ├── _conditions.css    # Beach conditions meter widget
│   ├── _map.css           # Map container, markers, popups
│   ├── _drawers.css       # Drawer/modal components
│   ├── _modals.css        # Share modal, lightbox
│   ├── _layout.css        # Hero section, empty states, hero gradients
│   ├── _guides.css        # Guide page layout, TOC, related guides
│   ├── _forms.css         # Range slider, Tom Select, compare bar
│   ├── _accessibility.css # Focus states, reduced motion, contrast
│   ├── _dark-mode.css     # All [data-theme="dark"] overrides
│   ├── _chat.css          # Chat floating panel + bubbles
│   ├── _responsive.css    # Mobile breakpoint overrides
│   └── _print.css         # Print styles
├── styles.css             # Bundled output (don't edit directly)
├── tailwind-input.css     # Tailwind entry point
└── tailwind.min.css       # Compiled Tailwind
```

### Workflow

1. **Edit** partials in `public/assets/css/partials/`
2. **Build** with `npm run build:css`
3. **Commit** both partials and bundled `styles.css`

### CSS Custom Properties

All colors, shadows, and z-index values are defined as CSS variables in `_variables.css`:

```css
/* Color Hierarchy - Unified System */
/* Primary (Blue) - Interactive elements, links, filters */
--color-primary: #3b82f6;
--color-primary-hover: #2563eb;

/* Secondary (Green) - Guide pages, success states */
--color-secondary: #10b981;
--color-secondary-hover: #059669;

/* Accent (Yellow) - Highlights, CTAs on dark backgrounds */
--color-accent: #fde047;
--color-accent-hover: #facc15;

/* Legacy aliases for backward compatibility */
--color-success: var(--color-secondary);

/* Light Mode Variables */
--color-white: #ffffff;
--color-text-on-light: #1f2937;        /* Dark text for light backgrounds */
--color-bg-light-primary: #ffffff;     /* White background */

/* Error/Warning */
--color-error: #ef4444;
--color-warning: #f59e0b;

/* Surfaces */
--color-surface: white;
--color-overlay: rgba(0, 0, 0, 0.5);

/* Z-index scale - always use these for stacking */
--z-drawer: 50;
--z-modal: 60;
--z-lightbox: 65;
--z-toast: 100;
```

### Color Usage Guidelines

- **Primary (Blue)** - Use for main interactive elements, links, filter chips
- **Secondary (Green)** - Use for guide pages, success states, positive actions
- **Accent (Yellow)** - Use for highlights and CTAs on dark backgrounds only
- Always use semantic variables, never hardcode hex values

### Adding New Styles

1. **Identify the correct partial** based on component type
2. **Use CSS variables** for colors, shadows, z-index
3. **Add dark mode overrides** in `_dark-mode.css` (not inline with component)
4. **Add responsive overrides** in `_responsive.css` for mobile-specific styles
5. **Run `npm run build:css`** to regenerate `styles.css`
6. **Update components when removing/renaming CSS classes** - If you remove or rename a CSS class, search for and update all PHP components that reference it to prevent broken styling

### Prose Content Classes

For rich text content sections, use these semantic classes:

- **`.prose-brand`** - For content on dark backgrounds (collection pages, beach detail pages)
  - Light text colors optimized for dark backgrounds
  - Full dark mode support in `_dark-mode.css`

- **`.prose-light`** - For content on light backgrounds (guide pages, light sections)
  - Dark text colors optimized for light backgrounds
  - Proper heading and link styling

- **`.beach-description`** - For collection page introduction sections
  - Styled for light gray backgrounds
  - Includes dark mode overrides

**Important:** Always pair prose classes with appropriate backgrounds to ensure readability.

### CSS Loading (CRITICAL)

**All pages MUST load CSS exclusively through `components/header.php`.** Never add `<link>` tags for CSS files directly in page `<head>` sections.

```php
<!-- WRONG - Don't do this in individual pages -->
<head>
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>

<!-- CORRECT - CSS loaded automatically via header.php -->
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
include APP_ROOT . '/components/header.php';
?>
```

Duplicate CSS loading causes race conditions and prevents styles from rendering properly. Cache-busting versions are managed centrally in `header.php`.

### Dark Mode Rules

All `[data-theme="dark"]` selectors are consolidated in `_dark-mode.css`:

```css
/* Good - in _dark-mode.css */
[data-theme="dark"] .my-component {
    background: var(--color-card-bg);
}

/* Bad - don't put dark mode styles inline with components */
.my-component { ... }
[data-theme="dark"] .my-component { ... }  /* Don't do this */
```

**Exception:** Theme toggle icon visibility stays with its component in `_filters.css`.

### Semantic Class Names

Use semantic class names for CSS hooks instead of targeting Tailwind utilities:

```php
<!-- Good - semantic class for CSS targeting -->
<div class="beach-hero h-64 md:h-96">

<!-- Bad - targeting Tailwind classes is fragile -->
<div class="h-64 md:h-96">  /* CSS: .h-64.md\:h-96 { } breaks if classes change */
```

Required semantic classes for mobile overrides:
- `.beach-hero`, `.beach-hero-overlay` - Beach detail hero
- `.beach-sidebar`, `.beach-main` - Beach detail layout

Hero gradient classes (defined in `_layout.css`):
- `.hero-gradient-dark` - Collection pages, dark blue/green gradient
- `.hero-gradient-guide` - Guide pages, green gradient (secondary color)
- Legacy: `.hero-gradient`, `.hero-gradient-purple` (deprecated, kept for compatibility)
- `.profile-stats`, `.profile-stat` - Profile page stats
- `.review-item`, `.review-item-content` - Review list items
- `.compare-bar-inner`, `.compare-bar-header` - Comparison bar
- `.container-padding` - Standard container padding

## Include File Architecture (IMPORTANT)

**CRITICAL: Avoid Duplicate Function Declarations**

The `inc/` directory contains shared PHP includes. These files have include guards to prevent duplicate function declarations that cause ERROR 500:

| File | Purpose | Notes |
|------|---------|-------|
| `helpers.php` | All utility/display functions | Contains: `getTagLabel()`, `getAmenityLabel()`, `getConditionLabel()`, `formatDistanceDisplay()` |
| `constants.php` | Data constants only | Contains: TAGS, AMENITIES, MUNICIPALITIES arrays + validation functions |
| `geo.php` | Geolocation utilities | Requires helpers.php for `formatDistanceDisplay()` |
| `db.php` | Database connection | Standalone, no dependencies |

**Rules to prevent ERROR 500:**
1. **Never define the same function in multiple files** - check `inc/helpers.php` first
2. **Always use `require_once`** - not `require` or `include`
3. **Display label functions belong in `helpers.php`** only (getTagLabel, getAmenityLabel, etc.)
4. **Validation functions belong in `constants.php`** (isValidTag, isValidMunicipality, etc.)
5. **Geolocation functions belong in `geo.php`** (calculateDistance, sortBeachesByDistance, etc.)

**If you see "Cannot redeclare function" errors:**
1. Check which two files define the function
2. Remove the duplicate from one file
3. Ensure the canonical file is included via `require_once`

## Troubleshooting

**Check error logs:**
```bash
# Nginx errors (most useful for PHP fatals)
tail -50 /var/log/nginx/beach-finder-error.log

# Production domain errors
tail -50 /var/log/nginx/puertoricobeachfinder-error.log

# PHP-FPM pool errors
tail -50 /var/log/php8.3-fpm.log
```

**Common ERROR 500 causes:**
1. Duplicate function declarations (check error log for "Cannot redeclare")
2. Missing include files
3. Database file permissions (data/ dir needs www-data write access)
4. PHP syntax errors (run `php -l filename.php` to check)
