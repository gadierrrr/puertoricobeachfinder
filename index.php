<?php
/**
 * Beach Finder - Main Page
 * Discover Puerto Rico's beaches
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/inc/geo.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Discover Puerto Rico Beaches';
$pageDescription = 'Find your perfect Puerto Rico beach from 230+ locations. Filter by amenities, conditions, and distance. Explore beaches for surfing, snorkeling, family fun, and more.';

// Add structured data for homepage
$extraHead = websiteSchema() . organizationSchema();

// Get filter parameters from URL
$selectedTags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];
$selectedMunicipality = $_GET['municipality'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$viewMode = $_GET['view'] ?? 'list';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Validate filters
$selectedTags = array_filter($selectedTags, 'isValidTag');
if ($selectedMunicipality && !isValidMunicipality($selectedMunicipality)) {
    $selectedMunicipality = '';
}

// Build query
$sql = 'SELECT DISTINCT b.* FROM beaches b';
$params = [];
$where = ['b.publish_status = "published"'];

// Join tags table if filtering by tags
if (!empty($selectedTags)) {
    $sql .= ' INNER JOIN beach_tags bt ON b.id = bt.beach_id';
    $placeholders = [];
    foreach ($selectedTags as $i => $tag) {
        $placeholders[] = ':tag' . $i;
        $params[':tag' . $i] = $tag;
    }
    $where[] = 'bt.tag IN (' . implode(',', $placeholders) . ')';
}

// Municipality filter
if ($selectedMunicipality) {
    $where[] = 'b.municipality = :municipality';
    $params[':municipality'] = $selectedMunicipality;
}

$sql .= ' WHERE ' . implode(' AND ', $where);

// Sorting
switch ($sortBy) {
    case 'rating':
        $sql .= ' ORDER BY b.google_rating DESC NULLS LAST, b.name ASC';
        break;
    case 'distance':
        // Distance sorting handled client-side with JS
        $sql .= ' ORDER BY b.name ASC';
        break;
    default:
        $sql .= ' ORDER BY b.name ASC';
}

// Get all beaches (for map view and client-side filtering)
$allBeaches = query($sql, $params);

// Batch fetch tags and amenities (2 queries instead of 2*N queries)
attachBeachMetadata($allBeaches);

// Paginate for list view
$totalBeaches = count($allBeaches);
$totalPages = ceil($totalBeaches / $perPage);
$beaches = array_slice($allBeaches, ($page - 1) * $perPage, $perPage);

// Get user favorites if logged in
$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]);
    $userFavorites = array_column($favorites, 'beach_id');
}

// Include header
include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient text-white py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4">
            Discover Puerto Rico's Beaches
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto mb-6">
            Explore <?= number_format($totalBeaches) ?>+ beautiful beaches. Find the perfect spot for surfing, snorkeling, family fun, or a peaceful escape.
        </p>

        <!-- Hero CTA Buttons -->
        <div class="hero-cta flex flex-wrap justify-center gap-4">
            <button onclick="requestUserLocation()" class="hero-cta-btn bg-white text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5" aria-hidden="true"></i>
                <span>Find Beaches Near Me</span>
            </button>
            <a href="#beach-grid" class="hero-cta-btn bg-blue-700 hover:bg-blue-800 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                <span>Browse All Beaches</span>
            </a>
        </div>
    </div>
</section>

<!-- Discovery Sections -->
<?php
$trendingBeaches = getTrendingBeaches(4);
$hiddenGems = getHiddenGems(4);
$showDiscovery = !empty($selectedTags) || !empty($selectedMunicipality) ? false : true;
?>
<?php if ($showDiscovery && (!empty($trendingBeaches) || !empty($hiddenGems))): ?>
<section class="py-8 bg-gradient-to-b from-blue-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Trending Now -->
            <?php if (!empty($trendingBeaches)): ?>
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-5 h-5 text-orange-500" aria-hidden="true"></i>
                    <span>Trending Now</span>
                </h2>
                <div class="space-y-3">
                    <?php foreach ($trendingBeaches as $i => $tb): ?>
                    <a href="/beach/<?= h($tb['slug']) ?>"
                       class="flex items-center gap-3 bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                            <img src="<?= h(getThumbnailUrl($tb['cover_image'])) ?>"
                                 alt="<?= h($tb['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                 loading="lazy">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate">
                                <?= h($tb['name']) ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?= h($tb['municipality']) ?></p>
                            <?php if ($tb['google_rating']): ?>
                            <div class="flex items-center gap-1 mt-0.5">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-sm font-medium text-amber-700"><?= number_format($tb['google_rating'], 1) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="text-2xl font-bold text-gray-200 group-hover:text-blue-200 transition-colors">
                            <?= $i + 1 ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hidden Gems -->
            <?php if (!empty($hiddenGems)): ?>
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="gem" class="w-5 h-5 text-purple-500" aria-hidden="true"></i>
                    <span>Hidden Gems</span>
                </h2>
                <div class="space-y-3">
                    <?php foreach ($hiddenGems as $hg): ?>
                    <a href="/beach/<?= h($hg['slug']) ?>"
                       class="flex items-center gap-3 bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                            <img src="<?= h(getThumbnailUrl($hg['cover_image'])) ?>"
                                 alt="<?= h($hg['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                 loading="lazy">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate">
                                <?= h($hg['name']) ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?= h($hg['municipality']) ?></p>
                            <?php if ($hg['google_rating']): ?>
                            <div class="flex items-center gap-1 mt-0.5">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-sm font-medium text-amber-700"><?= number_format($hg['google_rating'], 1) ?></span>
                                <?php if ($hg['google_review_count'] < 50): ?>
                                <span class="text-xs text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded ml-1">Undiscovered</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="sparkles" class="w-5 h-5 text-purple-300 group-hover:text-purple-400 transition-colors flex-shrink-0" aria-hidden="true"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main Content -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filters -->
        <?php
        $locationEnabled = false; // Set by JS when location is granted
        $maxDistance = 50;
        include __DIR__ . '/components/filters.php';
        ?>

        <!-- List View -->
        <div id="list-view" class="<?= $viewMode === 'list' ? '' : 'hidden' ?>">
            <?php
            $userLocation = null; // Set by JS when location is granted
            include __DIR__ . '/components/beach-grid.php';
            ?>

            <!-- Load More / Pagination -->
            <?php if ($totalPages > 1 && $page < $totalPages):
                $apiParams = array_filter([
                    'tags' => $selectedTags ?: null,
                    'municipality' => $selectedMunicipality ?: null,
                    'sort' => $sortBy !== 'name' ? $sortBy : null,
                    'page' => $page + 1
                ]);
            ?>
            <div id="load-more-container" class="text-center mt-8">
                <button id="load-more-btn"
                        hx-get="/api/beaches.php?<?= http_build_query($apiParams) ?>"
                        hx-target="#beach-grid"
                        hx-swap="beforeend"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    Load More Beaches
                    <span class="htmx-indicator ml-2">...</span>
                </button>
                <p class="text-sm text-gray-500 mt-2">
                    Showing <?= min($page * $perPage, $totalBeaches) ?> of <?= $totalBeaches ?> beaches
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Map View -->
        <div id="map-view" class="<?= $viewMode === 'map' ? '' : 'hidden' ?>">
            <div id="map-container"></div>
        </div>

    </div>
</section>

<!-- Beach Details Drawer -->
<div id="beach-drawer" class="drawer-overlay" onclick="closeBeachDrawer(event)">
    <div class="drawer-content" onclick="event.stopPropagation()">
        <div id="drawer-content-inner">
            <!-- Content loaded via HTMX -->
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" onclick="closeShareModal()">
    <div class="share-modal-content" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold">Share Beach</h3>
            <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close share dialog">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="share-modal-body">
            <!-- Content set by JS -->
        </div>
    </div>
</div>

<!-- Pass data to JavaScript -->
<script>
window.BeachFinder = {
    beaches: <?= json_encode($allBeaches) ?>,
    selectedTags: <?= json_encode($selectedTags) ?>,
    selectedMunicipality: <?= json_encode($selectedMunicipality) ?>,
    sortBy: <?= json_encode($sortBy) ?>,
    viewMode: <?= json_encode($viewMode) ?>,
    userFavorites: <?= json_encode($userFavorites) ?>,
    isAuthenticated: <?= isAuthenticated() ? 'true' : 'false' ?>,
    csrfToken: <?= json_encode(csrfToken()) ?>,
    mapCenter: <?= json_encode(getPRCenter()) ?>,
    totalBeaches: <?= $totalBeaches ?>,
    tagLabels: <?= json_encode(array_combine(TAGS, array_map('getTagLabel', TAGS))) ?>
};
</script>

<?php
// Extra scripts for map
$extraScripts = '<script defer src="/assets/js/map.js"></script>';
include __DIR__ . '/components/footer.php';
?>
