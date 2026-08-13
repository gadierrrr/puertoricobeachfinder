# Redesign v2 — Handoff / Task Brief

## Goal
Puerto Rico Beach Finder is getting a new visual language ("redesign v2": tropical/DTMF
palette, Alfa Slab One display type, a hydrographic island chart, a Nomad-List-style ranked
beach directory, derived "Beach Score"). **Two pages are done (homepage + beach detail).
The task is to (a) apply the redesign to EVERY page and (b) make every interaction fully
functional (no stubs).** This doc is the audit + architecture + how-to so you can finish it.

## Stack & where things are
- **Repo:** `/Users/gadiel/Desktop/puertoricobeachfinder` (PHP 8.3, raw PHP includes, no framework).
- **DB:** single SQLite file `data/beach-finder.db` (WAL). ~434 published beaches. Data access via
  `query()` / `queryOne()` (`inc/db.php`) + helpers in `inc/helpers.php`.
- **CSS:** Tailwind (`tailwind.config.js` → `public/assets/css/tailwind.min.css`) for the CLASSIC
  site; the redesign uses a **separate hand-authored** `public/assets/css/redesign.css`.
- **Branch:** `feat/redesign-v2` (pushed to GitHub `gadierrrr/puertoricobeachfinder`). Prod is on
  `main` and is **untouched / still classic**.
- **Local preview:** `php -S localhost:8082 -t public scripts/dev-router.php`, then append
  `?design=redesign` to any URL to force the redesign without changing env.
- **Deploy:** manual SSH. Server `root@159.198.43.250` (ssh alias `beach-prod`).
  `git pull && ./deploy.sh` in the checkout. `deploy.sh` = php-lint → `npm ci` → `npm run build`
  → **`git diff --exit-code` on committed built assets** (`tailwind.min.css`, `app.min.js`,
  `collection-explorer.min.js`, `chat.min.js`) → `php scripts/migrate.php` → smoke.
- **Live staging:** **https://staging.159.198.43.250.nip.io** — a second checkout at
  `/var/www/beach-finder-staging` on branch `feat/redesign-v2`, its own SQLite copy, its own
  `.env` (`APP_ENV=staging`, `HOMEPAGE_DESIGN=redesign`, `APP_DEBUG=1`), Let's Encrypt TLS,
  `X-Robots-Tag: noindex`. nginx vhost `/etc/nginx/sites-available/staging-beach-finder.conf`
  (this box also hosts 2 other prod sites — always `nginx -t` before reload).

## How the redesign is wired (the core mechanism)
- **Flag:** `useRedesign()` in `inc/env.php` (~L167) → true if `?design=redesign`, false if
  `?design=classic`, else `HOMEPAGE_DESIGN` env == `redesign`.
- **Per-page opt-in:** a page sets `$redesignLayout = useRedesign();` BEFORE including
  `components/header.php`, then (if true) includes its redesign template + footer and `return`s.
  **Only `public/index.php` (~L209) and `public/beach.php` (~L226) do this today.**
- **Chrome gating:** `components/header.php` (~L322-340) loads `redesign.css` + fonts and adds a
  `redesign` body class, and **skips the classic `components/nav.php` when `$redesignLayout` is
  set**. `components/footer.php` (~L20) skips only the visual `<footer>` for redesign — **the JS
  bundle, modals, toasts, and helper globals still load** (see "already available" below).
- **Redesign templates render their OWN inline topbar + footer** (currently duplicated in both
  templates — this is a problem to fix; see Phase 1).

## Files created for the redesign (the current surface)
- `templates/redesign/home.php` — homepage (hero band + island chart + directory).
- `templates/redesign/beach.php` — beach detail.
- `public/assets/css/redesign.css` — all redesign styling, scoped under `.rd` / `.rd-home` /
  `.rd-beach` (page-modifier classes prevent collisions; relies on Tailwind preflight for reset).
- `public/assets/js/redesign-home.js` — the directory (filter/sort/search/coast-filter/favorites).
- `inc/beach_score.php` — `computeBeachScore($beach,$tags,$amenities)` → overall + sub-scores +
  `bsColor()`. **Beach Score is DERIVED** (DB has no score fields).
- `inc/island_chart.php` — `projectToIslandChart($lat,$lng)`, `renderIslandLocator($lat,$lng)`,
  `islandRegionForMunicipality($muni)` (+ baked SVG geometry + municipality→coast map).
- `inc/env.php` — added `HOMEPAGE_DESIGN` schema key + `useRedesign()`.

## IMPORTANT: in-progress state of `redesign-home.js`
`redesign-home.js` was recently updated and now **expects a `window.RD_CFG` object and
server-rendered first-page tiles that `templates/redesign/home.php` does NOT yet provide.**
Before anything else, finish this contract:
- The template must emit `window.RD_CFG = { authed:<bool>, csrf:"<token>", favs:[<beachId strings>],
  urlPrefix:"/beach/" or "/es/playa/", regionNames:{...}, i18n:{ overall, save, viewBeach, beaches,
  noMatch, findYourBeach, byCoast, wholeIsland, manyMunicipios, barLabels:{...}, water:{...},
  crowd:{...} } }`.
- Each entry in `window.RD_BEACHES` must now include **`id`** (for favorites) and **`t`** (tags
  array, for chip filtering) — the current template does NOT add these. Add them in the
  `$rd[]` build loop in `home.php`.
- The **first page of tiles must be server-rendered** by a PHP helper (`$rdTile()`) whose markup
  **exactly mirrors** the JS `tile()` in `redesign-home.js` (including the `.bt-fav` button with
  `data-id` and `on` state) — the JS re-renders on state change, but SSR is needed for crawlability.
- The favorite flow is already implemented in JS (`toggleFavorite()` → POST
  `/api/toggle-favorite.php?format=json` with `beach_id`+`csrf_token`, auth-gated via
  `CFG.authed` → falls back to `showSignupPrompt('favorites')`, seeds from `CFG.favs`). The
  template just needs to supply `RD_CFG` and print `csrfToken()`.
- Verify the hero **condition chips** are wired to `st.tags` (JS `list()` already filters by tags);
  chips need `data-tag` + a click listener that pushes/removes from `st.tags` then `draw()`.

---

# AUDIT — what's done vs. what remains

## Coverage: only 2 of ~60+ page types are redesigned
Everything else still renders the classic Tailwind layout. Page inventory (all include
`components/header.php`+`footer.php`, directly or via `components/page-shell.php`, unless noted):

- **Done:** `index.php` (`/`), `beach.php` (`/beach/{slug}`).
- **Collection/grid pages (~26)** — most share `components/collection/explorer.php` (grid + filters
  + a **map view mode**) whose cards are rendered **client-side** by `collection-explorer.min.js`:
  `best-beaches.php` + ~24 variants (`best-{activity|municipality}-beaches.php`,
  `hidden-beaches-puerto-rico.php`, `beaches-near-san-juan{,-airport}.php`). Two grid pages use the
  PHP `components/beach-card.php` instead: `municipality.php` (`/beaches-in-{muni}`),
  `beaches-by-tag.php` (`/beaches/{tag}`).
- **Guides (~14)** — `guides/index.php` + 13 article files, all via `components/page-shell.php`
  (start/end); `guides/cms-router.php` is a router (no UI).
- **Auth / flow** — `quiz.php`, `quiz-results.php`, `compare.php`, `profile.php`, `login.php`
  (uses `components/footer-minimal.php`), `onboarding.php`, `verify.php`, `favorites.php`,
  `list.php`, `lists.php`.
- **Legal / utility** — `terms.php`, `privacy.php` (via page-shell), `unsubscribe.php`.
- **Self-contained (NOT header/footer — hand-rolled full HTML)** — `errors/404.php`,
  `errors/500.php`, `offline.php`. These need their own redesign styling inline.
- **No UI (skip):** `logout.php`, `go.php`, `sitemap.php`, `feed.xml.php`, everything under
  `public/api/` and `public/admin/`.

## Functional stubs on the two "done" pages
- **Beach page (`templates/redesign/beach.php`):** hero + dockbar **Save/Share** are dead
  `<a href="#">`; **Check in** button has no handler; **Sign in** is a `<span>`, not a link;
  nav "Map" points to `/#beaches`. (Working: Directions, Chart/Satellite toggle, scroll-spy.)
- **Homepage:** favorites now wired in JS (needs template `RD_CFG` — see above); **chips**,
  **Filters** button, and **Grid|Map** toggle (no map view exists) are stubs; **EN·ES** is static
  text (no language switch).
- **Shared:** topbar/footer are **duplicated inline** in both templates (drift already exists).
- **a11y:** `redesign.css` has responsive breakpoints but **no `:focus-visible`** and **no
  `prefers-reduced-motion`** (there are `transform:scale` hovers + smooth-scroll).
- **i18n:** partial — many hardcoded English strings in templates and (previously) JS. The JS now
  supports an `I`/`CFG.i18n` map; the template must supply it and translate remaining strings.
- **Analytics:** redesign emits **no** `data-bf-track` attributes and calls `bfTrack` nowhere
  (GA/PostHog pageviews still fire from the header, but funnel events don't).

---

# Recommended approach (highest leverage first)

**Phase 1 — Shared chrome (biggest win).** Build a **redesign nav partial** + **redesign footer
partial**; render them from `components/header.php` / `components/footer.php` when `useRedesign()`
(replace the current "skip nav/footer" behavior). Make `$redesignLayout` apply site-wide (set it in
`header.php` from `useRedesign()`, and in `components/page-shell.php` for the guides/legal path)
so **every page gets the new chrome + typography the moment the flag is on**. The new nav must
replicate `components/nav.php`: brand, primary links via `routeUrl()`, **auth-aware** right side
(`currentUser()` → sign-in vs favorites/profile/logout), and the **working EN/ES switcher**
(`getLocalizedUrlForCurrentRequest('en'/'es')` + `setLanguage()` → `/api/set-language.php`). Remove
the inline topbars/footers from the two templates and use the shared partials.

**Phase 2 — Shared redesigned beach-card.** Create a redesign variant of
`components/beach-card.php` matching the `.btile` look with a **persistent** favorite button
(reuse the classic auth-gated pattern: logged-in → POST `/api/toggle-favorite.php`; logged-out →
`showSignupPrompt('favorites')`; print `csrfToken()`; seed `$isFavorite` from a `$userFavorites`
lookup). Because `municipality.php`, `favorites.php`, `profile.php`, `index.php`, `list.php`, and
`components/beach-grid.php` all include this one file, redesigning it ports all of them at once.

**Phase 3 — Collection pages (~26).** Restyle `components/collection/explorer.php` (+
`collection/hero.php`, `toolbar.php`, `results.php`) and the **JS card template inside
`public/assets/js/collection-explorer.js`** (then rebuild → `collection-explorer.min.js`), plus the
**map view mode**. This one component powers all `best-*` / `beaches-near-*` / `hidden-*` pages.

**Phase 4 — Bespoke surfaces (one pass each):** `quiz.php` + `quiz-results.php` (flow + result
rows), `compare.php` (comparison table), the guide articles (page-shell content), `login.php` /
`profile.php` / `onboarding.php` (forms/dashboards), legal (`terms`/`privacy`/`unsubscribe`), and
the self-contained `errors/404.php`, `errors/500.php`, `offline.php`.

**Cross-cutting (do alongside every phase):**
- **Favorites/auth/analytics/i18n/a11y** wiring per "Functional stubs" above.
- Add `:focus-visible` + `@media (prefers-reduced-motion)` to `redesign.css`.
- Add `data-bf-track` + `data-bf-*` attributes (directions/details/share) and `bfTrack(...)` calls.
- Route all strings through `__()` / `$isEs` and localized `routeUrl()`.

---

# Key integration points (reuse these — they already load on redesign pages)
- **Favorites:** `POST /api/toggle-favorite.php` (add `?format=json` → `{success,is_favorite,...}`),
  per-user `user_favorites` table, CSRF via `csrfToken()`/`validateCsrf()`. Classic reference:
  `components/beach-card.php` (~L103-121). Logged-out → `showSignupPrompt('favorites')` (defined in
  `components/footer.php`).
- **Auth in nav:** `currentUser()` / `isAuthenticated()`; client-side `window.BeachFinderMeta`
  (`authenticated`, `user_id`) is emitted by `footer.php`.
- **Language:** `getCurrentLanguage()`, `routeUrl($key,$locale,$params)` and the EN↔ES route map in
  `inc/locale_routes.php`; `getLocalizedUrlForCurrentRequest($locale)` + `POST /api/set-language.php`
  for the switcher. i18n strings via `__('dot.key')` from `inc/lang/{en,es}.php`.
- **Analytics:** `window.bfTrack(event, props)` (`public/assets/js/analytics.js`); data-attribute
  events via `data-bf-track` + `data-bf-beach-id/-slug/-municipality/-source` (directions→
  `A3_directions_click`, details→`A1_list_to_detail_click`, share→`share_click`, etc.).
- **Available globals on redesign pages already:** `showSignupPrompt`, `showToast`, `bfTrack`,
  `openReviewForm(beachId,name)` (for "Check in"/reviews), Lucide icons, `window.BeachFinderMeta`.
- **Data helpers:** `attachBeachMetadata(&$beaches)` (adds `tags`/`amenities`),
  `getNearbyBeaches($id,$lat,$lng,$limit)`, `generateBeachFAQs($beach)`,
  `getWeatherForLocation($lat,$lng)` (`inc/weather.php`), `getTagLabel()`, `getBeachImageUrl()`.

# Gotchas (these bit us / will bite you)
- **deploy.sh built-asset gate:** a fresh `npm run build` must byte-match the committed built
  assets. Keep the redesign in `redesign.css` (not Tailwind) and **do not introduce Tailwind
  utility classes** in redesign templates that aren't already generated. If you must change Tailwind
  inputs or the JS bundles, rebuild **on the deploy server** and commit that output (local builds may
  differ). `redesign-home.js`/`redesign.css` are NOT part of the build (served static) — safe to edit.
- **Strict error handler swallows errors:** undefined variables/array keys become `ErrorException`
  → 500, and the 500 page then fails on "headers already sent", hiding the real cause. **Read
  `data/logs/app.log`** for the true error. (`index.php` does NOT define `$lang`; templates must set
  `$lang = $lang ?? getCurrentLanguage();`. Use `?? ''` on any possibly-missing array key.)
- **CSP:** inline `<script>` needs `<?= cspNonceAttr() ?>`. The beach page's **Satellite** view
  injects a `maps.google.com` iframe — **verify CSP allows it**; `inc/security_headers.php` had no
  `frame-src`, so you likely need to add `frame-src https://www.google.com https://maps.google.com`
  (or switch that toggle to the site's existing MapLibre map, which is already CSP-allowed).
- **Beach Score is derived** (`inc/beach_score.php`): tune, don't trust as ground truth. Field
  reality: `swim_difficulty` is a dead constant `3`; `surf` ∈ {calm,small,medium,large};
  `sargassum` ∈ {none,light,moderate,high}. The municipality→coast map is approximate.
- **`redesign.css` scoping:** everything under `.rd` + `.rd-home`/`.rd-beach`. Keep new pages using
  page-modifier scoping to avoid clobbering classic styles that still load.

# Deploy / verify loop
1. Edit → commit → push `feat/redesign-v2`.
2. On staging: `ssh beach-prod`, `cd /var/www/beach-finder-staging && git pull && ./deploy.sh`.
3. Verify at `https://staging.159.198.43.250.nip.io` (flag is on there) and locally via
   `?design=redesign`. Check `data/logs/app.log` and browser DevTools console for errors.
4. Confirm prod (`https://www.puertoricobeachfinder.com`) still serves classic.
5. **Go-live (later):** merge `feat/redesign-v2` → `main` (ships dormant behind the flag), then set
   `HOMEPAGE_DESIGN=redesign` in `/var/www/beach-finder/.env` + reload PHP-FPM. Rollback = flip flag.

# Open decisions (confirm with the product owner)
1. **Sequencing:** phased-by-leverage (recommended: Phase 1→4 above, reviewable on staging) vs.
   one big pass vs. high-visibility pages first (home/beach/collections/municipality/guides/quiz;
   defer account/legal/error).
2. **Functional bar for this pass** (all are the eventual target): favorites+auth-nav, full Spanish
   i18n, analytics events, homepage Map view + search autocomplete-to-detail. Confirm which are
   must-have now vs. acceptable-as-stub temporarily.
