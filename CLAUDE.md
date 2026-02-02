# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Puerto Rico Beach Finder is a PHP-based web application that provides a searchable database of 230+ beaches across Puerto Rico. It uses SQLite for data storage, HTMX for dynamic interactions, and Tailwind CSS for styling.

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
php init-db.php
```

**Run database migrations:**
```bash
php migrations/001-add-reviews-safety-quiz.php
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
- `api/` - JSON/HTML API endpoints for HTMX requests
- `admin/` - Admin panel for content management
- `auth/` - Authentication handlers (Google OAuth)
- `data/` - SQLite database files
- `migrations/` - Database migration scripts
- `scripts/` - Build scripts (build-css.sh)
- `assets/js/` - Frontend JavaScript (app.js, map.js, filters.js, geolocation.js)
- `assets/css/` - Stylesheets
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

Custom styles use a **partials-based architecture**. Source files live in `assets/css/partials/` and are bundled into `styles.css` for production.

```
assets/css/
├── partials/              # Source files (edit these)
│   ├── _variables.css     # CSS custom properties (colors, shadows, z-index)
│   ├── _base.css          # Typography, animations, glass utilities
│   ├── _loading.css       # Toasts, skeletons, spinners, HTMX states
│   ├── _cards.css         # Beach cards, score badges
│   ├── _filters.css       # Filter chips, tag buttons, view toggle
│   ├── _conditions.css    # Beach conditions meter widget
│   ├── _map.css           # Map container, markers, popups
│   ├── _drawers.css       # Drawer/modal components
│   ├── _modals.css        # Share modal, lightbox
│   ├── _layout.css        # Hero section, empty states
│   ├── _forms.css         # Range slider, Tom Select, compare bar
│   ├── _accessibility.css # Focus states, reduced motion, contrast
│   ├── _dark-mode.css     # All [data-theme="dark"] overrides
│   ├── _responsive.css    # Mobile breakpoint overrides
│   └── _print.css         # Print styles
├── styles.css             # Bundled output (don't edit directly)
├── tailwind-input.css     # Tailwind entry point
└── tailwind.min.css       # Compiled Tailwind
```

### Workflow

1. **Edit** partials in `assets/css/partials/`
2. **Build** with `npm run build:css`
3. **Commit** both partials and bundled `styles.css`

### CSS Custom Properties

All colors, shadows, and z-index values are defined as CSS variables in `_variables.css`:

```css
/* Colors - use these instead of hardcoded hex values */
--color-primary: #3b82f6;
--color-primary-hover: #2563eb;
--color-success: #10b981;
--color-error: #ef4444;
--color-warning: #f59e0b;
--color-gray-500: #6b7280;

/* Surfaces */
--color-surface: white;
--color-overlay: rgba(0, 0, 0, 0.5);

/* Z-index scale - always use these for stacking */
--z-drawer: 50;
--z-modal: 60;
--z-lightbox: 65;
--z-toast: 100;
```

### Adding New Styles

1. **Identify the correct partial** based on component type
2. **Use CSS variables** for colors, shadows, z-index
3. **Add dark mode overrides** in `_dark-mode.css` (not inline with component)
4. **Add responsive overrides** in `_responsive.css` for mobile-specific styles
5. **Run `npm run build:css`** to regenerate `styles.css`

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
