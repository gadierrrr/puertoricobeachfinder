# Mobile homepage — bug report

**Tested:** `http://localhost:8000/` on viewport ~515×669 (Chrome browser DevTools couldn't resize below ~515px on this Mac; behavior at 640+ also covers Tailwind `sm` breakpoint, so all observations apply to phones).
**Branch:** `redesign/discovery-ui`
**Date:** 2026-04-25

Numbered by severity. P1 = blocks core flow, P2 = visible/incorrect, P3 = polish.

---

## P1 — Blocking

### 1. Filter dropdowns are invisible when opened
Clicking **Region / Vibe / Amenities / Conditions / Drive from SJU** flips `<details open>` correctly, but the menu content is never visible to the user.

**Cause:** `.discovery-filter-bar__pills` (the row container) has `overflow-x: auto; overflow-y: hidden;` to enable horizontal scroll. The dropdown menu is positioned `absolute` *inside* this clipped parent, so its drop-down content is cut off vertically. Hit-testing the menu's bounding rect (top:218 → bottom:578) returns map markers — confirming the menu is visually below the clip.

**Fix:** move dropdown menus out of the clipped container (e.g., `position: fixed` + computed left, or render menus as portal-style siblings of the filter-bar), or set the menu's parent `<details>` to `position: static` and float the menu in a non-clipped wrapper.

Files: `public/assets/css/partials/_discovery.css` (`.discovery-filter-bar__pills`, `.discovery-dropdown__menu`), `components/discovery/filter-bar.php`.

### 2. Save / Add to day buttons do nothing
`<button class="discovery-action--save" data-action="toggleFavorite" data-action-args="[\"<id>\"]">` renders, but no global handler is registered. `typeof window.toggleFavorite === "undefined"`. Clicking does nothing — no auth modal, no toast, no heart fill.

Same for `data-action="addBeachToItinerary"`, `removeFilter`, `clearFilters`, `setMaxDistance`, `setMobileView`. The discovery action dispatcher is missing or these names aren't wired into it.

Files: `public/assets/js/discovery.js`, `public/assets/js/app.js`. Check that the `data-action` delegator registers all referenced names from `components/discovery/*.php`.

### 3. Hamburger menu does nothing
Clicking the `☰` button at top-left fires (the click registers) but no slide-out / drawer / menu opens. No console error.

Files: `components/nav.php`, `public/assets/js/app.js` (handler for `[aria-label="Open main menu"]`).

### 4. Desktop nav links visible at mobile viewport
`.nav-discovery__links` and `.nav-discovery__search` carry Tailwind utility `hidden md:flex`, but a custom rule in CSS sets:
```
.nav-discovery__links { display: flex; ... }
```
Specificity tie (1 class vs 1 class) → cascade order wins, and the custom partial loads after Tailwind, so `display: flex` wins. Result: at 515px viewport the desktop nav (`Guides Quiz Map Region…`) overflows the right edge.

**Fix:** either remove `display: flex` from the base rule and let `md:flex` apply at 768px+, or add a media query around the custom rule (`@media (min-width: 768px) { .nav-discovery__links { display: flex; } }`).

Files: `public/assets/css/partials/_base.css` or wherever `.nav-discovery__links` is declared.

---

## P2 — Visible / incorrect

### 5. Geolocation toast fires on page load without user opt-in
"Location enabled! Distances are now shown." appears at every load, even before the user touches *Near Me*. Also appears after every form submit (search). Likely the prior session granted geolocation; the toast should at minimum only show once-per-session, or be triggered only by the *Near Me* button.

### 6. Toast obscures the bottom mobile tabbar
`.toast-container` positions at `bottom: ~16px` with `height: 73px`. That puts its top at ~580 and bottom at 653. The tabbar lives at top:600–669, so the toast sits squarely on top of "List / Saved / Chat".

**Fix:** offset toasts above the tabbar (e.g., `bottom: calc(var(--mobile-tabbar-h) + 16px)`).

Files: `public/assets/css/partials/_loading.css` (`.toast-container`).

### 7. Map LEGEND card hidden behind tabbar
The inline legend (`.discovery-map__legend-inline`) is positioned at the bottom-left of the map and extends below the tabbar's top edge. Visible "LEGEND beach" peeks out under the tabbar.

**Fix:** add `bottom: calc(var(--mobile-tabbar-h) + 12px)` on mobile, or hide the legend entirely on mobile in favor of an info button.

### 8. Filter-bar doesn't actually stick on scroll
`.discovery-filter-bar` has `position: sticky; top: 0; z-index: 30;` but when the user scrolls in **List** view, the bar moves off-screen at `top: -235` instead of pinning. The page scrolls on `<html>` (3538px tall, scrollY clamped < 1000 in some interactions). Sticky breaks because of layout/wrapper interaction in `discovery-split-v2`. Also: the fixed nav header (`top:0, h:52, z:40`) sits above z:30, so even if it stuck it would hide behind the header. The sticky `top` should be `52px`, not `0`.

### 9. Empty/0×0 "Ask the assistant" FAB rendered on mobile
`.discovery-assistant-fab` has zero dimensions on mobile (`getBoundingClientRect` → all 0). It's still in the DOM with text "Ask the assistant", taking accessibility focus. Either remove from DOM on mobile or actually display it. The bottom tabbar's **Chat** tab seems to be the mobile equivalent — if intentional, drop the FAB on mobile via `display: none`.

### 10. Search submit unconditionally resets view to Map
After typing in the search field on **List** view and pressing Enter, the URL becomes `?view=map&q=flam` and the layout switches back to the map. Users on the list deserve to stay on the list when refining their query.

### 11. No live search / autocomplete on mobile
The mobile search pill in `discovery-mobile-search` accepts input and submits on Enter, but provides no suggestions / live-filter. The legacy hero search had autocomplete (`#search-autocomplete`); the discovery shell doesn't wire it up.

### 12. Map markers heavily overlap, no clustering
At default zoom (whole-island fit), ~448 markers stack into a coastal blob. No clusterer is applied. Consider Supercluster or maplibre-gl's built-in cluster source.

---

## P3 — Polish

### 13. "Drive from: SJU 30min" pill clipped at right edge
The 5th filter pill is wider than the remaining horizontal space at 515px; only "Drive f…" is visible until the user scrolls horizontally. Either shorten the label ("≤30 min from SJU") or signal scrollability with a fade.

### 14. Filter-bar cosmetic: dropdown menus exist in DOM with `display: grid` even when closed
`.discovery-dropdown__menu` is rendered with `display:grid; opacity:1; visibility:visible; pointer-events:auto;` regardless of `<details>` open state. They aren't visible only because the parent clips them (see #1). For correctness/perf:
```css
details:not([open]) > .discovery-dropdown__menu { display: none; }
```

### 15. Header overlaps the mobile-top section during scroll transition
The mobile top (search pill + Near Me/Map/Quiz chips) is not sticky and scrolls under the fixed header. Mid-scroll the chips appear half-occluded. Either make the mobile-top sticky too (clean handoff) or pull it into the fixed header on mobile.

### 16. Basemap tiles don't render until first interaction
On initial load, the map shows markers over a blank gray canvas. Pan/zoom causes tiles to load. Likely a fit-bounds-before-style-ready issue. Trigger an explicit `map.resize()` after style load or wait for `style.load` before fitting bounds.

### 17. SES warning in console
`SES Removing unpermitted intrinsics` from MetaMask's `lockdown-install.js`. Not our bug — extension noise. Worth filtering in test harnesses but not a real issue.

---

## What I tested

- Loaded `/` on 515px viewport.
- Clicked: hamburger, all 5 filter pills, Region, Save (heart), List/Map tabbar tabs, Quiz chip.
- Typed "flam" in main search, submitted with Enter.
- Inspected scroll behavior, sticky positions, overflow chains.
- Read console (only SES extension noise).
- Inspected DOM for unwired `data-action` handlers.

## What I did NOT exhaustively test

- Authenticated flows (Save → /login redirect chain, favorites persistence).
- Geolocation grant/deny dialog (browser had granted state cached).
- Beach drawer (`#beach-drawer`) — the drawer sits at the bottom of `index.php` but I didn't tap a marker.
- Quiz, Saved, Chat tab landings.
- Spanish locale (`/es`).
- Deep links with prefilled filters (`/?tags[]=surfing`).
