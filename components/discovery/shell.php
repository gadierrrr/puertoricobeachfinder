<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Discovery Shell — Home · Map-first discovery (wireframe 1:1)
 *
 * Renders the full map-first homepage experience:
 *   - Sticky filter bar (filter-bar.php)
 *   - Split grid: left = list (discovery__list) · right = map (discovery__map)
 *
 * Expects: $beaches, $totalBeaches, $selectedTags, $selectedMunicipality,
 *          $amenities, $hasLifeguard, $maxDistance, $searchQuery, $sortBy, $lang
 */
?>
<?php
$localizedHome = $localizedHome ?? (function_exists('routeUrl') ? routeUrl('home', $lang ?? 'en') : '/');
$localizedQuiz = $localizedQuiz ?? (function_exists('routeUrl') ? routeUrl('quiz', $lang ?? 'en') : '/quiz');
?>
<section class="discovery-shell" id="discovery">
    <!-- Mobile-only: search pill + quick-action chips (wireframe 1:1) -->
    <div class="discovery-mobile-top" aria-hidden="false">
        <form class="discovery-mobile-search" action="<?= h($localizedHome) ?>" method="GET" role="search"
              autocomplete="off" id="discovery-mobile-search-form">
            <i data-lucide="search" class="w-4 h-4" aria-hidden="true"></i>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   id="discovery-mobile-search-input"
                   placeholder="<?= h(__('beach.search_placeholder_discovery')) ?>"
                   aria-label="<?= h(__('common.search')) ?>"
                   aria-autocomplete="list"
                   aria-controls="discovery-mobile-search-results"
                   aria-expanded="false"
                   autocomplete="off">
            <?php
            // Preserve the user's current view (list vs map) across search submissions.
            $currentView = $_GET['view'] ?? '';
            if ($currentView === 'map' || $currentView === 'list'):
            ?>
            <input type="hidden" name="view" value="<?= h($currentView) ?>">
            <?php endif; ?>
            <div id="discovery-mobile-search-results"
                 class="discovery-mobile-search__results hidden"
                 role="listbox"
                 aria-label="<?= h(__('common.search')) ?>"></div>
        </form>
        <div class="discovery-mobile-quick" role="group" aria-label="<?= h(__('filters.refine')) ?>">
            <button type="button" class="discovery-quick-chip" data-action="requestUserLocation">
                <i data-lucide="locate" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('filters.near_me')) ?></span>
            </button>
            <button type="button" class="discovery-quick-chip" data-action="setMobileView" data-action-args='["map"]'>
                <i data-lucide="map" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('aria.map_view')) ?></span>
            </button>
            <a href="<?= h($localizedQuiz) ?>" class="discovery-quick-chip">
                <i data-lucide="sparkles" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('nav.quiz')) ?></span>
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/filter-bar.php'; ?>

    <div class="discovery-split-v2">
        <!-- LEFT: sticky list column -->
        <aside class="discovery__list" aria-label="Beach list">
            <header class="discovery__list-header">
                <h2 class="discovery__list-title">
                    <span class="discovery__list-label">LIST</span>
                    <span class="discovery__list-count" id="discovery-count">
                        <?= h(__('beach.list_count', ['count' => number_format($totalBeaches)])) ?>
                    </span>
                </h2>
                <label class="discovery__sort">
                    <?= h(__('common.sort') ?: 'Sort') ?>:
                    <select id="discovery-sort" data-action="applyFilters" data-on="change">
                        <option value="name"     <?= ($sortBy ?? '') === 'name'     ? 'selected' : '' ?>><?= h(__('beach.sort_best_match')) ?></option>
                        <option value="rating"   <?= ($sortBy ?? '') === 'rating'   ? 'selected' : '' ?>><?= h(__('beach.sort_rating')) ?></option>
                        <option value="distance" <?= ($sortBy ?? '') === 'distance' ? 'selected' : '' ?>><?= h(__('beach.sort_distance')) ?></option>
                    </select>
                </label>
            </header>

            <div class="discovery__cards" id="beach-grid">
                <?php if (empty($beaches)): ?>
                <div class="discovery__empty">
                    <i data-lucide="umbrella" class="w-12 h-12 mx-auto mb-3" aria-hidden="true"></i>
                    <h3><?= h(__('filters.no_results_title')) ?></h3>
                    <p><?= h(__('filters.no_results_hint')) ?></p>
                    <button data-action="clearFilters" class="discovery-chip discovery-chip--clear">
                        <?= h(__('filters.clear_filters')) ?> ✕
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($beaches as $beach): include __DIR__ . '/compact-card.php'; endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (($totalPages ?? 1) > 1 && ($page ?? 1) < ($totalPages ?? 1)): ?>
            <div class="discovery__load-more">
                <button id="load-more-btn"
                        hx-get="/api/beaches.php?page=<?= (int)($page + 1) ?>"
                        hx-target="#beach-grid"
                        hx-swap="beforeend"
                        class="discovery-action">
                    <?= h(__('pages.home.load_more')) ?>
                </button>
            </div>
            <?php endif; ?>
        </aside>

        <!-- RIGHT: full-height map column -->
        <div class="discovery__map">
            <div id="map-container"></div>

            <!-- Bottom bar: [ Near me ]  [ 30-min drive ]  [ Legend ] (wireframe 1:1) -->
            <div class="discovery-map__bottom-bar">
                <button type="button" data-action="requestUserLocation" class="discovery-map__btn">
                    <i data-lucide="locate" class="w-4 h-4" aria-hidden="true"></i>
                    <?= h(__('beach.near_me_short')) ?>
                </button>
                <button type="button" data-action="setMaxDistance" data-action-args='[30]' class="discovery-map__btn">
                    <i data-lucide="clock" class="w-4 h-4" aria-hidden="true"></i>
                    <?= h(__('beach.drive_30min')) ?>
                </button>

                <div class="discovery-map__legend-inline" aria-label="<?= h(__('beach.legend_title')) ?>">
                    <span class="discovery-map__legend-item"><i class="pin-dot pin-dot--family"></i><?= h(function_exists('getTagLabel') ? getTagLabel('family-friendly') : 'Family') ?></span>
                    <span class="discovery-map__legend-item"><i class="pin-dot pin-dot--surf"></i><?= h(function_exists('getTagLabel') ? getTagLabel('surfing') : 'Surf') ?></span>
                    <span class="discovery-map__legend-item"><i class="pin-dot pin-dot--snorkel"></i><?= h(function_exists('getTagLabel') ? getTagLabel('snorkeling') : 'Snorkel') ?></span>
                    <span class="discovery-map__legend-item"><i class="pin-dot pin-dot--secluded"></i><?= h(function_exists('getTagLabel') ? getTagLabel('secluded') : 'Secluded') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ask the assistant FAB (bottom-right, outside map) -->
    <button type="button" class="discovery-assistant-fab" data-action="toggleChatPanel"
            aria-label="<?= h(__('chat.ask_assistant')) ?>">
        <i data-lucide="sparkles" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('chat.ask_assistant')) ?></span>
    </button>
</section>
