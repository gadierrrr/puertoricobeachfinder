# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Puerto Rico Beach Finder is a PHP-based web application that provides a searchable database of 230+ beaches across Puerto Rico. It uses SQLite for data storage, HTMX for dynamic interactions, and Tailwind CSS for styling.

## Common Commands

**Build Tailwind CSS:**
```bash
npx tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.min.css --minify
```

**Watch Tailwind CSS during development:**
```bash
npx tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.min.css --watch
```

**Initialize/reset database:**
```bash
php init-db.php
```

**Run database migrations:**
```bash
php migrations/001-add-reviews-safety-quiz.php
```

**Minify JavaScript (using terser):**
```bash
npx terser assets/js/app.js -o assets/js/app.min.js
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
- `assets/js/` - Frontend JavaScript (app.js, map.js, filters.js, geolocation.js)
- `assets/css/` - Stylesheets (styles.css for custom CSS, tailwind-input.css as Tailwind entry)

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
