# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Puerto Rico Beach Finder is a PHP-based web application that provides a searchable database of 468 beaches across Puerto Rico. It uses SQLite for data storage, HTMX for dynamic interactions, and Tailwind CSS for styling.

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
  - `components/beach/` - Beach detail page components (13 files, see Beach Detail Architecture below)
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
- `public/assets/js/` - Frontend JavaScript (app.js, map.js, filters.js, geolocation.js)
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

The beach detail page (`public/beach.php`) is decomposed into 13 focused components. The main file (~230 lines) handles data fetching, SEO, and component includes only.

```
public/beach.php                        (230 lines) — data + includes
components/beach/
  ├── hero.php             (55 lines)  — Cover photo, gradient, breadcrumbs, title
  ├── info-bar.php         (67 lines)  — Rating badge, tags, directions, share
  ├── section-nav.php      (29 lines)  — Sticky horizontal tab navigation
  ├── quick-facts.php      (94 lines)  — 2x2 Quick Facts grid (emoji icons)
  ├── about.php            (50 lines)  — Description + feature badges + visitor tips
  ├── extended-sections.php(79 lines)  — Grouped sections: Plan Your Visit / About This Beach
  ├── photos.php           (50 lines)  — Gallery + user photo uploads (conditional)
  ├── reviews.php          (46 lines)  — User reviews (conditional, hidden when empty)
  ├── sidebar.php          (175 lines) — Weather, conditions, crowd, map, amenities, safety
  ├── related.php          (74 lines)  — Planning guides + similar beaches
  ├── sticky-bar.php       (57 lines)  — Mobile bottom action bar (directions/save/share)
  ├── modals.php           (1166 lines)— All modal dialogs (share, lightbox, report, checkin, review, upload)
  └── scripts.php          (73 lines)  — Weather widget loader + section nav observer
```

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
│   ├── _cards.css         # Component classes, beach cards, score badges, prose
│   ├── _filters.css       # Filter chips, tag buttons, view toggle
│   ├── _map.css           # Map container, markers, popups
│   ├── _drawers.css       # Drawer/modal components
│   ├── _modals.css        # Share modal, lightbox
│   ├── _layout.css        # Hero section, empty states, hero gradients
│   ├── _guides.css        # Guide page layout, TOC, related guides
│   ├── _forms.css         # Range slider, Tom Select, compare bar
│   ├── _accessibility.css # Focus states, reduced motion, contrast
│   ├── _dark-mode.css     # All [data-theme="dark"] overrides (incl. guide remaps)
│   ├── _responsive.css    # Mobile breakpoints (400/640/768/1024px)
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

### Component Classes

Reusable CSS classes are defined in `_cards.css`. See `DESIGN-SYSTEM.md` for the full reference.

| Class | Purpose |
|-------|---------|
| `.card-glass` | Translucent dark card container |
| `.card-glass--interactive` | Adds hover border effect to `.card-glass` |
| `.btn-glass` | Translucent action button on dark backgrounds |
| `.btn-primary` | Yellow accent CTA button |
| `.text-shadow-hero` | Text shadow for image overlays |
| `.prose-brand` | Rich text on dark backgrounds (collection intros, beach descriptions) |
| `.beach-card` | Beach card with hover lift |
| `.beach-detail-card` | Dark card on beach detail page |

### Prose Content Classes

- **`.prose-brand`** - For all rich text content on dark backgrounds (collection pages, beach detail)
  - Full dark mode support in `_dark-mode.css`

**Note:** `.prose-light` and `.beach-description` were removed — use `.prose-brand` instead.

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
