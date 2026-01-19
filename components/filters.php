<?php
/**
 * Filter Controls Component (Enhanced)
 *
 * @param array $selectedTags - Currently selected tags
 * @param string $selectedMunicipality - Currently selected municipality
 * @param string $sortBy - Current sort option
 * @param string $viewMode - Current view mode (list/map)
 * @param bool $locationEnabled - Whether user location is enabled
 * @param int $maxDistance - Max distance filter in km
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

$selectedTags = $selectedTags ?? [];
$selectedMunicipality = $selectedMunicipality ?? '';
$sortBy = $sortBy ?? 'name';
$viewMode = $viewMode ?? 'list';
$locationEnabled = $locationEnabled ?? false;
$maxDistance = $maxDistance ?? 50;
$activeFilterCount = count($selectedTags) + ($selectedMunicipality ? 1 : 0);
?>

<!-- Skip Link for Accessibility -->
<a href="#beach-grid" class="skip-link">Skip to beach results</a>

<!-- Mobile Filter Bar -->
<div class="md:hidden flex items-center gap-2 mb-4">
    <button id="mobile-filter-btn"
            onclick="openFilterDrawer()"
            class="flex-1 flex items-center justify-center gap-2 bg-white border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
        <i data-lucide="sliders-horizontal" class="w-4 h-4" aria-hidden="true"></i>
        <span>Filters</span>
        <?php if ($activeFilterCount > 0): ?>
        <span class="bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full"><?= $activeFilterCount ?></span>
        <?php endif; ?>
    </button>

    <!-- View Toggle (Mobile) -->
    <div class="flex rounded-lg border border-gray-300 overflow-hidden" role="group" aria-label="View mode">
        <button onclick="setViewMode('list')"
                id="mobile-view-list-btn"
                aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"
                class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium <?= $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
            <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i>
        </button>
        <button onclick="setViewMode('map')"
                id="mobile-view-map-btn"
                aria-pressed="<?= $viewMode === 'map' ? 'true' : 'false' ?>"
                class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium <?= $viewMode === 'map' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
            <i data-lucide="map" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </div>
</div>

<!-- Mobile Filter Drawer -->
<div id="filter-drawer" class="filter-drawer-overlay md:hidden" onclick="closeFilterDrawer(event)" role="dialog" aria-modal="true" aria-labelledby="filter-drawer-title">
    <div class="filter-drawer" onclick="event.stopPropagation()">
        <div class="filter-drawer-handle" aria-hidden="true"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h2 id="filter-drawer-title" class="text-lg font-semibold text-gray-900">Filters</h2>
            <button onclick="closeFilterDrawer()" class="p-2 text-gray-400 hover:text-gray-600" aria-label="Close filters">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Drawer Content -->
        <div class="p-4 space-y-5 overflow-y-auto max-h-[60vh]">
            <!-- Location Button -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <button id="mobile-location-btn"
                        onclick="requestUserLocation()"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors text-sm font-medium">
                    <i data-lucide="map-pin" id="mobile-location-icon" class="w-4 h-4" aria-hidden="true"></i>
                    <span id="mobile-location-text">Use My Location</span>
                </button>
            </div>

            <!-- Municipality -->
            <div>
                <label for="mobile-municipality-filter" class="block text-sm font-medium text-gray-700 mb-2">Municipality</label>
                <select id="mobile-municipality-filter"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Municipalities</option>
                    <?php foreach (MUNICIPALITIES as $muni): ?>
                    <option value="<?= h($muni) ?>" <?= $selectedMunicipality === $muni ? 'selected' : '' ?>><?= h($muni) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Distance Slider -->
            <div id="mobile-distance-container" class="<?= $locationEnabled ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Distance</label>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">5km</span>
                    <input type="range"
                           id="mobile-distance-filter"
                           min="5"
                           max="100"
                           step="5"
                           value="<?= h($maxDistance) ?>"
                           class="flex-1">
                    <span class="text-sm text-gray-500">100km</span>
                </div>
                <div class="text-center mt-1">
                    <span id="mobile-distance-value" class="text-sm font-medium text-blue-600"><?= h($maxDistance) ?>km</span>
                </div>
            </div>

            <!-- Sort -->
            <div>
                <label for="mobile-sort-filter" class="block text-sm font-medium text-gray-700 mb-2">Sort by</label>
                <select id="mobile-sort-filter"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                    <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?> id="mobile-sort-distance-option" <?= $locationEnabled ? '' : 'disabled' ?>>Distance</option>
                    <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Rating</option>
                </select>
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Beach Type</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (TAGS as $tag): ?>
                    <button onclick="toggleTagMobile('<?= h($tag) ?>')"
                            data-tag="<?= h($tag) ?>"
                            class="mobile-tag-btn px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                                   <?= in_array($tag, $selectedTags)
                                       ? 'bg-blue-600 text-white'
                                       : 'bg-gray-100 text-gray-700' ?>">
                        <?= h(getTagLabel($tag)) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 flex gap-3">
            <button onclick="clearFiltersMobile()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                Clear All
            </button>
            <button onclick="applyFiltersMobile()" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                Show Results
            </button>
        </div>
    </div>
</div>

<!-- Desktop Filters -->
<div class="filters-container bg-white rounded-xl shadow-sm p-4 mb-6 hidden md:block" role="search" aria-label="Beach filters">
    <!-- Top Row: Location Button, View Toggle, Sort -->
    <div class="flex flex-wrap items-center gap-3 mb-4 filter-row">
        <!-- Location Button -->
        <button id="location-btn"
                onclick="requestUserLocation()"
                aria-label="Enable location to see distances"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors text-sm font-medium">
            <i data-lucide="map-pin" id="location-icon" class="w-4 h-4" aria-hidden="true"></i>
            <span id="location-text">Use My Location</span>
        </button>

        <!-- View Toggle -->
        <div class="flex rounded-lg border border-gray-300 overflow-hidden ml-auto" role="group" aria-label="View mode">
            <button onclick="setViewMode('list')"
                    id="view-list-btn"
                    aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium <?= $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i>
                <span class="sr-only-mobile">List</span>
            </button>
            <button onclick="setViewMode('map')"
                    id="view-map-btn"
                    aria-pressed="<?= $viewMode === 'map' ? 'true' : 'false' ?>"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium <?= $viewMode === 'map' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                <i data-lucide="map" class="w-4 h-4" aria-hidden="true"></i>
                <span class="sr-only-mobile">Map</span>
            </button>
        </div>
    </div>

    <!-- Second Row: Municipality, Distance (if location), Sort -->
    <div class="flex flex-wrap items-center gap-3 mb-4 filter-row">
        <!-- Municipality Filter (Searchable) -->
        <div class="flex-1 min-w-[200px]">
            <label for="municipality-filter" class="sr-only">Filter by municipality</label>
            <select id="municipality-filter"
                    onchange="applyFilters()"
                    aria-label="Filter by municipality"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Municipalities</option>
                <?php foreach (MUNICIPALITIES as $muni): ?>
                <option value="<?= h($muni) ?>" <?= $selectedMunicipality === $muni ? 'selected' : '' ?>>
                    <?= h($muni) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Distance Slider (shown when location enabled) -->
        <div id="distance-filter-container" class="flex-1 min-w-[200px] <?= $locationEnabled ? '' : 'hidden' ?>">
            <div class="flex items-center gap-2">
                <label for="distance-filter" class="text-sm text-gray-600 whitespace-nowrap">Within:</label>
                <input type="range"
                       id="distance-filter"
                       min="5"
                       max="100"
                       step="5"
                       value="<?= h($maxDistance) ?>"
                       onchange="applyFilters()"
                       aria-valuemin="5"
                       aria-valuemax="100"
                       aria-valuenow="<?= h($maxDistance) ?>"
                       aria-valuetext="<?= h($maxDistance) ?> kilometers"
                       class="flex-1">
                <span id="distance-value" class="text-sm font-medium text-gray-700 min-w-[50px]" aria-live="polite"><?= h($maxDistance) ?>km</span>
            </div>
        </div>

        <!-- Sort -->
        <div class="min-w-[150px]">
            <label for="sort-filter" class="sr-only">Sort beaches by</label>
            <select id="sort-filter"
                    onchange="applyFilters()"
                    aria-label="Sort beaches by"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Sort by Name</option>
                <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?> id="sort-distance-option" <?= $locationEnabled ? '' : 'disabled' ?>>
                    Sort by Distance
                </option>
                <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Sort by Rating</option>
            </select>
        </div>
    </div>

    <!-- Tag Filters -->
    <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by beach type">
        <?php foreach (TAGS as $tag): ?>
        <button onclick="toggleTag('<?= h($tag) ?>')"
                data-tag="<?= h($tag) ?>"
                aria-pressed="<?= in_array($tag, $selectedTags) ? 'true' : 'false' ?>"
                class="tag-btn px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                       <?= in_array($tag, $selectedTags)
                           ? 'bg-blue-600 text-white'
                           : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <?= h(getTagLabel($tag)) ?>
        </button>
        <?php endforeach; ?>

        <!-- Clear Filters -->
        <button onclick="clearFilters()"
                id="clear-filters-btn"
                aria-label="Clear all filters"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-medium text-red-600 hover:bg-red-50 transition-colors <?= empty($selectedTags) && empty($selectedMunicipality) ? 'hidden' : '' ?>">
            <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
            <span>Clear</span>
        </button>
    </div>
</div>

<!-- Applied Filters Summary (Filter Chips) -->
<div id="applied-filters" class="flex flex-wrap gap-2 mb-4" role="region" aria-label="Applied filters" style="display: none;">
    <!-- Dynamically populated by JavaScript -->
</div>

<!-- Results Count -->
<div class="flex items-center justify-between mb-4">
    <p id="results-count" class="text-gray-600 text-sm" aria-live="polite" aria-atomic="true">
        <!-- Will be updated by JS -->
    </p>
</div>

<!-- Mobile Filter Drawer JavaScript -->
<script>
// Mobile Filter Drawer State
let mobileFilterState = {
    selectedTags: <?= json_encode($selectedTags) ?>,
    selectedMunicipality: '<?= h($selectedMunicipality) ?>',
    sortBy: '<?= h($sortBy) ?>',
    maxDistance: <?= (int)$maxDistance ?>
};

function openFilterDrawer() {
    const drawer = document.getElementById('filter-drawer');
    if (drawer) {
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Sync mobile drawer state with current state
        syncMobileDrawerState();

        // Re-initialize Lucide icons if needed
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeFilterDrawer(e) {
    if (e && e.target !== document.getElementById('filter-drawer')) return;
    const drawer = document.getElementById('filter-drawer');
    if (drawer) {
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}

function syncMobileDrawerState() {
    // Sync from global state to mobile drawer
    if (typeof state !== 'undefined') {
        mobileFilterState.selectedTags = [...state.selectedTags];
        mobileFilterState.selectedMunicipality = state.selectedMunicipality;
        mobileFilterState.sortBy = state.sortBy;
        mobileFilterState.maxDistance = state.maxDistance;
    }

    // Update mobile drawer UI
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    if (mobileSelect) mobileSelect.value = mobileFilterState.selectedMunicipality;

    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    if (mobileSortSelect) mobileSortSelect.value = mobileFilterState.sortBy;

    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.value = mobileFilterState.maxDistance;
        const valueDisplay = document.getElementById('mobile-distance-value');
        if (valueDisplay) valueDisplay.textContent = mobileFilterState.maxDistance + 'km';
    }

    // Sync tags
    document.querySelectorAll('.mobile-tag-btn').forEach(btn => {
        const tag = btn.dataset.tag;
        if (mobileFilterState.selectedTags.includes(tag)) {
            btn.classList.add('bg-blue-600', 'text-white');
            btn.classList.remove('bg-gray-100', 'text-gray-700');
        } else {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        }
    });
}

function toggleTagMobile(tag) {
    const idx = mobileFilterState.selectedTags.indexOf(tag);
    if (idx > -1) {
        mobileFilterState.selectedTags.splice(idx, 1);
    } else {
        mobileFilterState.selectedTags.push(tag);
    }

    // Update button UI
    const btn = document.querySelector(`.mobile-tag-btn[data-tag="${tag}"]`);
    if (btn) {
        btn.classList.toggle('bg-blue-600');
        btn.classList.toggle('text-white');
        btn.classList.toggle('bg-gray-100');
        btn.classList.toggle('text-gray-700');
    }
}

function clearFiltersMobile() {
    mobileFilterState.selectedTags = [];
    mobileFilterState.selectedMunicipality = '';
    mobileFilterState.sortBy = 'name';
    mobileFilterState.maxDistance = 50;

    // Reset UI
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    if (mobileSelect) mobileSelect.value = '';

    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    if (mobileSortSelect) mobileSortSelect.value = 'name';

    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.value = 50;
        const valueDisplay = document.getElementById('mobile-distance-value');
        if (valueDisplay) valueDisplay.textContent = '50km';
    }

    document.querySelectorAll('.mobile-tag-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });
}

function applyFiltersMobile() {
    // Collect values from mobile drawer
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');

    mobileFilterState.selectedMunicipality = mobileSelect?.value || '';
    mobileFilterState.sortBy = mobileSortSelect?.value || 'name';
    mobileFilterState.maxDistance = parseInt(mobileDistanceSlider?.value || 50);

    // Sync to global state
    if (typeof state !== 'undefined') {
        state.selectedTags = [...mobileFilterState.selectedTags];
        state.selectedMunicipality = mobileFilterState.selectedMunicipality;
        state.sortBy = mobileFilterState.sortBy;
        state.maxDistance = mobileFilterState.maxDistance;

        // Sync desktop UI
        const desktopSelect = document.getElementById('municipality-filter');
        if (desktopSelect) {
            if (desktopSelect.tomselect) {
                desktopSelect.tomselect.setValue(state.selectedMunicipality);
            } else {
                desktopSelect.value = state.selectedMunicipality;
            }
        }

        const desktopSortSelect = document.getElementById('sort-filter');
        if (desktopSortSelect) desktopSortSelect.value = state.sortBy;

        const desktopDistanceSlider = document.getElementById('distance-filter');
        if (desktopDistanceSlider) {
            desktopDistanceSlider.value = state.maxDistance;
            const valueDisplay = document.getElementById('distance-value');
            if (valueDisplay) valueDisplay.textContent = state.maxDistance + 'km';
        }

        // Sync tag buttons
        document.querySelectorAll('.tag-btn:not(.mobile-tag-btn)').forEach(btn => {
            const tag = btn.dataset.tag;
            if (state.selectedTags.includes(tag)) {
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                btn.setAttribute('aria-pressed', 'false');
            }
        });
    }

    // Close drawer and apply filters
    closeFilterDrawer();

    // Apply filters
    if (typeof applyFiltersWithHtmx === 'function') {
        applyFiltersWithHtmx();
    } else if (typeof applyFilters === 'function') {
        applyFilters();
    }

    // Update filter badge count
    updateMobileFilterBadge();
}

function updateMobileFilterBadge() {
    const count = (typeof state !== 'undefined')
        ? state.selectedTags.length + (state.selectedMunicipality ? 1 : 0)
        : mobileFilterState.selectedTags.length + (mobileFilterState.selectedMunicipality ? 1 : 0);

    const btn = document.getElementById('mobile-filter-btn');
    if (!btn) return;

    const badge = btn.querySelector('span.bg-blue-600');
    if (count > 0) {
        if (badge) {
            badge.textContent = count;
        } else {
            const newBadge = document.createElement('span');
            newBadge.className = 'bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full';
            newBadge.textContent = count;
            btn.appendChild(newBadge);
        }
    } else if (badge) {
        badge.remove();
    }
}

// Update mobile distance display on slider change
document.addEventListener('DOMContentLoaded', () => {
    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.addEventListener('input', (e) => {
            const valueDisplay = document.getElementById('mobile-distance-value');
            if (valueDisplay) valueDisplay.textContent = e.target.value + 'km';
        });
    }

    // ESC key to close filter drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('filter-drawer');
            if (drawer && drawer.classList.contains('open')) {
                closeFilterDrawer();
            }
        }
    });
});
</script>
