# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Puerto Rico Beach Finder is a PHP-based web application that provides a searchable database of 230+ beaches across Puerto Rico. It uses SQLite for data storage, HTMX for dynamic interactions, and Tailwind CSS for styling. The site supports English/Spanish via an i18n system, has a collection page system for curated beach lists, and includes Google OAuth authentication.

## Common Commands

**Build all assets (CSS + Tailwind + JS):**
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

**Minify JavaScript:**
```bash
npm run build:js
```

**Watch Tailwind CSS during development:**
```bash
npm run dev
```

**Initialize/reset database:**
```bash
php scripts/init-db.php
```

**Run database migrations:**
```bash
php scripts/migrate.php
```

**Migration dry-run (preview without applying):**
```bash
php scripts/migrate.php --dry-run
```

**Verify all migrations applied:**
```bash
php scripts/migrate.php --check
```

**PHP syntax check (lint all files):**
```bash
find . -type f -name '*.php' -not -path './.git/*' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
```

**Lint single file:**
```bash
php -l path/to/file.php
```

**Design system lint (HTML/CSS conventions):**
```bash
node scripts/lint-design-system.js
```

**Local development server:**
```bash
php -S localhost:8082 -t public scripts/dev-router.php
```

**Run deploy checks locally:**
```bash
./deploy.sh
```

## Architecture

### Technology Stack
- **Backend:** PHP 8.3+ (no framework, procedural with include guards)
- **Database:** SQLite3 with WAL mode, foreign keys ON (`data/beach-finder.db`)
- **Frontend:** HTMX for dynamic updates, vanilla JavaScript
- **CSS:** Tailwind CSS 3.x + custom CSS partials bundled via shell script
- **Icons:** Lucide icons (SVG)
- **Maps:** Leaflet.js
- **Auth:** Google OAuth 2.0 + magic links (session-based)
- **Email:** Resend API
- **i18n:** English/Spanish (inc/i18n.php + inc/lang/)
- **Fonts:** Inter (sans), Playfair Display (serif) - self-hosted

### Directory Structure
- `inc/` - Core PHP includes (db, helpers, auth, constants, bootstrap, i18n, etc.)
  - `inc/lang/` - Translation files (en.php, es.php)
- `components/` - Reusable PHP UI components (header, footer, beach-card, filters, etc.)
  - `components/collection/` - Collection explorer components (hero, toolbar, results, card)
- `public/` - Web document root (**ONLY** this should be web-served)
  - `public/api/` - JSON/HTML API endpoints for HTMX requests
  - `public/admin/` - Admin panel for content management
  - `public/auth/` - Authentication handlers (Google OAuth)
  - `public/guides/` - Editorial/guide pages (11 guides)
  - `public/errors/` - Friendly error pages (404, 500)
  - `public/assets/js/` - Frontend JavaScript
  - `public/assets/css/` - Stylesheets (see CSS Architecture below)
  - `public/assets/icons/` - PWA app icons
  - `public/images/` - Beach images and thumbnails
- `data/` - SQLite database and import files (never web-served)
- `migrations/` - Database migration scripts (001 through 013)
- `scripts/` - CLI tools + build scripts (never web-served)
  - `scripts/content/` - AI content generation system
- `config/` - Configuration files (design-lint-allowlist.json)
- `deploy/` - Deployment configs (nginx hardening template)
- `docs/` - Project documentation and reports
- `templates/` - Email and page templates

### Bootstrap Pattern (CRITICAL)

**Every public PHP page** must start with:
```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
```

This loads `bootstrap.php` at the repo root, which defines:
- `APP_ROOT` - Absolute path to repository root
- `PUBLIC_ROOT` - `APP_ROOT . '/public'`
- Environment variables (from `.env`)
- Error handlers

**Always use `APP_ROOT` / `PUBLIC_ROOT`** for filesystem paths. Avoid fragile `__DIR__` relative traversal.

```php
// Good
require_once APP_ROOT . '/inc/helpers.php';
include APP_ROOT . '/components/header.php';

// Bad - fragile relative paths
require_once __DIR__ . '/../inc/helpers.php';
```

### Key Patterns

**Database Access:** Use the helper functions in `inc/db.php`:
- `query($sql, $params)` - Returns array of rows
- `queryOne($sql, $params)` - Returns single row or null
- `execute($sql, $params)` - For INSERT/UPDATE/DELETE
- Always use parameterized queries with named (`:param`) or positional (`?`) placeholders

**Batch Data Loading:** Use `attachBeachMetadata($beaches)` to efficiently load tags and amenities for multiple beaches (avoids N+1 queries).

**HTML Escaping:** Always use `h($string)` from `inc/helpers.php` for output escaping. Never output user/database content without escaping.

**HTMX Integration:** API endpoints return HTML when `HX-Request` header is present, JSON otherwise. Check with `isHtmx()` helper.

**Authentication:** Session-based with magic links or Google OAuth. Use `isAuthenticated()`, `currentUser()`, and `requireAuth()` helpers from `inc/helpers.php`. Admin access uses `isAdmin()` and `requireAdmin()` from `inc/admin.php`.

**CSRF Protection:** All POST endpoints must include CSRF validation:
```php
// In forms
echo csrfField(); // outputs hidden input

// In POST handlers
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => 'Invalid token'], 403);
}
```

**Controlled Vocabularies:** Tags, amenities, municipalities, and condition scales are defined in `inc/constants.php`. Use validation helpers like `isValidTag()`, `isValidMunicipality()`, `isValidAmenity()`.

**Shared Components:** Reusable UI components in `components/` directory:
- `header.php` - Page header and all CSS loading (MUST be included on every page)
- `footer.php` / `footer-minimal.php` - Page footers
- `nav.php` - Navigation menu
- `beach-card.php` - Individual beach card display
- `beach-grid.php` - Grid layout for beaches
- `beach-drawer.php` - Beach detail drawer/modal
- `breadcrumbs.php` - Navigation breadcrumbs
- `hero-guide.php` - Used by all guide pages, requires `$pageTitle`, `$pageDescription`, optional `$breadcrumbs`
- `seo-schemas.php` - JSON-LD structured data for SEO
- `weather-widget.php` - Weather display for beach locations
- `review-card.php` - User review display
- `page-shell.php` - Base page layout wrapper
- `collection/explorer.php` - Shared collection explorer (hero + toolbar + results)
- Always use shared components for consistency; avoid creating inline variants

**Beach Images:** Use `renderBeachImage($beach, $alt, $class, $sizes, $loading)` from `inc/helpers.php` for responsive images with automatic srcset generation. Use `getBeachImageUrl($beach, $size)` for specific sizes ('original', 'large', 'medium', 'thumb', 'placeholder'). Use `getBeachImageAlt($beach)` for SEO-friendly alt text.

**Internationalization:** Use `__('key')` for translated strings and `_e('key')` for echoed output. Translation files are in `inc/lang/en.php` and `inc/lang/es.php`. Use `getCurrentLanguage()` to check active language.

### Collection System

Collection pages (best-beaches, best-family-beaches, etc.) use a shared architecture:

1. **Context Registry** (`inc/collection_contexts.php`) - Defines each collection's config (slug, mode, default sort/limit, hero content)
2. **Query Builder** (`inc/collection_query.php`) - Shared filtering, sorting, and pagination
3. **Explorer Component** (`components/collection/explorer.php`) - Renders hero + toolbar + results
4. **API Endpoint** (`public/api/collection-beaches.php`) - HTMX endpoint for dynamic filtering

To add a new collection page:
1. Add context config to `collectionContextRegistry()` in `inc/collection_contexts.php`
2. Create the page in `public/` using the explorer component
3. The page should load the collection context and include `components/collection/explorer.php`

### Database Schema (Key Tables)
- `beaches` - Main beach records with coordinates, ratings, conditions, slug, cover_image, publish_status
- `beach_tags` - Many-to-many: beach activities (surfing, snorkeling, etc.)
- `beach_amenities` - Many-to-many: facilities (restrooms, parking, etc.)
- `beach_images` - Admin-uploaded beach images with size variants
- `beach_checkins` - User check-in tracking with conditions/crowd
- `content_sections` - Extended beach content (history, getting_there, etc.)
- `users` - User accounts (OAuth or magic link)
- `user_favorites` - User's saved beaches
- `user_progress` - Explorer level progression
- `reviews` - User reviews with ratings and photos
- `email_templates` - Stored email templates

### Dark Mode
Dark mode uses the selector strategy with `data-theme="dark"` attribute. Configured in `tailwind.config.js`.

### Tailwind Configuration

Custom extensions in `tailwind.config.js`:
- **Font families:** `font-sans` (Inter), `font-serif` (Playfair Display)
- **Colors:** `brand-dark`, `brand-darker`, `brand-yellow`, `brand-text`, `brand-muted`, plus `tropical` (cyan, sun, coral, sand) and `warm` palettes
- **Shadows:** `shadow-card`, `shadow-card-hover`, `shadow-soft`, `shadow-soft-lg`, `shadow-glass`, `shadow-sunny`
- **Animations:** `fade-in-up`, `bounce-slow`
- **Animation delays:** `delay-100` through `delay-600` (custom plugin)
- **Background images:** `bg-hero-gradient`

## CSS Architecture

### Overview

Custom styles use a **partials-based architecture**. Source files live in `public/assets/css/partials/` and are bundled into `styles.css` by `scripts/build-css.sh`.

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
│   ├── _collections.css   # Collection page specific styles
│   ├── _forms.css         # Range slider, Tom Select, compare bar
│   ├── _accessibility.css # Focus states, reduced motion, contrast
│   ├── _dark-mode.css     # All [data-theme="dark"] overrides
│   ├── _responsive.css    # Mobile breakpoint overrides
│   └── _print.css         # Print styles
├── styles.css             # Bundled output (don't edit directly)
├── tailwind-input.css     # Tailwind entry point
└── tailwind.min.css       # Compiled Tailwind output
```

**Note:** `_beach.css` exists in partials but is **not included** in the build script. If you need to add beach detail page styles, either add them to the appropriate existing partial or add `_beach.css` to `scripts/build-css.sh` in the correct order.

### Workflow

1. **Edit** partials in `public/assets/css/partials/`
2. **Build** with `npm run build:css`
3. **If adding a new partial**, add it to `scripts/build-css.sh` in the correct order
4. **Commit** both partials and bundled `styles.css`

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
- Always use semantic CSS variables, never hardcode hex values

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
- **`.prose-light`** - For content on light backgrounds (guide pages, light sections)
- **`.beach-description`** - For collection page introduction sections

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
- `.profile-stats`, `.profile-stat` - Profile page stats
- `.review-item`, `.review-item-content` - Review list items
- `.compare-bar-inner`, `.compare-bar-header` - Comparison bar
- `.container-padding` - Standard container padding

Hero gradient classes (defined in `_layout.css`):
- `.hero-gradient-dark` - Collection pages, dark blue/green gradient
- `.hero-gradient-guide` - Guide pages, green gradient (secondary color)
- Legacy: `.hero-gradient`, `.hero-gradient-purple` (deprecated, kept for compatibility)

## Include File Architecture (IMPORTANT)

**CRITICAL: Avoid Duplicate Function Declarations**

The `inc/` directory contains shared PHP includes. These files have include guards (`if (defined('...')) return;`) to prevent duplicate function declarations that cause ERROR 500.

### Core Include Files

| File | Purpose | Key Functions/Constants |
|------|---------|------------------------|
| `bootstrap.php` | App initialization | Loads env, error handlers; sets `APP_ROOT`, `PUBLIC_ROOT` |
| `db.php` | Database connection & helpers | `getDB()`, `query()`, `queryOne()`, `execute()`, `attachBeachMetadata()`, `uuid()` |
| `helpers.php` | All display/utility functions | `h()`, `isHtmx()`, `csrfToken()`, `getTagLabel()`, `getAmenityLabel()`, `renderBeachImage()`, `getBeachImageUrl()`, `currentUser()`, `isAuthenticated()`, `requireAuth()` |
| `constants.php` | Controlled vocabularies & validation | `TAGS`, `AMENITIES`, `MUNICIPALITIES`, `CONDITION_SCALES`, `CONTENT_SECTIONS`; `isValidTag()`, `isValidMunicipality()` |
| `geo.php` | Geolocation utilities | `calculateDistance()`, `sortBeachesByDistance()`, `filterBeachesByDistance()` |
| `auth.php` | Magic link authentication | `sendMagicLink()`, `verifyMagicLink()`, `logout()` |
| `google-oauth.php` | Google OAuth 2.0 | `getGoogleAuthUrl()`, `exchangeCodeForToken()`, `findOrCreateGoogleUser()`, `loginUser()` |
| `admin.php` | Admin helpers | `isAdmin()`, `requireAdmin()`, `getAdminStats()` |
| `collection_contexts.php` | Collection page registry | `collectionContextRegistry()` - maps collection keys to configs |
| `collection_query.php` | Collection query builders | `fetchCollectionBeaches()`, `collectionFiltersFromRequest()`, `getCollectionContext()` |
| `i18n.php` | Internationalization | `__()`, `_e()`, `getCurrentLanguage()`, `setLanguage()` |
| `env.php` | Environment variables | `env()`, `envRequire()`, `envBool()`, `loadEnvFile()` |
| `email.php` | Email via Resend API | `sendEmail()`, `sendWelcomeEmail()`, `sendTemplateEmail()` |
| `weather.php` | Weather API integration | `getWeatherForLocation()`, `calculateBeachScore()` |
| `crowd.php` | Crowd level tracking | `getBeachCrowdLevel()`, `getBatchCrowdLevels()`, `getCrowdBadgeHtml()` |
| `image-optimizer.php` | Image processing (GD) | `optimizeImage()`, `getBeachImages()`, `getBeachCoverImage()` |
| `rate_limiter.php` | Rate limiting | `RateLimiter` class for API throttling |
| `security_headers.php` | Security headers | HTTP security header configuration |
| `session.php` | Session management | Secure session configuration |
| `error-handler.php` | Error handling | `appLog()`, `renderHttpError()`, `registerErrorHandlers()` |

### Rules to Prevent ERROR 500
1. **Never define the same function in multiple files** - check `inc/helpers.php` first
2. **Always use `require_once`** - not `require` or `include`
3. **Display label functions belong in `helpers.php`** only (getTagLabel, getAmenityLabel, etc.)
4. **Validation functions belong in `constants.php`** (isValidTag, isValidMunicipality, etc.)
5. **Geolocation functions belong in `geo.php`** (calculateDistance, sortBeachesByDistance, etc.)

**If you see "Cannot redeclare function" errors:**
1. Check which two files define the function
2. Remove the duplicate from one file
3. Ensure the canonical file is included via `require_once`

## Security Practices

- **CSRF protection** on all POST endpoints using `csrfToken()` / `validateCsrf()`
- **Output escaping** with `h()` on all user/database content
- **Parameterized queries** - never concatenate user input into SQL
- **Rate limiting** via `RateLimiter` class on API endpoints
- **Session fingerprinting** (IP + User-Agent hash) to prevent hijacking
- **Session timeout** (30 minutes inactivity)
- **Security headers** configured in `inc/security_headers.php`
- **Input validation** using controlled vocabulary validators (`isValidTag()`, `isValidMunicipality()`, etc.)
- **Redirect sanitization** with `sanitizeInternalRedirect()` - prevents open redirects
- **No secrets in code** - CI runs gitleaks secret scanning; use `.env` for API keys
- **Admin access control** - `requireAdmin()` guard on all admin pages/endpoints

## CI/CD Pipeline

GitHub Actions CI (`.github/workflows/ci.yml`) runs on every push and PR:

1. **PHP lint** - Syntax checks all `.php` files
2. **npm ci + build** - Installs deps and builds CSS/Tailwind/JS
3. **Verify generated assets committed** - `git diff --exit-code` on compiled CSS/JS
4. **Database init + migrations** - Validates schema and migration scripts
5. **Secret scanning** - gitleaks checks full git history

**Important: Generated assets must be committed.** After running `npm run build`, commit the generated files:
- `public/assets/css/tailwind.min.css`
- `public/assets/css/styles.css`
- `public/assets/js/app.min.js`
- `public/assets/js/collection-explorer.min.js`

CI will **fail** if generated assets differ from committed versions.

Run the full deploy check locally before pushing:
```bash
./deploy.sh
```

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

**CSS not rendering properly:**
1. Check for duplicate `<link>` tags (CSS must only load via `components/header.php`)
2. Run `npm run build` to rebuild all assets
3. Clear browser cache (cache-busting is managed in header.php)

**Migration issues:**
1. `php scripts/migrate.php --dry-run` to preview changes
2. `php scripts/migrate.php --check` to verify all applied
3. Check `data/` directory permissions for SQLite write access
