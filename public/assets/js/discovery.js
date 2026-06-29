/**
 * ⚠️ INACTIVE / WIP — Discovery UI redesign, not wired into any live page.
 *    Action handlers (toggleFavorite, addBeachToItinerary, removeFilter, …) are
 *    unwired. Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Discovery split-view enhancer
 *
 * The minified app.js ships its own setViewMode() that flips between
 * #list-view and #map-view. We wrap it to add a third "split" mode
 * which reveals #split-view and applies a compact class to #beach-grid.
 *
 * Responsibilities handled here (and only here):
 *   - Show/hide #split-view / #list-view / #map-view.
 *   - Toggle the .beach-grid--split compact modifier.
 *   - Keep aria-pressed in sync on all three toggle buttons.
 *   - Mirror the mode in window.BeachFinder.viewMode so URL sync
 *     performed by app.js's buildQueryParams() picks it up.
 *   - Fire a bfTrack analytics event on manual toggles.
 */
(function () {
    'use strict';

    var SPLIT = 'split';
    var originalSetViewMode = window.setViewMode;

    function applyCompactGrid(isSplit) {
        var grid = document.getElementById('beach-grid');
        if (grid) grid.classList.toggle('beach-grid--split', !!isSplit);
    }

    function updateAriaPressed(mode) {
        var map = {
            list: document.getElementById('view-list-btn'),
            map: document.getElementById('view-map-btn'),
            split: document.getElementById('view-split-btn')
        };
        Object.keys(map).forEach(function (k) {
            var btn = map[k];
            if (!btn) return;
            btn.setAttribute('aria-pressed', k === mode ? 'true' : 'false');
            if (k === mode) {
                btn.classList.remove('bg-warm-50', 'text-warm-700', 'hover:bg-warm-100');
                btn.classList.add('bg-sunset-400', 'text-ocean-900');
            } else {
                btn.classList.remove('bg-sunset-400', 'text-ocean-900');
                btn.classList.add('bg-warm-50', 'text-warm-700', 'hover:bg-warm-100');
            }
        });
    }

    function writeViewParam(mode) {
        try {
            var params = new URLSearchParams(window.location.search);
            if (mode === 'list') {
                params.delete('view');
            } else {
                params.set('view', mode);
            }
            var qs = params.toString();
            history.replaceState(null, '', qs ? '?' + qs : window.location.pathname);
        } catch (e) { /* noop */ }
    }

    function track(mode) {
        if (typeof window.bfTrack === 'function') {
            window.bfTrack('view_mode_toggle', { mode: mode, source: 'homepage' });
        }
    }

    function showSplit() {
        var split = document.getElementById('split-view');
        var list = document.getElementById('list-view');
        var map = document.getElementById('map-view');
        if (split) split.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (map) map.classList.add('hidden');
        applyCompactGrid(true);
        updateAriaPressed(SPLIT);
        if (window.BeachFinder) window.BeachFinder.viewMode = SPLIT;
        // Keep the mode in URL even when the user doesn't touch a filter next.
        writeViewParam(SPLIT);

        if (typeof window.initializeMap === 'function' && !window._bfSplitMapInit) {
            window.initializeMap();
            window._bfSplitMapInit = true;
        } else if (typeof window.updateMapMarkers === 'function') {
            window.updateMapMarkers();
        }
    }

    function hideSplit() {
        var split = document.getElementById('split-view');
        if (split) split.classList.add('hidden');
        applyCompactGrid(false);
    }

    window.setViewMode = function (mode) {
        if (mode === SPLIT) {
            showSplit();
            track(SPLIT);
            return;
        }
        hideSplit();
        if (window.BeachFinder) window.BeachFinder.viewMode = mode;
        if (typeof originalSetViewMode === 'function') {
            originalSetViewMode(mode);
        }
        // Re-sync aria on list/map after the original function stamped classes.
        updateAriaPressed(mode);
        track(mode);
    };

    // ----- Category color on map markers -----
    // After addBeachMarkers places .beach-marker nodes into the map, look up
    // each one's primary tag from window.BeachFinder.beaches and stamp a
    // modifier class. The CSS pin-dot--* rules render the color.
    var CATEGORY_ORDER = ['family-friendly', 'surfing', 'snorkeling', 'secluded'];
    var CATEGORY_CLASS = {
        'family-friendly': 'beach-marker--family',
        'surfing':         'beach-marker--surf',
        'snorkeling':      'beach-marker--snorkel',
        'secluded':        'beach-marker--secluded'
    };
    function classifyMarkers() {
        var beaches = (window.BeachFinder && window.BeachFinder.beaches) || [];
        if (!beaches.length) return;
        var byId = {};
        beaches.forEach(function (b) { byId[b.id] = b; });
        document.querySelectorAll('.beach-marker').forEach(function (el) {
            var beach = byId[el.dataset.beachId];
            if (!beach) return;
            var tags = beach.tags || [];
            for (var i = 0; i < CATEGORY_ORDER.length; i++) {
                var cat = CATEGORY_ORDER[i];
                if (tags.indexOf(cat) !== -1) {
                    el.classList.add(CATEGORY_CLASS[cat]);
                    break;
                }
            }
        });
    }

    // Re-classify whenever markers are (re)added. MapLibre doesn't emit an
    // event, so we poll briefly after an initializeMap/updateMapMarkers call.
    function scheduleClassify() {
        [200, 600, 1500, 3000].forEach(function (d) {
            setTimeout(function () {
                classifyMarkers();
                if (typeof recomputeClusters === 'function') recomputeClusters();
            }, d);
        });
    }

    function installMapFnWrappers() {
        ['initializeMap', 'updateMapMarkers', 'addBeachMarkers'].forEach(function (name) {
            var orig = window[name];
            if (typeof orig !== 'function' || orig.__bfWrapped) return;
            var wrapped = function () {
                var r = orig.apply(this, arguments);
                scheduleClassify();
                return r;
            };
            wrapped.__bfWrapped = true;
            window[name] = wrapped;
        });
    }

    // PR archipelago bounds (mainland + Culebra + Vieques).
    // Used to force a fit after markers are placed so offshore pins
    // are always visible, not just mainland.
    var PR_BOUNDS_SW = [-67.45, 17.85];
    var PR_BOUNDS_NE = [-65.15, 18.65];

    // Monkey-patch fitMapToMarkers so every invocation uses a wider
    // padding and an explicit PR archipelago-safe maxZoom — ensures
    // Culebra + Vieques pins land inside the viewport.
    (function patchFit() {
        if (typeof window.fitMapToMarkers !== 'function' || window.fitMapToMarkers.__bfPatched) return;
        var orig = window.fitMapToMarkers;
        var patched = function () {
            var r = orig.apply(this, arguments);
            // Follow up with a forced PR-archipelago fit via a dummy
            // marker access — the original already touched state.map,
            // so any subsequent state.map.fitBounds call works. We
            // re-extend bounds by calling the map via a freshly added
            // & immediately-removed marker to obtain the map ref.
            setTimeout(refitViaMarker, 150);
            return r;
        };
        patched.__bfPatched = true;
        window.fitMapToMarkers = patched;
    })();

    function refitViaMarker() {
        if (typeof maplibregl === 'undefined') return;
        // Create a throwaway marker to obtain the map ref, then fit.
        try {
            var tmp = new maplibregl.Marker({ element: document.createElement('div') });
            // addTo requires a map, which is what we don't have. Use the
            // stored BF_MAP_REF if the patched constructor captured it.
            var map = window.BF_MAP_REF && window.BF_MAP_REF.instance;
            if (!map) return;
            map.fitBounds([PR_BOUNDS_SW, PR_BOUNDS_NE], {
                padding: { top: 30, bottom: 30, left: 30, right: 30 },
                duration: 600,
                maxZoom: 10
            });
        } catch (e) { /* noop */ }
    }

    function refitToPR() {
        var map = window.BF_MAP_REF && window.BF_MAP_REF.instance;
        if (!map) {
            // Fallback: call map.js's fit-to-markers (works even if we
            // haven't captured the map ref), which will trigger our
            // patched version and follow up with PR-archipelago bounds.
            if (typeof window.fitMapToMarkers === 'function') {
                try { window.fitMapToMarkers(); } catch (e) {}
            }
            return;
        }
        try {
            map.fitBounds([PR_BOUNDS_SW, PR_BOUNDS_NE], {
                padding: { top: 30, bottom: 30, left: 30, right: 30 },
                duration: 600,
                maxZoom: 10
            });
        } catch (e) { /* noop */ }
    }

    // Capture the MapLibre Map instance at construction time so we can
    // drive fitBounds from outside app.min.js (which keeps the map in
    // a module-level closure).
    (function captureMap() {
        function tryPatch() {
            if (typeof maplibregl === 'undefined' || !maplibregl.Map || maplibregl.Map.__bfPatched) {
                return typeof maplibregl !== 'undefined' && maplibregl.Map && maplibregl.Map.__bfPatched;
            }
            var OrigMap = maplibregl.Map;
            function PatchedMap(opts) {
                var inst = Reflect.construct(OrigMap, [opts], PatchedMap);
                window.BF_MAP_REF = { instance: inst };
                return inst;
            }
            PatchedMap.prototype = OrigMap.prototype;
            Object.setPrototypeOf(PatchedMap, OrigMap);
            PatchedMap.__bfPatched = true;
            maplibregl.Map = PatchedMap;
            return true;
        }
        if (!tryPatch()) {
            var tries = 0;
            var t = setInterval(function () {
                tries++;
                if (tryPatch() || tries > 50) clearInterval(t);
            }, 100);
        }
    })();

    // Boot. The new discovery shell is always map-first, so whenever the
    // #map-container is on the page (any viewMode), ensure the map is
    // initialised, beaches are loaded, and markers are placed + classified.
    function bootDiscoveryMap() {
        if (!document.getElementById('map-container')) return;
        if (typeof window.initializeMap === 'function') {
            window.initializeMap();
        }
        // Force a resize once the map's canvas has been sized to its
        // container — fixes the "blank basemap until interaction" flicker
        // when the map container measures during a mid-layout reflow.
        [200, 600, 1200].forEach(function (d) {
            setTimeout(function () {
                var m = window.BF_MAP_REF && window.BF_MAP_REF.instance;
                if (m && typeof m.resize === 'function') { try { m.resize(); } catch (e) {} }
            }, d);
        });
        // Wire clustering once the map ref is captured.
        [400, 1200, 2400].forEach(function (d) {
            setTimeout(attachClusterEvents, d);
        });
        if (window.BeachFinder && typeof window.BeachFinder.loadBeaches === 'function') {
            window.BeachFinder.loadBeaches().then(function () {
                if (typeof window.addBeachMarkers === 'function') {
                    try { window.addBeachMarkers(); } catch (e) {}
                }
                var loading = document.querySelector('.map-loading');
                if (loading) loading.remove();
                scheduleClassify();
                // Fit bounds to cover mainland PR + Culebra + Vieques.
                // Schedule at multiple delays because the map style/source
                // may not be fully loaded immediately after marker add.
                [300, 900, 2000].forEach(function (d) { setTimeout(refitToPR, d); });
            });
        }
    }

    // ----- Mobile view toggle (≤767px list ↔ map) -----
    // Persisted in localStorage and mirrored to ?view=. Stacks the split
    // into full-screen panes via body.mobile-view-{list,map} (CSS hides the
    // opposite pane). The MapLibre map is NOT re-initialised — only visibility
    // toggles, so the same marker set is shared between the two panes.
    var MOBILE_VIEW_KEY = 'bf.mobileView';
    function isMobileViewport() { return window.matchMedia('(max-width: 767px)').matches; }
    function readMobileView() {
        var fromUrl = new URLSearchParams(window.location.search).get('view');
        if (fromUrl === 'map' || fromUrl === 'list') return fromUrl;
        try {
            var stored = localStorage.getItem(MOBILE_VIEW_KEY);
            if (stored === 'map' || stored === 'list') return stored;
        } catch (e) { /* noop */ }
        return 'list';
    }
    function applyMobileView(mode) {
        var body = document.body;
        if (!body) return;
        body.classList.toggle('mobile-view-map', mode === 'map');
        body.classList.toggle('mobile-view-list', mode !== 'map');
        document.querySelectorAll('.mobile-tabbar__tab[data-tab-id]').forEach(function (el) {
            var id = el.getAttribute('data-tab-id');
            var on = (mode === 'map' && id === 'map') || (mode !== 'map' && id === 'list');
            if (id === 'map' || id === 'list') {
                el.classList.toggle('is-active', on);
                el.setAttribute('aria-current', on ? 'page' : 'false');
            }
        });
        try { localStorage.setItem(MOBILE_VIEW_KEY, mode); } catch (e) { /* noop */ }
        try {
            var params = new URLSearchParams(window.location.search);
            if (mode === 'list') { params.delete('view'); } else { params.set('view', mode); }
            var qs = params.toString();
            history.replaceState(null, '', qs ? '?' + qs : window.location.pathname);
        } catch (e) { /* noop */ }
        // Nudge MapLibre after the map pane becomes visible so its canvas
        // resizes to the newly measured container (no re-init, no re-markers).
        if (mode === 'map' && window.BF_MAP_REF && window.BF_MAP_REF.instance) {
            setTimeout(function () {
                try { window.BF_MAP_REF.instance.resize(); refitToPR(); } catch (e) {}
            }, 60);
        }
    }
    window.setMobileView = function (mode) {
        if (mode !== 'map' && mode !== 'list') mode = 'list';
        applyMobileView(mode);
        if (typeof window.bfTrack === 'function') {
            window.bfTrack('mobile_view_toggle', { mode: mode });
        }
    };

    // ----- Suppress the "Location enabled" toast on cached page loads -----
    // app.js's DOMContentLoaded handler restores userLocation from localStorage
    // and calls onLocationGranted(), which fires a toast every time. We only
    // want that toast on a fresh permission grant — not on every reload. Since
    // we can't intercept the call (it's a local function reference inside the
    // minified app.js IIFE), wrap showToast to drop the location-toast for the
    // first ~600ms after page load if the cached-restore path triggered it.
    (function () {
        if (!('localStorage' in window)) return;
        var hadCached = false;
        try { hadCached = !!localStorage.getItem('userLocation'); } catch (e) {}
        if (!hadCached) return;
        var origShowToast = window.showToast;
        var bootCutoff = Date.now() + 800;
        window.showToast = function (msg, type, dur) {
            if (Date.now() < bootCutoff && /location enabled/i.test(String(msg || ''))) {
                return null;
            }
            return origShowToast ? origShowToast.apply(this, arguments) : null;
        };
        // Restore the original after the boot window closes.
        setTimeout(function () { if (origShowToast) window.showToast = origShowToast; }, 900);
    })();

    // ----- Action handlers referenced by data-action= in the discovery shell -----
    // The CSP delegator (csp-bindings.js) calls window.<name>(...args) when a
    // matching [data-action] element fires. Define handlers that are missing
    // from app.js / collection-explorer.js (which only load on collection pages).

    // Save / favorite. Discovery cards are anonymous-friendly: a click bumps
    // unauthenticated users to /login with a return-to. Authenticated users
    // toggle via the existing toggle-favorite endpoint.
    if (typeof window.toggleFavorite !== 'function') {
        window.toggleFavorite = function (beachId) {
            var auth = !!(window.BeachFinder && window.BeachFinder.isAuthenticated);
            if (!auth) {
                if (typeof window.showSignupPrompt === 'function') {
                    return window.showSignupPrompt('favorites');
                }
                if (typeof window.showToast === 'function') {
                    window.showToast('Sign in to save favorites', 'info', 2400);
                }
                var ret = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = '/login?return_to=' + ret;
                return;
            }
            var token = (window.BeachFinder && window.BeachFinder.csrfToken) || '';
            fetch('/api/toggle-favorite.php?format=json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ beach_id: beachId, csrf_token: token }).toString()
            }).then(function (r) { return r.json(); }).then(function (data) {
                var isFav = data && data.is_favorite === true;
                document.querySelectorAll('[data-action="toggleFavorite"][data-action-args*="' + beachId + '"]').forEach(function (btn) {
                    btn.classList.toggle('is-favorite', isFav);
                    btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
                });
                if (typeof window.showToast === 'function') {
                    window.showToast(isFav ? 'Saved' : 'Removed', 'success', 1600);
                }
            }).catch(function () {
                if (typeof window.showToast === 'function') {
                    window.showToast('Could not update favorites', 'error', 2400);
                }
            });
        };
    }

    // Add to day plan — placeholder until the itinerary feature ships.
    if (typeof window.addBeachToItinerary !== 'function') {
        window.addBeachToItinerary = function (beachId, beachName) {
            try {
                var KEY = 'bf.itinerary';
                var list = JSON.parse(localStorage.getItem(KEY) || '[]');
                if (!list.some(function (b) { return b.id === beachId; })) {
                    list.push({ id: beachId, name: beachName || '', addedAt: Date.now() });
                    localStorage.setItem(KEY, JSON.stringify(list));
                }
                if (typeof window.showToast === 'function') {
                    window.showToast('Added to your day · ' + (list.length) + ' beach' + (list.length !== 1 ? 'es' : ''), 'success', 2200);
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Could not save to itinerary', 'error', 2200);
                }
            }
        };
    }

    // ----- Mobile search autocomplete -----
    // Wires the discovery-mobile-search input to a dropdown of matching beaches.
    // Uses BeachFinder.loadBeaches() to fetch the full set lazily, then performs
    // an in-memory substring match on name + municipality. Clicking a result
    // navigates to the beach detail page.
    function initMobileAutocomplete() {
        var form = document.getElementById('discovery-mobile-search-form');
        var input = document.getElementById('discovery-mobile-search-input');
        var resultsBox = document.getElementById('discovery-mobile-search-results');
        if (!form || !input || !resultsBox) return;

        var beaches = null;
        var debounceT = null;
        var activeIndex = -1;
        var lastResults = [];

        function ensureBeaches() {
            if (Array.isArray(beaches) && beaches.length) return Promise.resolve(beaches);
            if (window.BeachFinder && typeof window.BeachFinder.loadBeaches === 'function') {
                return window.BeachFinder.loadBeaches().then(function (list) {
                    beaches = Array.isArray(list) ? list : [];
                    return beaches;
                });
            }
            beaches = [];
            return Promise.resolve(beaches);
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function getLang() {
            try { return (document.documentElement.lang || 'en').slice(0, 2); } catch (e) { return 'en'; }
        }

        function beachHref(b) {
            var lang = getLang();
            return (lang === 'es' ? '/es/playa/' : '/beach/') + (b.slug || '');
        }

        function render(results) {
            lastResults = results;
            activeIndex = -1;
            if (!results.length) {
                resultsBox.innerHTML = '<div class="discovery-mobile-search__empty">No beaches match that search</div>';
                show(true);
                return;
            }
            var html = results.map(function (b, i) {
                return '<a class="discovery-mobile-search__result" role="option" id="dms-r-' + i + '"' +
                       ' href="' + escapeHtml(beachHref(b)) + '">' +
                       '<span class="discovery-mobile-search__result-icon" aria-hidden="true">📍</span>' +
                       '<span class="discovery-mobile-search__result-text">' +
                       '<span class="discovery-mobile-search__result-name">' + escapeHtml(b.name || '') + '</span>' +
                       (b.municipality ? '<span class="discovery-mobile-search__result-muni">' + escapeHtml(b.municipality) + '</span>' : '') +
                       '</span></a>';
            }).join('');
            resultsBox.innerHTML = html;
            show(true);
        }

        function show(open) {
            resultsBox.classList.toggle('hidden', !open);
            input.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function search(q) {
            ensureBeaches().then(function (list) {
                var nq = q.trim().toLowerCase();
                if (nq.length < 2) { show(false); return; }
                var matches = [];
                for (var i = 0; i < list.length && matches.length < 10; i++) {
                    var b = list[i];
                    var name = (b.name || '').toLowerCase();
                    var muni = (b.municipality || '').toLowerCase();
                    if (name.indexOf(nq) !== -1 || muni.indexOf(nq) !== -1) {
                        matches.push(b);
                    }
                }
                render(matches);
            });
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceT);
            var v = input.value;
            debounceT = setTimeout(function () { search(v); }, 140);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2 && lastResults.length) show(true);
        });

        input.addEventListener('keydown', function (e) {
            if (resultsBox.classList.contains('hidden') || !lastResults.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(lastResults.length - 1, activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(0, activeIndex - 1);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                window.location.href = beachHref(lastResults[activeIndex]);
                return;
            } else if (e.key === 'Escape') {
                show(false);
                return;
            } else {
                return;
            }
            resultsBox.querySelectorAll('[role="option"]').forEach(function (n, i) {
                n.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
                if (i === activeIndex) n.scrollIntoView({ block: 'nearest' });
            });
        });

        // Hide on outside click.
        document.addEventListener('click', function (e) {
            if (!form.contains(e.target)) show(false);
        });

        // Hide on submit (browser navigates anyway, but cleaner UX).
        form.addEventListener('submit', function () { show(false); });
    }

    // ----- Map marker clustering (lightweight grid-based) -----
    // The legacy map.js places one MapLibre marker per beach. With ~448 markers
    // at the island-wide zoom level they pile up into an unreadable blob. This
    // module hides overlapping markers behind a synthetic cluster pill on every
    // zoom/move; expanding back to individual markers when zoomed in.
    var CLUSTER_GRID_PX = 56;        // markers within this screen-pixel radius merge
    var CLUSTER_DISABLE_ZOOM = 11;   // at/above this zoom, all markers shown individually
    var clusterMarkers = [];

    function clearClusters() {
        clusterMarkers.forEach(function (m) { try { m.remove(); } catch (e) {} });
        clusterMarkers = [];
    }

    function recomputeClusters() {
        var map = window.BF_MAP_REF && window.BF_MAP_REF.instance;
        if (!map) return;
        var beaches = (window.BeachFinder && window.BeachFinder.beaches) || [];
        if (!beaches.length) return;
        // Map of beach id → real marker element (the .beach-marker we render).
        // The parent .maplibregl-marker is what MapLibre positions via CSS transform.
        var markerEls = {};
        document.querySelectorAll('.beach-marker[data-beach-id]').forEach(function (el) {
            markerEls[el.getAttribute('data-beach-id')] = el;
        });

        clearClusters();

        var zoom = map.getZoom ? map.getZoom() : 0;
        // High-zoom: unhide every real marker; no cluster pills.
        if (zoom >= CLUSTER_DISABLE_ZOOM) {
            Object.keys(markerEls).forEach(function (id) {
                markerEls[id].style.visibility = '';
            });
            return;
        }

        // Bucket beach lng/lat into a grid of CLUSTER_GRID_PX pixels.
        var grid = Object.create(null);
        for (var i = 0; i < beaches.length; i++) {
            var b = beaches[i];
            var lng = parseFloat(b.lng), lat = parseFloat(b.lat);
            if (!isFinite(lng) || !isFinite(lat)) continue;
            var pt;
            try { pt = map.project([lng, lat]); } catch (e) { pt = null; }
            if (!pt) continue;
            var gx = Math.floor(pt.x / CLUSTER_GRID_PX);
            var gy = Math.floor(pt.y / CLUSTER_GRID_PX);
            var key = gx + '_' + gy;
            (grid[key] || (grid[key] = [])).push({ id: b.id, lng: lng, lat: lat });
        }

        Object.keys(grid).forEach(function (key) {
            var cell = grid[key];
            if (cell.length <= 1) {
                var el = markerEls[cell[0].id];
                if (el) el.style.visibility = '';
                return;
            }
            var sumLat = 0, sumLng = 0;
            cell.forEach(function (item) {
                var mEl = markerEls[item.id];
                if (mEl) mEl.style.visibility = 'hidden';
                sumLat += item.lat;
                sumLng += item.lng;
            });
            var centerLng = sumLng / cell.length;
            var centerLat = sumLat / cell.length;
            var pill = document.createElement('div');
            pill.className = 'beach-cluster' +
                (cell.length >= 25 ? ' beach-cluster--xl' : cell.length >= 8 ? ' beach-cluster--lg' : '');
            pill.textContent = cell.length;
            pill.setAttribute('aria-label', cell.length + ' beaches in this area');
            pill.addEventListener('click', function () {
                try {
                    map.flyTo({
                        center: [centerLng, centerLat],
                        zoom: Math.min(13, (map.getZoom() || 0) + 2),
                        duration: 500
                    });
                } catch (e) {}
            });
            try {
                var cm = new maplibregl.Marker({ element: pill }).setLngLat([centerLng, centerLat]).addTo(map);
                clusterMarkers.push(cm);
            } catch (e) {}
        });
    }

    function attachClusterEvents() {
        var map = window.BF_MAP_REF && window.BF_MAP_REF.instance;
        if (!map || map.__bfClusterAttached) return;
        map.__bfClusterAttached = true;
        map.on('moveend', recomputeClusters);
        map.on('zoomend', recomputeClusters);
        // Initial pass after markers settle.
        setTimeout(recomputeClusters, 400);
        setTimeout(recomputeClusters, 1200);
    }

    // ----- Mobile-top dynamic height -----
    // Measures .discovery-mobile-top once it's laid out and writes its height
    // into a CSS var so the sticky filter-bar lands flush below it.
    function syncMobileTopHeight() {
        var mt = document.querySelector('.discovery-mobile-top');
        if (!mt) return;
        var h = Math.round(mt.getBoundingClientRect().height);
        if (h > 0) document.documentElement.style.setProperty('--bf-mobile-top-h', h + 'px');
    }

    // Distance filter — set max-distance and re-apply filters.
    if (typeof window.setMaxDistance !== 'function') {
        window.setMaxDistance = function (km) {
            var distInput = document.getElementById('distance-filter');
            if (distInput) distInput.value = String(km);
            // Update the dropdown trigger label so the chosen value is visible.
            document.querySelectorAll('.discovery-dropdown--drive .discovery-dropdown__item').forEach(function (item) {
                item.classList.toggle('is-active', String(parseInt(item.textContent, 10)) === String(km));
            });
            if (typeof window.applyFilters === 'function') {
                window.applyFilters();
            }
        };
    }

    // Preserve the user's mobile view (list vs map) across search submissions.
    // The mobile search form on the discovery shell posts `q` as a GET, but
    // PHP's default render mode is map; without a hint, the user's list
    // selection is lost on every search. Inject view=<current> at submit time.
    function preserveMobileViewOnSearch() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.classList || !form.classList.contains('discovery-mobile-search')) return;
            // If the form already has a view input, leave it.
            if (form.querySelector('input[name="view"]')) return;
            var mode = document.body.classList.contains('mobile-view-list') ? 'list'
                     : document.body.classList.contains('mobile-view-map') ? 'map' : '';
            if (!mode) return;
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'view';
            hidden.value = mode;
            form.appendChild(hidden);
        });
    }

    function onReady() {
        installMapFnWrappers();
        preserveMobileViewOnSearch();
        initMobileAutocomplete();
        syncMobileTopHeight();
        window.addEventListener('resize', syncMobileTopHeight);
        if (window.BeachFinder && window.BeachFinder.viewMode === SPLIT) {
            showSplit();
        }
        // Initialise mobile view from URL / localStorage on every page load.
        applyMobileView(readMobileView());
        window.addEventListener('resize', function () {
            // On viewport crossing we just reapply — safe no-op at ≥768px
            // because CSS ignores the body classes at that breakpoint.
            applyMobileView(readMobileView());
        });
        bootDiscoveryMap();
        scheduleClassify();
    }
    // discovery.js loads BEFORE map.js (on purpose, so our maplibregl.Map
    // constructor patch is in place when map.js creates the map). That
    // means by the time this module evaluates, map.js hasn't run yet —
    // window.initializeMap, addBeachMarkers, fitMapToMarkers are all
    // undefined. Wait for the 'load' event (fires after every deferred
    // script has executed) before installing wrappers and booting.
    if (document.readyState === 'complete') {
        onReady();
    } else {
        window.addEventListener('load', onReady, { once: true });
    }
})();
