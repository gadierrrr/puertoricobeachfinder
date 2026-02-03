# CLAUDE.md

Guidance for Claude Code and other LLMs working with this codebase.

## Quick Reference

| Aspect | Detail |
|--------|--------|
| **Stack** | PHP 8.x (procedural, no framework), SQLite3, HTMX, Tailwind CSS 3.x, vanilla JS |
| **Database** | `data/beach-finder.db` (SQLite3, WAL mode) |
| **Auth** | Google OAuth (primary); magic links (disabled) |
| **Routing** | Direct file mapping — no router. URL → PHP file. |
| **Templating** | None — raw PHP includes with `<?php include ?>` |
| **Icons** | Lucide icons (loaded in footer.php) |
| **i18n** | English + Spanish via `inc/i18n.php` and `inc/lang/{en,es}.php` |
| **Testing** | No test framework or test files exist |

## Commands

```bash
npm run build           # Build all assets (CSS partials + Tailwind)
npm run build:css       # Concatenate CSS partials → assets/css/styles.css
npm run build:tailwind  # Compile Tailwind → assets/css/tailwind.min.css
npm run build:js        # Minify app.js → app.min.js (via Terser)
npm run dev             # Watch Tailwind during development

php init-db.php                              # Initialize/reset database schema
php migrations/001-add-reviews-safety-quiz.php  # Run a specific migration
php -l filename.php                          # Syntax-check a PHP file
```

### Verification After Changes

```bash
php -l changed-file.php           # Always syntax-check modified PHP
npm run build                     # Rebuild if CSS partials or Tailwind classes changed
npm run build:js                  # Rebuild if assets/js/app.js changed
```

## Routing & Page Map

No routing framework. URLs map directly to PHP files on disk. Nginx handles URL rewriting externally.

### Public Pages

| URL Pattern | File | Purpose |
|-------------|------|---------|
| `/` | `index.php` | Homepage — search, filters, trending carousel, quiz CTA |
| `/beach/{slug}` | `beach.php` | Beach detail — reviews, gallery, map, weather |
| `/best-beaches` | `best-beaches.php` | Collection: top-rated beaches |
| `/best-surfing-beaches` | `best-surfing-beaches.php` | Collection: surfing |
| `/best-snorkeling-beaches` | `best-snorkeling-beaches.php` | Collection: snorkeling |
| `/best-family-beaches` | `best-family-beaches.php` | Collection: family-friendly |
| `/beaches-near-san-juan` | `beaches-near-san-juan.php` | Collection: near San Juan |
| `/hidden-beaches-puerto-rico` | `hidden-beaches-puerto-rico.php` | Collection: hidden gems |
| `/guides` | `guides/index.php` | Guide listing page |
| `/guides/beach-safety-tips` | `guides/beach-safety-tips.php` | Guide: safety |
| `/guides/snorkeling-guide` | `guides/snorkeling-guide.php` | Guide: snorkeling |
| `/guides/surfing-guide` | `guides/surfing-guide.php` | Guide: surfing |
| `/guides/getting-to-puerto-rico-beaches` | `guides/getting-to-puerto-rico-beaches.php` | Guide: transportation |
| `/guides/culebra-vs-vieques` | `guides/culebra-vs-vieques.php` | Guide: island comparison |
| `/guides/best-time-visit-puerto-rico-beaches` | `guides/best-time-visit-puerto-rico-beaches.php` | Guide: timing |
| `/guides/bioluminescent-bays` | `guides/bioluminescent-bays.php` | Guide: bio bays |
| `/login` | `login.php` | Login page (Google OAuth) |

### API Endpoints

All in `api/` directory. HTMX-aware: return HTML when `HX-Request` header is present, JSON otherwise. Check with `isHtmx()`.

| Endpoint | Method | Purpose | Key Params |
|----------|--------|---------|------------|
| `api/beaches.php` | GET | List/filter beaches | `tags[]`, `municipality`, `q`, `sort`, `page`, `limit`, `format` |
| `api/beach-detail.php` | GET | Single beach detail | `id` or `slug`, `format` |
| `api/beaches-map.php` | GET | All beaches for map (GeoJSON-like) | — |
| `api/toggle-favorite.php` | POST | Save/unsave beach | `beach_id` (requires auth + CSRF) |
| `api/checkin.php` | POST | Check in at beach | `beach_id`, optional notes/photos |
| `api/reviews/submit.php` | POST | Submit review | `beach_id`, `rating`, `title`, `review_text`, `visit_date` |
| `api/photos.php` | GET/POST | Beach photos | `beach_id` |
| `api/lists.php` | GET | User's favorites/checkins | — (requires auth) |
| `api/random-beach.php` | GET | Random beach for discovery | — |
| `api/weather.php` | GET | Current weather for beach | `lat`, `lng` |
| `api/weather-batch.php` | GET | Weather for multiple beaches | beach IDs |
| `api/set-language.php` | POST | Set language preference | `lang` (en/es) |
| `api/quiz/match.php` | POST | Quiz → recommendations | quiz answers |

### Admin Pages (require `requireAdmin()`)

| URL | File | Purpose |
|-----|------|---------|
| `/admin` | `admin/index.php` | Dashboard with stats |
| `/admin/beaches` | `admin/beaches.php` | Beach CRUD |
| `/admin/reviews` | `admin/reviews.php` | Review moderation |
| `/admin/users` | `admin/users.php` | User management |
| `/admin/emails` | `admin/emails.php` | Email templates |
| `/admin/place-id-audit` | `admin/place-id-audit.php` | Google Place ID audit |

### Admin API

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/admin/upload-image.php` | POST | Upload beach images |
| `api/admin/audit-place-ids.php` | GET | Audit Google Place IDs |
| `api/admin/quick-add-beach.php` | POST | Quick-add a beach |

## Architecture

### Directory Structure

```
├── inc/                  # Core PHP includes (see Include Files section)
├── components/           # Reusable PHP UI components (see Components section)
├── api/                  # API endpoints for HTMX and JSON
├── admin/                # Admin panel pages
├── auth/                 # OAuth handlers
│   └── google/           # Google OAuth (index.php → callback.php)
├── guides/               # SEO guide pages
├── data/                 # SQLite database files
├── migrations/           # Database migration scripts (001–012)
├── scripts/              # Build scripts (build-css.sh)
├── assets/
│   ├── js/               # Frontend JavaScript (source files)
│   └── css/
│       ├── partials/     # CSS source files (edit THESE)
│       ├── styles.css    # Generated bundle (don't edit directly)
│       ├── tailwind-input.css  # Tailwind entry point
│       └── tailwind.min.css    # Generated Tailwind output
├── images/               # Beach cover images
├── docs/                 # Documentation
└── *.php                 # Top-level page files (index, beach, collections)
```

### Source vs. Generated Files

**Edit these (source):**
- `assets/css/partials/*.css` — CSS source partials
- `assets/css/tailwind-input.css` — Tailwind entry point
- `assets/js/app.js` — Main JavaScript source
- All `.php` files

**Don't edit (generated by build):**
- `assets/css/styles.css` — built from partials by `npm run build:css`
- `assets/css/tailwind.min.css` — built by `npm run build:tailwind`
- `assets/js/app.min.js` — built by `npm run build:js`

## Key Patterns

### Database Access (`inc/db.php`)

```php
$rows = query("SELECT * FROM beaches WHERE municipality = ?", [$muni]);
$beach = queryOne("SELECT * FROM beaches WHERE slug = ?", [$slug]);
execute("UPDATE beaches SET name = ? WHERE id = ?", [$name, $id]);
```

**Batch loading (avoids N+1):** Always use `attachBeachMetadata($beaches)` after fetching multiple beaches. This runs 2 queries (tags + amenities for all) instead of 2N.

### HTML Escaping

Always use `h($string)` from `inc/helpers.php` for any user-supplied output. This wraps `htmlspecialchars()` with UTF-8.

### HTMX Integration

API endpoints detect HTMX via `isHtmx()` helper (checks `HX-Request` header). Pattern:

```php
// In an API endpoint
if (isHtmx()) {
    // Return HTML fragment for swap
    include __DIR__ . '/../components/beach-card.php';
} else {
    jsonResponse($data);
}
```

Client-side HTMX trigger example:
```html
<button hx-get="/api/beaches.php?page=2" hx-target="#beach-grid" hx-swap="beforeend">
    Load More
</button>
```

Programmatic HTMX request (JavaScript):
```javascript
htmx.ajax('GET', `/api/beach-detail.php?id=${id}`, {
    target: '#drawer-content-inner',
    swap: 'innerHTML'
});
```

### Authentication

- **Session setup:** `inc/session.php` — HTTPOnly, Secure, SameSite=Lax cookies; 30-min timeout; session fingerprinting (IP + User-Agent)
- **OAuth flow:** `auth/google/index.php` → Google → `auth/google/callback.php` → sets `$_SESSION['user_id']`
- **Helpers in `inc/helpers.php`:** `isAuthenticated()`, `currentUser()`, `requireAuth()` (redirect), `requireAdmin()` (403)
- **CSRF:** `csrfToken()`, `validateCsrf()`, `csrfField()` — required on all POST forms

### Controlled Vocabularies (`inc/constants.php`)

All valid values are defined as constants. Always validate input against them:

- **TAGS:** calm-waters, surfing, snorkeling, family-friendly, accessible, secluded, popular, scenic, swimming, diving, fishing, camping
- **AMENITIES:** restrooms, showers, lifeguard, parking, food, equipment-rental, accessibility, picnic-areas, shade-structures, water-sports
- **MUNICIPALITIES:** All 78 Puerto Rico municipalities
- **CONDITION_SCALES:** sargassum (none/light/moderate/heavy), surf (calm/small/medium/large), wind (calm/light/moderate/strong)

Validation: `isValidTag($tag)`, `isValidAmenity($amenity)`, `isValidMunicipality($muni)`

## Components (`components/`)

All components are plain PHP includes. They expect variables to be set in the including scope.

### Layout Components

| Component | Required Variables | Notes |
|-----------|-------------------|-------|
| `header.php` | `$pageTitle`, `$pageDescription`, `$canonicalUrl` (optional) | Loads ALL CSS, meta tags, nav. **All pages must use this for CSS.** |
| `footer.php` | — | Scripts, Lucide icons, analytics |
| `header-nav-only.php` | — | Minimal header variant |

### Hero Components

| Component | Required Variables | Notes |
|-----------|-------------------|-------|
| `hero-guide.php` | `$pageTitle`, `$pageDescription`, optional `$breadcrumbs` | Green gradient, used by all guide pages |
| `hero-collection.php` | `$pageTitle`, `$pageDescription` | Dark blue gradient, used by collection pages |

### Beach Display

| Component | Required Variables | Notes |
|-----------|-------------------|-------|
| `beach-card.php` | `$beach` (array with tags/amenities attached) | Card with image, rating, tags |
| `beach-grid.php` | `$beaches` | Grid container, loads cards |
| `beach-drawer.php` | `$beach` (full detail) | Slide-up detail drawer |

### Other Components

| Component | Purpose |
|-----------|---------|
| `filters.php` | Filter sidebar (tags, municipality, distance, sort) |
| `review-card.php` | User review display |
| `weather-widget.php` | Weather widget for beach detail |
| `breadcrumbs.php` | Navigation breadcrumbs |
| `quick-fact-card.php` | Info card component |
| `seo-schemas.php` | JSON-LD structured data generation |

**Rule:** Always use shared components. Never create inline variants of existing components.

## Include Files (`inc/`)

### Dependency & Ownership Rules

| File | Purpose | Owns | Dependencies |
|------|---------|------|--------------|
| `db.php` | Database connection + query helpers | `query()`, `queryOne()`, `execute()`, `attachBeachMetadata()` | None |
| `helpers.php` | All utility/display functions | `h()`, `isHtmx()`, `csrfToken()`, auth helpers, label getters, image helpers, `searchBeaches()` | None |
| `constants.php` | Data constants + validation | TAGS, AMENITIES, MUNICIPALITIES arrays, `isValidTag()`, etc. | None |
| `geo.php` | Geolocation utilities | `calculateDistance()`, `sortBeachesByDistance()`, `filterBeachesByDistance()` | `helpers.php` |
| `session.php` | Session configuration | Cookie settings, timeout | Must be included BEFORE `session_start()` |
| `auth.php` | Magic link auth | `sendMagicLink()`, `verifyMagicLink()` | `db.php`, `helpers.php` |
| `google-oauth.php` | Google OAuth flow | `initiateOAuth()`, `exchangeCodeForToken()`, `getGoogleUserInfo()` | `db.php` |
| `admin.php` | Admin access control | `isAdmin()`, `requireAdmin()`, `getAdminStats()` | `helpers.php` |
| `security_headers.php` | HTTP security headers | CSP, X-Frame-Options, cache control | None |
| `rate_limiter.php` | Brute-force prevention | `RateLimiter` class | `db.php` |
| `i18n.php` | Internationalization | `getCurrentLanguage()`, `setLanguage()`, `loadTranslations()` | None |
| `email.php` | Email sending | `sendEmail()`, `sendTemplateEmail()` | `db.php` |
| `weather.php` | Weather API wrapper | `getWeather()` (1-hour cache) | None |
| `crowd.php` | Crowd estimation | `getCrowdLevel()`, `getCrowdHistory()` | `db.php` |
| `image-optimizer.php` | Image processing | `optimizeImage()`, `generateThumbnail()` | None |

### Critical Rules to Prevent ERROR 500

1. **Always use `require_once`** — never `require` or `include`
2. **Never define the same function in multiple files** — check `helpers.php` first
3. **Display label functions → `helpers.php`** only (`getTagLabel()`, `getAmenityLabel()`, etc.)
4. **Validation functions → `constants.php`** (`isValidTag()`, `isValidMunicipality()`, etc.)
5. **Geolocation functions → `geo.php`** (`calculateDistance()`, `sortBeachesByDistance()`, etc.)

If you see "Cannot redeclare function" errors: find the two files defining it, remove the duplicate, and ensure the canonical file is `require_once`'d.

## JavaScript (`assets/js/`)

| File | Purpose | Key Exports/Globals |
|------|---------|-------------------|
| `app.js` | Main application — state management, beach filtering, favorites, drawer, toasts, focus trap, search | Global `state` object |
| `filters.js` | Filter chips, municipality select, distance slider, sort dropdown, HTMX filter application | — |
| `geolocation.js` | Browser geolocation API, Haversine distance calc, localStorage persistence, distance badges | — |
| `map.js` | MapLibre GL initialization, beach markers, clustering, popups, filter sync | CartoDB basemap |
| `share.js` | Share modal, clipboard copy, social sharing (Twitter, Facebook, WhatsApp) | — |
| `admin-images.js` | Admin image upload, cropping, drag-and-drop, thumbnails | — |

**No module system** — all scripts loaded via `<script>` tags in `footer.php`. They share state through the global `state` object and DOM events.

## Database Schema

### Core Tables

**`beaches`** — Main beach records
- `id` (TEXT PK), `slug` (UNIQUE), `name`, `municipality`, `lat`, `lng`
- Conditions: `sargassum`, `surf`, `wind`
- Content: `cover_image`, `description`, `parking_details`, `safety_info`, `local_tips`, `best_time`
- Google: `place_id`, `google_rating`, `google_review_count`
- `publish_status` (published/draft), `created_at`, `updated_at`

**`beach_tags`** — Many-to-many: beach ↔ activity tags
- `beach_id`, `tag` — unique constraint on pair

**`beach_amenities`** — Many-to-many: beach ↔ facilities
- `beach_id`, `amenity` — unique constraint on pair

**`beach_gallery`** — Additional photos: `beach_id`, `image_url`, `position`

**`beach_aliases`** — Alternative names: `beach_id`, `alias`

**`beach_features`** — Highlight boxes: `beach_id`, `title`, `description`, `position`

**`beach_tips`** — Visitor tips: `beach_id`, `category`, `tip`, `position`

**`beach_safety`** — Safety data: `beach_id` (PK), `swim_difficulty`, `lifeguard`, `rip_current_risk`, `typical_wave_height`, `hazards`, `nearest_hospital`, `emergency_phone`, `safe_for_children`

**`beach_content_sections`** — Extended content: `beach_id`, `section_type`, `heading`, `content`, `display_order`, `status`

### User Tables

**`users`** — `id` (TEXT PK), `email` (UNIQUE), `name`, `is_admin`, timestamps

**`user_favorites`** — `user_id`, `beach_id` — unique constraint on pair

**`beach_reviews`** — `id`, `beach_id`, `user_id`, `rating` (1–5), `title`, `review_text`, `visit_date`, `visit_type`, `would_recommend`, `helpful_count`, `status` (published/pending/hidden)

**`review_photos`** — `review_id`, `photo_url`, `caption`

**`review_helpful_votes`** — `review_id`, `user_id` — unique constraint on pair

**`magic_links`** — `email`, `token` (hashed), `expires_at`, `used`

**`rate_limits`** — `identifier`, `action`, `attempts`, `window_start`

## CSS Architecture

### Partials System

Source files in `assets/css/partials/` are concatenated (in order) into `assets/css/styles.css` by `scripts/build-css.sh`.

| Partial | Purpose |
|---------|---------|
| `_variables.css` | CSS custom properties (colors, shadows, z-index scale) |
| `_base.css` | Typography, animations, glass utilities |
| `_loading.css` | Toasts, skeletons, spinners, HTMX loading states |
| `_cards.css` | Beach cards, score badges, prose content classes |
| `_filters.css` | Filter chips, tag buttons, view toggle, theme toggle |
| `_conditions.css` | Beach conditions meter widget (sargassum/surf/wind) |
| `_map.css` | Map container, markers, popups |
| `_drawers.css` | Drawer/modal components |
| `_modals.css` | Share modal, lightbox |
| `_layout.css` | Hero sections, empty states, hero gradients |
| `_guides.css` | Guide page layout, TOC, related guides |
| `_forms.css` | Range sliders, Tom Select dropdowns, compare bar |
| `_accessibility.css` | Focus states, reduced motion, high contrast |
| `_dark-mode.css` | **ALL** `[data-theme="dark"]` overrides (consolidated) |
| `_responsive.css` | Mobile breakpoint overrides |
| `_print.css` | Print styles |

### CSS Workflow

1. Edit partials in `assets/css/partials/`
2. Run `npm run build:css` to regenerate `styles.css`
3. If you added/changed Tailwind classes in PHP or JS, also run `npm run build:tailwind`
4. Commit both partials AND generated files

### Key Rules

**CSS loading:** All pages load CSS exclusively through `components/header.php`. Never add `<link>` tags in individual pages — duplicates cause race conditions. Cache-busting is managed centrally in header.php.

**Colors:** Use CSS custom properties from `_variables.css`, never hardcode hex values. See that file for the full color system (primary/blue, secondary/green, accent/yellow, error, warning, surfaces).

**Z-index scale:** Always use variables: `--z-drawer: 50`, `--z-modal: 60`, `--z-lightbox: 65`, `--z-toast: 100`.

**Dark mode:** All `[data-theme="dark"]` selectors go in `_dark-mode.css`, not inline with components. Exception: theme toggle icon visibility stays in `_filters.css`.

**Responsive:** Mobile overrides go in `_responsive.css`, not inline with components.

**Semantic class names:** Add semantic CSS classes (e.g., `.beach-hero`) for CSS targeting instead of targeting Tailwind utility classes.

### Prose Content Classes

- **`.prose-brand`** — Light text for dark backgrounds (collection pages, beach detail)
- **`.prose-light`** — Dark text for light backgrounds (guide pages)
- **`.beach-description`** — Collection page intro sections (light gray backgrounds)

Always pair prose classes with appropriate backgrounds.

### Hero Gradient Classes (defined in `_layout.css`)

- `.hero-gradient-dark` — Collection pages (dark blue/green)
- `.hero-gradient-guide` — Guide pages (green/secondary)
- `.hero-gradient`, `.hero-gradient-purple` — Legacy, deprecated

### Tailwind Configuration (`tailwind.config.js`)

- Dark mode: selector strategy with `[data-theme="dark"]`
- Content scan: `**/*.php` + `assets/js/**/*.js`
- Custom colors: `brand-dark`, `brand-darker`, `brand-yellow`, `brand-text`, `brand-muted`
- Custom shadows: `shadow-card`, `shadow-glass`, `shadow-sunny`
- Custom fonts: Inter (sans), Playfair Display (serif)
- Animation delay plugin: `delay-100` through `delay-600`

## Security Model

### Session Security (`inc/session.php`)
- HTTPOnly cookies (no JS access)
- Secure flag (HTTPS only)
- SameSite=Lax
- Custom session name: `BEACH_FINDER_SESSION`
- 30-minute inactivity timeout
- Session fingerprinting validates IP + User-Agent match

### CSRF Protection
- Token generated per session: `csrfToken()`
- Hidden field helper: `csrfField()` — include in all POST forms
- Validated server-side: `validateCsrf()` — check before state changes

### Security Headers (`inc/security_headers.php`)
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- Referrer-Policy: strict-origin-when-cross-origin
- CSP allowing: self, Tailwind CDN, HTMX, MapLibre, Umami Analytics

### Rate Limiting (`inc/rate_limiter.php`)
- Applied to magic link and check-in endpoints
- Configurable per identifier (email/IP) + action + time window
- Returns `['allowed' => bool, 'remaining' => int, 'reset_at' => datetime]`

## SEO & Structured Data

### Meta Tags
- Managed in `components/header.php` via `$pageTitle`, `$pageDescription`, `$canonicalUrl` variables
- Open Graph + Twitter Card tags auto-generated
- PWA meta tags included

### JSON-LD Schemas (`components/seo-schemas.php`)
- WebSite schema (homepage)
- Organization schema
- BreadcrumbList (guide pages)
- Place schema (each beach)
- LocalBusiness schema (beaches with reviews)
- Review schema (user reviews)

## Environment Variables

Loaded from `.env` by `loadEnv()` in `inc/db.php`:
- `APP_URL` — Application domain
- `APP_NAME` — Site name
- `DB_PATH` — SQLite database path
- Google OAuth: client_id, client_secret, redirect_uri
- Email service credentials
- Weather API credentials (optional)

## Change Impact Checklist

Use this when making changes to understand what else may need updating:

| If you change... | Also update... |
|-----------------|----------------|
| CSS partial files | Run `npm run build:css`; commit both partials and `styles.css` |
| Tailwind classes in PHP/JS | Run `npm run build:tailwind`; commit `tailwind.min.css` |
| `assets/js/app.js` | Run `npm run build:js`; commit `app.min.js` |
| A CSS class name | Search all `components/*.php` and page files for references |
| A function in `inc/helpers.php` | Search all files that call it (`grep -r "functionName"`) |
| Database schema | Create a new migration in `migrations/`; update `init-db.php` |
| `inc/constants.php` arrays | Update any UI that renders those options (filters, forms) |
| `components/header.php` | Every page is affected — test broadly |
| A component's expected variables | Update all files that include that component |
| Hero gradient classes | Check all collection and guide pages using them |
| Authentication logic | Test both logged-in and logged-out states |

## Troubleshooting

### Error Logs

```bash
tail -50 /var/log/nginx/beach-finder-error.log         # Nginx errors (PHP fatals)
tail -50 /var/log/nginx/puertoricobeachfinder-error.log # Production domain
tail -50 /var/log/php8.3-fpm.log                        # PHP-FPM pool
```

### Common ERROR 500 Causes

1. **Duplicate function declarations** — error log shows "Cannot redeclare". Find both files, remove duplicate, use `require_once`
2. **Missing include files** — check paths, use `__DIR__` for relative includes
3. **Database permissions** — `data/` dir needs `www-data` write access
4. **PHP syntax errors** — run `php -l filename.php` to check
5. **Session issues** — `session.php` must be included BEFORE `session_start()`

### Common Mistakes to Avoid

- Adding `<link>` CSS tags outside `header.php` (causes duplicate loading)
- Using `include` instead of `require_once` (causes function redeclaration)
- Forgetting `h()` on user-supplied output (XSS vulnerability)
- Forgetting CSRF token in POST forms
- Hardcoding hex colors instead of CSS variables
- Putting dark mode overrides inline instead of in `_dark-mode.css`
- Editing `styles.css` directly instead of the partials
- Not running `npm run build` after CSS/Tailwind changes
- Creating new page files without including `header.php` and `footer.php`
- Querying tags/amenities per-beach in a loop instead of using `attachBeachMetadata()`
