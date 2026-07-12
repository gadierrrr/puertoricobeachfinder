<?php
/**
 * Beach Comparison Page
 * Compare up to 3 beaches side-by-side
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/i18n.php';

// Get beach IDs from URL
$beachIds = isset($_GET['beaches']) ? array_filter(array_map('trim', explode(',', $_GET['beaches']))) : [];
$beachIds = array_slice($beachIds, 0, 3); // Max 3 beaches

// Page metadata
$pageTitle = __('compare.title');
$pageDescription = __('compare.description');

// Fetch beaches if IDs provided
$beaches = [];
if (!empty($beachIds)) {
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $beaches = query("
        SELECT * FROM beaches
        WHERE id IN ($placeholders) AND publish_status = 'published'
    ", $beachIds);

    // Maintain order from URL
    $beachesById = [];
    foreach ($beaches as $beach) {
        $beachesById[$beach['id']] = $beach;
    }
    $beaches = [];
    foreach ($beachIds as $id) {
        if (isset($beachesById[$id])) {
            $beaches[] = $beachesById[$id];
        }
    }

    // Fetch tags and amenities for each beach
    foreach ($beaches as &$beach) {
        $beach['tags'] = array_column(
            query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
            'tag'
        );
        $beach['amenities'] = array_column(
            query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
            'amenity'
        );

        // Get review stats
        $reviewStats = queryOne("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count
            FROM beach_reviews
            WHERE beach_id = :id AND status = 'published'
        ", [':id' => $beach['id']]);
        $beach['user_rating'] = $reviewStats['avg_rating'] ? round($reviewStats['avg_rating'], 1) : null;
        $beach['user_review_count'] = $reviewStats['count'];
    }
    unset($beach);

    if (count($beaches) > 1) {
        $beachNames = array_map(fn($b) => $b['name'], $beaches);
        $pageTitle = __('compare.title_vs', ['names' => implode(' vs ', $beachNames)]);
    }
}

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => __('compare.title')]
];

require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/components/seo-schemas.php';
$comparePath = localizePath('/compare', getCurrentLanguage());
$extraHead = ($extraHead ?? '')
    . webPageSchema($pageTitle, $pageDescription, $comparePath)
    . breadcrumbSchema([
        ['name' => __('nav.home'), 'url' => routeUrl('home', getCurrentLanguage())],
        ['name' => __('compare.title'), 'url' => $comparePath],
    ]);

$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-tool');
include APP_ROOT . '/components/header.php';
?>

<main id="main-content" class="min-h-screen bg-sand-50">
    <!-- Header -->
    <section class="bg-white border-b border-warm-200 py-6 pt-24 managed-page-hero"<?= pageHeroAttributes('compare') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            <div class="mb-4">
                <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-warm-900"><?= h(__('compare.title')) ?></h1>
                    <p class="text-warm-500 mt-1"><?= h(__('compare.subtitle')) ?></p>
                </div>
                <div class="flex gap-2">
                    <button data-action="openBeachSelector"
                            class="bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-4 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                        <span><?= h(__('compare.add_beach')) ?></span>
                    </button>
                    <?php if (!empty($beaches)): ?>
                    <button data-action="clearComparison"
                            class="bg-warm-100 hover:bg-warm-200 text-warm-900 px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 border border-warm-200">
                        <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                        <span><?= h(__('compare.clear_all')) ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($beaches)): ?>
    <!-- Empty State -->
    <section class="py-16">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <div class="text-6xl mb-4">⚖️</div>
            <h2 class="text-xl font-semibold text-warm-900 mb-2"><?= h(__('compare.no_beaches_selected')) ?></h2>
            <p class="text-warm-500 mb-6"><?= h(__('compare.empty_state_description')) ?></p>
            <button data-action="openBeachSelector"
                    class="bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-semibold transition-colors inline-flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>
                <span><?= h(__('compare.add_first_beach')) ?></span>
            </button>
            <p class="text-sm text-gray-500 mt-4">
                <?= h(__('compare.tip_also_add')) ?> <a href="/" class="text-sunset-400 hover:text-sunset-400"><?= h(__('compare.beach_listing')) ?></a> <?= h(__('compare.tip_compare_button')) ?>
            </p>
        </div>
    </section>
    <?php elseif (count($beaches) === 1): ?>
    <!-- Single Beach - Prompt to add more -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-sunset-400/10 border border-sunset-400/30 rounded-lg p-4 mb-6 flex items-center gap-3">
                <i data-lucide="info" class="w-5 h-5 text-sunset-400 flex-shrink-0" aria-hidden="true"></i>
                <p class="text-warm-600"><?= h(__('compare.add_more_prompt')) ?></p>
                <button data-action="openBeachSelector" class="ml-auto bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors">
                    <?= h(__('compare.add_beach')) ?>
                </button>
            </div>

            <!-- Show single beach card -->
            <div class="max-w-md">
                <?php $beach = $beaches[0]; ?>
                <div class="beach-detail-card overflow-hidden">
                    <div class="relative">
                        <img src="<?= h(getBeachImageUrl($beach, 'medium')) ?>"
                             data-fallback-src="/images/beaches/placeholder-beach.webp"
                             alt="<?= h($beach['name']) ?>"
                             class="w-full h-48 object-cover">
                        <button data-action="removeFromComparison" data-action-args='["<?= h($beach['id']) ?>"]'
                                class="absolute top-2 right-2 bg-black/50 hover:bg-black/70 text-white p-1.5 rounded-full border border-warm-200"
                                aria-label="<?= h(__('compare.aria_remove')) ?>">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-warm-900"><?= h($beach['name']) ?></h3>
                        <p class="text-warm-500 text-sm"><?= h($beach['municipality']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- Comparison Table -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Beach Headers -->
            <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> gap-4 mb-6">
                <?php foreach ($beaches as $beach): ?>
                <div class="beach-detail-card overflow-hidden">
                    <div class="relative">
                        <a href="/beach/<?= h($beach['slug']) ?>">
                            <img src="<?= h(getBeachImageUrl($beach, 'medium')) ?>"
                                 data-fallback-src="/images/beaches/placeholder-beach.webp"
                                 alt="<?= h($beach['name']) ?>"
                                 class="w-full h-40 object-cover hover:opacity-90 transition-opacity">
                        </a>
                        <button data-action="removeFromComparison" data-action-args='["<?= h($beach['id']) ?>"]'
                                class="absolute top-2 right-2 bg-black/50 hover:bg-black/70 text-white p-1.5 rounded-full border border-warm-200 transition-colors"
                                aria-label="<?= h(__('compare.aria_remove_named', ['name' => $beach['name']])) ?>">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4 text-center">
                        <a href="/beach/<?= h($beach['slug']) ?>" class="font-bold text-lg text-warm-900 hover:text-sunset-400 transition-colors">
                            <?= h($beach['name']) ?>
                        </a>
                        <p class="text-warm-500 text-sm"><?= h($beach['municipality']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Comparison Sections -->
            <div class="space-y-4">

                <!-- Ratings -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-warm-50 px-4 py-3 border-b border-warm-200">
                        <h3 class="font-semibold text-warm-900 flex items-center gap-2">
                            <i data-lucide="star" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_ratings')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 text-center">
                            <!-- Google Rating -->
                            <?php if ($beach['google_rating']): ?>
                            <div class="mb-3">
                                <div class="text-2xl font-bold text-sunset-400"><?= number_format($beach['google_rating'], 1) ?></div>
                                <div class="text-xs text-gray-500"><?= h(__('compare.google_rating')) ?></div>
                                <?php if ($beach['google_review_count']): ?>
                                <div class="text-xs text-gray-500">(<?= number_format($beach['google_review_count']) ?> <?= h(__('compare.reviews_count')) ?>)</div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="mb-3 text-gray-500 text-sm"><?= h(__('compare.no_google_rating')) ?></div>
                            <?php endif; ?>

                            <!-- User Rating -->
                            <?php if ($beach['user_rating']): ?>
                            <div>
                                <div class="text-xl font-bold text-sunset-400"><?= number_format($beach['user_rating'], 1) ?></div>
                                <div class="text-xs text-gray-500"><?= h(__('compare.community_rating')) ?></div>
                                <div class="text-xs text-gray-500">(<?= $beach['user_review_count'] ?> <?= h(__('compare.reviews_count')) ?>)</div>
                            </div>
                            <?php else: ?>
                            <div class="text-gray-500 text-sm"><?= h(__('compare.no_community_reviews')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Activities (Tags) -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-warm-50 px-4 py-3 border-b border-warm-200">
                        <h3 class="font-semibold text-warm-900 flex items-center gap-2">
                            <i data-lucide="activity" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_activities')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4">
                            <?php if (!empty($beach['tags'])): ?>
                            <div class="flex flex-wrap gap-1.5 justify-center">
                                <?php foreach ($beach['tags'] as $tag): ?>
                                <span class="inline-block bg-sunset-400/10 text-sunset-400 px-2 py-1 rounded-full text-xs border border-sunset-400/20">
                                    <?= h(getTagLabel($tag)) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.not_specified')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-warm-50 px-4 py-3 border-b border-warm-200">
                        <h3 class="font-semibold text-warm-900 flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_amenities')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php
                        // Collect all amenities across beaches
                        $allAmenities = [];
                        foreach ($beaches as $beach) {
                            $allAmenities = array_merge($allAmenities, $beach['amenities']);
                        }
                        $allAmenities = array_unique($allAmenities);
                        sort($allAmenities);
                        ?>
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4">
                            <?php if (!empty($beach['amenities'])): ?>
                            <ul class="space-y-1.5">
                                <?php foreach ($allAmenities as $amenity): ?>
                                <li class="flex items-center gap-2 text-sm <?= in_array($amenity, $beach['amenities']) ? 'text-warm-600' : 'text-gray-600' ?>">
                                    <?php if (in_array($amenity, $beach['amenities'])): ?>
                                    <i data-lucide="check" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                                    <?php else: ?>
                                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <span><?= h(getAmenityLabel($amenity)) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.no_amenities_listed')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Conditions -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-warm-50 px-4 py-3 border-b border-warm-200">
                        <h3 class="font-semibold text-warm-900 flex items-center gap-2">
                            <i data-lucide="waves" class="w-4 h-4 text-cyan-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_conditions')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 space-y-2">
                            <?php if ($beach['sargassum'] || $beach['surf'] || $beach['wind']): ?>
                                <?php if ($beach['sargassum']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-warm-500"><?= h(__('compare.condition_sargassum')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['sargassum']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('sargassum', $beach['sargassum'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if ($beach['surf']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-warm-500"><?= h(__('compare.condition_surf')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['surf']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('surf', $beach['surf'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if ($beach['wind']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-warm-500"><?= h(__('compare.condition_wind')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['wind']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('wind', $beach['wind'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.no_conditions_data')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Access & Parking -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-warm-50 px-4 py-3 border-b border-warm-200">
                        <h3 class="font-semibold text-warm-900 flex items-center gap-2">
                            <i data-lucide="car" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_access')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 space-y-2 text-sm">
                            <?php if ($beach['access_label']): ?>
                            <div>
                                <span class="text-gray-500"><?= h(__('compare.access_label')) ?></span>
                                <span class="text-warm-900 font-medium ml-1"><?= h($beach['access_label']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($beach['parking_details']): ?>
                            <div>
                                <span class="text-gray-500"><?= h(__('compare.parking_label')) ?></span>
                                <span class="text-warm-600 ml-1"><?= h($beach['parking_details']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!$beach['access_label'] && !$beach['parking_details']): ?>
                            <div class="text-center text-gray-500"><?= h(__('compare.no_info_available')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="compare-grid compare-actions grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-warm-200">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 text-center">
                            <a href="/beach/<?= h($beach['slug']) ?>"
                               class="inline-block bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-4 py-2 rounded-lg font-semibold transition-colors">
                                <?= h(__('compare.view_details')) ?>
                            </a>
                            <a href="<?= h(getDirectionsUrl($beach)) ?>"
                               target="_blank"
                               class="inline-block ml-2 bg-warm-100 hover:bg-warm-200 text-warm-900 px-4 py-2 rounded-lg font-medium transition-colors border border-warm-200">
                                <?= h(__('compare.directions')) ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- Beach Selector Modal -->
<div id="beach-selector-modal" class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="selector-title" data-action="closeBeachSelector">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col border border-warm-200" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-warm-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h2 id="selector-title" class="text-lg font-semibold text-warm-900"><?= h(__('compare.modal_title')) ?></h2>
            <button data-action="closeBeachSelector" class="text-warm-500 hover:text-warm-900 p-1" aria-label="<?= h(__('compare.aria_close')) ?>">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-4 border-b border-warm-200 flex-shrink-0">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" aria-hidden="true"></i>
                <input type="text" id="beach-search" placeholder="<?= h(__('compare.search_placeholder')) ?>"
                       class="w-full pl-10 pr-4 py-2 bg-warm-50 border border-warm-200 rounded-lg text-warm-900 placeholder-gray-500 focus:ring-2 focus:ring-ocean-400/50 focus:border-ocean-400/50"
                       data-action="filterBeaches" data-action-args='["__this__"]' data-on="input"
                       aria-label="<?= h(__('compare.search_placeholder')) ?>">
            </div>
        </div>

        <div id="beach-list" class="overflow-y-auto flex-1 p-2">
            <!-- Beaches loaded via JS -->
            <div class="text-center py-8 text-gray-500"><?= h(__('compare.loading_beaches')) ?></div>
        </div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Current comparison beaches
let comparisonBeaches = <?= json_encode(array_column($beaches, 'id')) ?>;
const MAX_COMPARE_PAGE = 3;

// Translated strings for JS
const COMPARE_STRINGS = <?= json_encode([
    'no_beaches_found' => __('compare.no_beaches_found'),
    'max_alert' => __('compare.max_alert'),
    'max_alert_js' => __('compare.max_alert_js'),
]) ?>;

// All beaches for selector
let allBeaches = [];

// Load all beaches for selector
async function loadAllBeaches() {
    try {
        const response = await fetch('/api/beaches.php?format=json&limit=50&page=1');
        if (!response.ok) throw new Error('Beach list request failed');
        const data = await response.json();
        allBeaches = data.data || data.beaches || [];

        const totalPages = Math.max(1, Number(data.meta?.pages || 1));
        if (totalPages > 1) {
            const pageRequests = [];
            for (let page = 2; page <= totalPages; page++) {
                pageRequests.push(
                    fetch('/api/beaches.php?format=json&limit=50&page=' + page)
                        .then(pageResponse => {
                            if (!pageResponse.ok) throw new Error('Beach list page request failed');
                            return pageResponse.json();
                        })
                );
            }
            const pageResults = await Promise.all(pageRequests);
            pageResults.forEach(pageData => {
                allBeaches.push(...(pageData.data || pageData.beaches || []));
            });
        }
        renderBeachList(document.getElementById('beach-search')?.value || '');
    } catch (error) {
        console.error('Failed to load beaches:', error);
    }
}

function renderBeachList(filter = '') {
    const container = document.getElementById('beach-list');
    const filterLower = filter.toLowerCase();

    const filtered = allBeaches.filter(beach => {
        if (comparisonBeaches.includes(beach.id)) return false;
        if (!filter) return true;
        return beach.name.toLowerCase().includes(filterLower) ||
               beach.municipality.toLowerCase().includes(filterLower);
    });

    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">' + escapeHtml(COMPARE_STRINGS.no_beaches_found) + '</div>';
        return;
    }

    container.innerHTML = filtered.slice(0, 50).map(beach => `
        <button data-action="addToComparison" data-action-args='["${beach.id}"]'
                class="w-full flex items-center gap-3 p-3 hover:bg-warm-50 rounded-lg transition-colors text-left"
                ${comparisonBeaches.length >= MAX_COMPARE_PAGE ? 'disabled' : ''}>
            <img src="${beach.image_url || beach.cover_image || '/images/beaches/placeholder-beach.webp'}"
                 data-fallback-src="/images/beaches/placeholder-beach.webp"
                 alt="${escapeHtml(beach.name)}" class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="font-medium text-warm-900 truncate">${escapeHtml(beach.name)}</div>
                <div class="text-sm text-warm-500">${escapeHtml(beach.municipality)}</div>
            </div>
            ${beach.google_rating ? `<div class="text-sunset-400 text-sm">★ ${beach.google_rating.toFixed(1)}</div>` : ''}
        </button>
    `).join('');

    if (comparisonBeaches.length >= MAX_COMPARE_PAGE) {
        container.innerHTML = '<div class="text-center py-4 text-sunset-400 bg-sunset-400/10 border border-sunset-400/30 rounded-lg mb-2">' + escapeHtml(COMPARE_STRINGS.max_alert) + '</div>' + container.innerHTML;
    }
}

function filterBeaches(query) {
    renderBeachList(query);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function openBeachSelector() {
    document.getElementById('beach-selector-modal').classList.remove('hidden');
    document.getElementById('beach-selector-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';
    document.getElementById('beach-search').value = '';
    document.getElementById('beach-search').focus();

    if (allBeaches.length === 0) {
        loadAllBeaches();
    } else {
        renderBeachList();
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeBeachSelector() {
    document.getElementById('beach-selector-modal').classList.add('hidden');
    document.getElementById('beach-selector-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function addToComparison(beachId) {
    if (comparisonBeaches.length >= MAX_COMPARE_PAGE) {
        alert(COMPARE_STRINGS.max_alert_js);
        return;
    }

    if (!comparisonBeaches.includes(beachId)) {
        comparisonBeaches.push(beachId);
        updateComparisonUrl();
    }
}

function removeFromComparison(beachId) {
    comparisonBeaches = comparisonBeaches.filter(id => id !== beachId);
    updateComparisonUrl();
}

function clearComparison() {
    comparisonBeaches = [];
    updateComparisonUrl();
}

function updateComparisonUrl() {
    if (comparisonBeaches.length === 0) {
        window.location.href = '/compare';
    } else {
        window.location.href = '/compare?beaches=' + comparisonBeaches.join(',');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeBeachSelector();
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include APP_ROOT . '/components/footer.php'; ?>
