<?php
/**
 * Beach Finder - Main Page
 * Discover Puerto Rico's beaches
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/geo.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/index.php') {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $target = '/';
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    exit;
}

// Locale-aware front controller: resolve Spanish (and future locale) routes,
// handle trailing-slash redirects, or 404 unknown paths.
// Real directory paths like /guides/ are served by Nginx's directory index
// and never reach this code.
if ($requestPath !== '/') {
    // Check locale route match first (before trailing-slash redirect).
    $routeMatch = localeRouteMatch($requestPath);

    if (is_array($routeMatch) && ($routeMatch['route_key'] ?? '') === 'home') {
        // /es is a valid localized homepage route — fall through to render homepage.
    } elseif (is_array($routeMatch)) {
        // Non-home locale route (e.g. /es/mejores-playas-familiares).
        // Resolve to internal PHP script and include it.
        $resolved = resolvePublicScriptFromLocalizedPath($requestPath);
        if (is_array($resolved) && !empty($resolved['script'])) {
            // Inject query params (e.g. slug for beach detail pages).
            foreach (($resolved['query'] ?? []) as $qk => $qv) {
                $_GET[(string) $qk] = $qv;
            }
            include APP_ROOT . '/public' . $resolved['script'];
            exit;
        }
        // Fallback: if resolution fails unexpectedly, 404.
        http_response_code(404);
        include APP_ROOT . '/public/errors/404.php';
        exit;
    } elseif (str_ends_with($requestPath, '/')) {
        // Trailing-slash redirect for non-matched paths.
        $clean = rtrim($requestPath, '/');
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        header('Location: ' . $clean . ($qs !== '' ? '?' . $qs : ''), true, 301);
        exit;
    } else {
        // Check for accented municipality URLs (e.g., /beaches-in-guánica → /beaches-in-guanica)
        $decodedPath = urldecode($requestPath);
        if (preg_match('#^/(beaches-in-|es/playas-en-)(.+)$#u', $decodedPath, $m)) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $m[2]);
            if ($ascii !== false && $ascii !== '' && $ascii !== $m[2]) {
                $qs = $_SERVER['QUERY_STRING'] ?? '';
                header('Location: /' . $m[1] . $ascii . ($qs !== '' ? '?' . $qs : ''), true, 301);
                exit;
            }
        }
        // No locale match, no trailing slash — unknown URL → 404.
        http_response_code(404);
        include APP_ROOT . '/public/errors/404.php';
        exit;
    }
}

// Page metadata
$pageTitle = __('pages.home.title');
$pageDescription = __('pages.home.description');

// Add structured data for homepage
$extraHead = websiteSchema() . organizationSchema();

// Get filter parameters from URL
$selectedTags = [];
if (isset($_GET['tags'])) {
    $selectedTags = array_merge($selectedTags, (array)$_GET['tags']);
}
if (isset($_GET['tags[]'])) {
    $selectedTags = array_merge($selectedTags, (array)$_GET['tags[]']);
}
if (isset($_GET['activity']) && is_string($_GET['activity'])) {
    $selectedTags[] = $_GET['activity'];
}
$selectedMunicipality = $_GET['municipality'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$viewMode = $_GET['view'] ?? 'list';
$selectedCollection = $_GET['collection'] ?? '';
$includeAll = isset($_GET['include_all']) && in_array((string)$_GET['include_all'], ['1', 'true'], true);
$hasLifeguard = isset($_GET['has_lifeguard']) && in_array((string)$_GET['has_lifeguard'], ['1', 'true'], true);
$amenities = [];
if (isset($_GET['amenities'])) {
    $amenities = array_merge($amenities, (array)$_GET['amenities']);
}
if (isset($_GET['amenities[]'])) {
    $amenities = array_merge($amenities, (array)$_GET['amenities[]']);
}
if (in_array('lifeguards', $amenities, true) || in_array('lifeguard', $amenities, true)) {
    $hasLifeguard = true;
}
if (!$selectedCollection && (($_GET['near'] ?? '') === 'san-juan')) {
    $selectedCollection = 'beaches-near-san-juan';
}
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$searchQuery = trim($_GET['q'] ?? '');

// Validate filters
$selectedTags = array_values(array_unique(array_filter($selectedTags, 'isValidTag')));
if ($selectedMunicipality && !isValidMunicipality($selectedMunicipality)) {
    $selectedMunicipality = '';
}
if (!isValidCollectionKey((string)$selectedCollection)) {
    $selectedCollection = '';
}

// Build query
$sql = 'SELECT DISTINCT b.* FROM beaches b';
$params = [];
$where = ['b.publish_status = "published"', '(b.location_type = "beach" OR b.location_type IS NULL)'];

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

// Lifeguard filter
if ($hasLifeguard) {
    $where[] = 'b.has_lifeguard = 1';
}

// Search query filter - searches name, municipality, and description
if ($searchQuery) {
    $where[] = '(b.name LIKE :search OR b.municipality LIKE :search2 OR b.description LIKE :search3)';
    $searchPattern = '%' . $searchQuery . '%';
    $params[':search'] = $searchPattern;
    $params[':search2'] = $searchPattern;
    $params[':search3'] = $searchPattern;
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

// Get data for hero section
$tagCounts = getBeachCountsByTag();
$popularBeaches = getPopularBeaches(4);
$siteStats = getSiteStats();
$publishedCount = queryOne('SELECT COUNT(*) as cnt FROM beaches WHERE publish_status = "published" AND (location_type = "beach" OR location_type IS NULL)')['cnt'];

// Include header
include APP_ROOT . '/components/header.php';
?>

<!-- Hero Section - Consolidated, search-first layout -->
<header class="relative w-full min-h-[540px] sm:min-h-[600px] lg:min-h-[620px] flex items-center pt-20 overflow-hidden">
    <!-- Background with gradient overlays -->
    <div class="absolute inset-0 -z-10">
        <img src="/images/beaches/jobos-beach-isabela-18513-67085.jpg"
             alt="Jobos Beach in Isabela, Puerto Rico - famous for surfing"
             class="w-full h-full object-cover scale-110"
             loading="eager">
        <!-- bottom-up brand gradient -->
        <div class="absolute inset-0 bg-hero-gradient"></div>
        <!-- left scrim keeps the consolidated text column legible wherever it sits -->
        <div class="absolute inset-0 bg-gradient-to-r from-ocean-900/75 via-ocean-900/30 to-transparent"></div>
        <div class="absolute inset-0 bg-black/30 sm:bg-black/15"></div>
    </div>

    <!-- Hero Content - single consolidated column -->
    <div class="relative z-10 w-full max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-[73px]">
        <div class="max-w-[680px] text-left animate-fade-in-up">
            <!-- Eyebrow Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/15 backdrop-blur-sm text-orange-300 text-xs mb-4">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?= h(__('pages.home.hero_eyebrow')) ?>
            </div>

            <!-- Headline -->
            <h1 class="text-[34px] sm:text-[48px] lg:text-[56px] font-serif text-white leading-[1.1] mb-3.5">
                <?= h(__('pages.home.hero_headline_1')) ?> <em style="color: #a3e8ea"><?= h(__('pages.home.hero_headline_2')) ?></em>
            </h1>

            <!-- Subtitle -->
            <p class="text-[15px] sm:text-base mb-6" style="color: rgba(255,255,255,0.82); max-width: 540px">
                <?= h(__('pages.home.hero_subtitle', ['count' => number_format($totalBeaches)])) ?>
            </p>

            <!-- Search (primary anchor, directly under the headline) -->
            <div class="bg-white rounded-2xl p-2 shadow-search animate-fade-in-up delay-100 w-full max-w-[600px]">
                <form action="/#beaches" method="GET" class="hero-search-form relative" id="hero-search-form">
                    <div class="flex items-center px-3 py-2">
                        <i data-lucide="search" class="w-[18px] h-[18px] text-warm-400 flex-shrink-0" aria-hidden="true"></i>
                        <input type="text"
                               name="q"
                               id="hero-search-input"
                               placeholder="<?= h(__('pages.home.search_placeholder')) ?>"
                               value="<?= h($searchQuery) ?>"
                               class="flex-1 bg-transparent border-none text-warm-900 placeholder-warm-400 px-3 py-2 focus:outline-none text-[15px]"
                               aria-label="<?= h(__('common.search')) ?>"
                               autocomplete="off">
                        <button type="submit" class="bg-ocean-600 hover:bg-ocean-700 text-white w-[42px] h-[42px] rounded-full flex items-center justify-center transition-colors flex-shrink-0" aria-label="<?= h(__('pages.home.search_button')) ?>">
                            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                    <!-- Search Autocomplete Dropdown -->
                    <div id="search-autocomplete" class="hidden absolute left-0 right-0 top-full mt-2 bg-white shadow-lg border border-warm-200 rounded-xl overflow-hidden z-50">
                        <div id="search-results" class="max-h-64 overflow-y-auto"></div>
                    </div>
                </form>
            </div>

            <!-- Stats (inline row, under the search) -->
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-5 text-[13px]" style="color: rgba(255,255,255,0.85)">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-sunset-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?= h(__('pages.home.hero_stat_rating', ['rating' => number_format($siteStats['avg_rating'], 1)])) ?>
                </span>
                <span aria-hidden="true" style="color: rgba(255,255,255,0.35)">&middot;</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-sunset-400"></span>
                    <?= h(__('pages.home.hero_stat_reviews', ['count' => number_format($siteStats['total_reviews'] / 1000) . 'K'])) ?>
                </span>
            </div>
        </div>
    </div>
</header>

<!-- Search Autocomplete Script -->
<script <?= cspNonceAttr() ?>>
(function() {
    const searchInput = document.getElementById('hero-search-input');
    const searchForm = document.getElementById('hero-search-form');
    const autocomplete = document.getElementById('search-autocomplete');
    const resultsContainer = document.getElementById('search-results');
    let debounceTimer = null;
    let beaches = <?= json_encode(array_map(function($b) {
        return [
            'name' => $b['name'],
            'slug' => $b['slug'],
            'municipality' => $b['municipality'],
            'tags' => $b['tags'] ?? []
        ];
    }, array_slice($allBeaches, 0, 100))) ?>;

    if (!searchInput || !autocomplete || !resultsContainer) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim().toLowerCase();

        if (query.length < 2) {
            autocomplete.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(function() {
            const results = beaches.filter(function(beach) {
                return beach.name.toLowerCase().includes(query) ||
                       beach.municipality.toLowerCase().includes(query) ||
                       (beach.tags && beach.tags.some(function(t) { return t.toLowerCase().includes(query); }));
            }).slice(0, 6);

            if (results.length === 0) {
                autocomplete.classList.add('hidden');
                return;
            }

            resultsContainer.innerHTML = results.map(function(beach) {
                return '<a href="/beach/' + beach.slug + '" class="flex items-center gap-3 px-4 py-3 hover:bg-warm-50 transition-colors border-b border-warm-100 last:border-0">' +
                    '<div class="w-8 h-8 rounded-full bg-sunset-400/20 flex items-center justify-center flex-shrink-0">' +
                    '<svg class="w-4 h-4 text-sunset-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="text-warm-900 font-medium truncate">' + beach.name + '</div>' +
                    '<div class="text-xs text-warm-500">' + beach.municipality + '</div>' +
                    '</div>' +
                    '<svg class="w-4 h-4 text-warm-300 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>' +
                    '</a>';
            }).join('');

            autocomplete.classList.remove('hidden');
        }, 150);
    });

    // Hide on click outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            autocomplete.classList.add('hidden');
        }
    });

    // Hide on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            autocomplete.classList.add('hidden');
        }
    });

    // Position autocomplete relative to form
    searchForm.style.position = 'relative';
})();
</script>

<!-- Category Cards - floating strip overlapping the hero's bottom edge -->
<section class="bg-white pb-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="relative z-20 -mt-10 sm:-mt-14 md:-mt-20 bg-white rounded-2xl shadow-xl border border-warm-100 p-4 md:p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-3">
                <?php
                $categories = [
                    'surfing'          => ['emoji' => '🏄‍♂️', 'label' => __('pages.home.category_surfing'),    'bg' => 'bg-blue-100'],
                    'snorkeling'       => ['emoji' => '🤿',    'label' => __('pages.home.category_snorkeling'), 'bg' => 'bg-teal-100'],
                    'family-friendly'  => ['emoji' => '👨‍👩‍👧', 'label' => __('pages.home.category_family'),     'bg' => 'bg-amber-100'],
                    'secluded'         => ['emoji' => '🌴',    'label' => __('pages.home.category_secluded'),   'bg' => 'bg-green-100'],
                    'swimming'         => ['emoji' => '🏊',    'label' => __('tags.swimming'),                  'bg' => 'bg-cyan-100'],
                    'scenic'           => ['emoji' => '🏞️',    'label' => __('tags.scenic'),                    'bg' => 'bg-orange-100'],
                ];
                foreach ($categories as $tag => $cat):
                    $count = $tagCounts[$tag] ?? 0;
                ?>
                <a href="/beaches/<?= h($tag) ?>"
                   class="flex flex-col items-center gap-2.5 py-4 px-3 rounded-xl hover:bg-warm-50 transition-colors group">
                    <div class="w-12 h-12 rounded-xl <?= $cat['bg'] ?> flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <?= $cat['emoji'] ?>
                    </div>
                    <span class="font-semibold text-warm-900 text-sm"><?= h($cat['label']) ?></span>
                    <span class="text-xs text-warm-500"><?= $count ?> <?= $count === 1 ? 'beach' : 'beaches' ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Trending Now - Horizontal Carousel -->
<?php
$trendingBeaches = getTrendingBeaches(8);
        $showDiscovery = !empty($selectedTags) || !empty($selectedMunicipality) || $hasLifeguard ? false : true;
?>
<?php if ($showDiscovery && !empty($trendingBeaches)): ?>
<section class="py-12 md:py-16 pl-4 sm:pl-6 md:pl-20 bg-sand-50">
    <!-- Section Header -->
    <div class="flex justify-between items-center pr-4 sm:pr-6 md:pr-20 mb-6 md:mb-8">
        <h2 class="text-[28px] font-serif font-normal text-warm-900"><?= h(__('pages.home.trending_now')) ?></h2>
        <div class="flex gap-2">
            <button data-action="scrollCarousel" data-action-args='["trending",-1]' class="w-10 h-10 rounded-full bg-white hover:bg-warm-50 border border-warm-200 flex items-center justify-center text-warm-700 transition-colors" aria-label="<?= h(__('common.previous')) ?>">
                <i data-lucide="chevron-left" class="w-5 h-5" aria-hidden="true"></i>
            </button>
            <button data-action="scrollCarousel" data-action-args='["trending",1]' class="w-10 h-10 rounded-full bg-white hover:bg-warm-50 border border-warm-200 flex items-center justify-center text-warm-700 transition-colors" aria-label="<?= h(__('common.next')) ?>">
                <i data-lucide="chevron-right" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Carousel -->
    <div id="trending-carousel" class="flex overflow-x-auto gap-4 snap-x snap-mandatory hide-scrollbar pb-4">
        <?php foreach ($trendingBeaches as $i => $tb): ?>
        <a href="/beach/<?= h($tb['slug']) ?>"
           class="w-[280px] snap-start relative group rounded-xl overflow-hidden flex-shrink-0">
            <!-- Image -->
            <div class="relative overflow-hidden" style="height: 373px">
                <img src="<?= h(getThumbnailUrl($tb['cover_image'])) ?>"
                     alt="<?= h($tb['name']) ?>"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                     loading="lazy">

                <!-- Gradient overlay -->
                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/80 to-transparent"></div>

                <!-- Trending badge -->
                <div class="absolute top-3 left-3 z-20 bg-black/40 backdrop-blur-md rounded-full px-3 py-1 text-xs text-white font-medium">
                    #<?= $i + 1 ?> Trending
                </div>

                <!-- Bottom content -->
                <div class="absolute bottom-0 left-0 w-full p-4 z-20">
                    <div class="text-[11px] text-white/70 uppercase tracking-wider mb-1"><?= h($tb['municipality']) ?></div>
                    <h3 class="text-xl font-bold text-white"><?= h($tb['name']) ?></h3>

                    <?php if ($tb['google_rating']): ?>
                    <div class="flex items-center gap-1.5 mt-2 text-[13px]">
                        <span class="text-yellow-400">★</span>
                        <span class="text-white font-medium"><?= number_format($tb['google_rating'], 1) ?></span>
                        <?php if ($tb['google_review_count']): ?>
                        <span class="text-white/50">(<?= number_format($tb['google_review_count']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<script <?= cspNonceAttr() ?>>
function scrollCarousel(id, direction) {
    const carousel = document.getElementById(id + '-carousel');
    if (carousel) {
        const scrollAmount = carousel.offsetWidth * 0.8;
        carousel.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
    }
}
</script>
<?php endif; ?>

<!-- Main Content -->
<section id="beaches" class="py-12 md:py-16 bg-sand-50 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filters -->
        <?php
        $locationEnabled = false; // Set by JS when location is granted
        $maxDistance = 50;
        include APP_ROOT . '/components/filters.php';
        ?>

        <!-- List View -->
        <div id="list-view" class="<?= $viewMode === 'list' ? '' : 'hidden' ?>">
            <?php
            $userLocation = null; // Set by JS when location is granted
            include APP_ROOT . '/components/beach-grid.php';
            ?>

            <!-- Load More / Pagination -->
            <?php if ($totalPages > 1 && $page < $totalPages):
                $apiParams = array_filter([
                    'tags' => $selectedTags ?: null,
                    'municipality' => $selectedMunicipality ?: null,
                    'q' => $searchQuery ?: null,
                    'sort' => $sortBy !== 'name' ? $sortBy : null,
                    'has_lifeguard' => $hasLifeguard ? '1' : null,
                    'page' => $page + 1
                ]);
            ?>
            <div id="load-more-container" class="text-center mt-8">
                <button id="load-more-btn"
                        hx-get="/api/beaches.php?<?= http_build_query($apiParams) ?>"
                        hx-target="#beach-grid"
                        hx-swap="beforeend"
                        class="bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-medium transition-colors">
                    <?= h(__('pages.home.load_more')) ?>
                    <span class="htmx-indicator ml-2">...</span>
                </button>
                <p class="text-sm text-white/60 mt-2">
                    <?= h(__('pages.home.showing_count', ['shown' => min($page * $perPage, $totalBeaches), 'total' => $totalBeaches])) ?>
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
<div id="beach-drawer" class="drawer-overlay" role="dialog" aria-modal="true" aria-label="Beach details" data-action="closeBeachDrawer" data-action-args='["__event__"]'>
    <div class="drawer-content" data-action-stop data-action="noop" data-on="click">
        <div id="drawer-content-inner">
            <!-- Content loaded via HTMX -->
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold"><?= h(__('pages.home.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="<?= h(__('common.close')) ?>">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="share-modal-body">
            <!-- Content set by JS -->
        </div>
    </div>
</div>

<!-- Quiz CTA Section -->
<section id="experiences" class="py-16 md:py-24 px-4 sm:px-6 md:px-20 text-center bg-white">
    <div class="max-w-4xl mx-auto border border-ocean-200 rounded-3xl p-8 md:p-12 bg-white shadow-card">
        <h3 class="text-3xl md:text-4xl font-serif italic text-warm-900 mb-6">
            <?= h(__('pages.home.quiz_headline')) ?>
        </h3>
        <p class="text-warm-500 mb-8 text-base md:text-lg max-w-2xl mx-auto">
            <?= h(__('pages.home.quiz_subtitle')) ?>
        </p>
        <a href="/quiz" class="inline-block px-8 md:px-12 py-3 md:py-4 bg-sunset-400 text-ocean-900 font-bold rounded-full hover:scale-105 transition-transform">
            <?= h(__('pages.home.quiz_button')) ?>
        </a>
    </div>
</section>

<!-- Planning Resources -->
<section class="py-16 md:py-20 px-4 sm:px-6 bg-sand-100">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-[28px] font-serif font-normal text-warm-900"><?= h(__('pages.home.resources_heading')) ?></h2>
                <p class="text-[15px] text-warm-500 mt-1"><?= h(__('pages.home.resources_subtitle')) ?></p>
            </div>
            <a href="/guides" class="hidden sm:inline-flex items-center gap-1 text-ocean-600 hover:text-ocean-700 font-medium transition-colors">
                <?= h(__('pages.home.resources_all_guides')) ?>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php
            $guideCards = [
                ['slug' => 'snorkeling-guide',              'type' => 'Guide',    'emoji' => '🤿', 'title' => 'Best Snorkeling Spots & What to Bring',  'desc' => 'Crystal-clear waters, vibrant reefs, and the gear you need.'],
                ['slug' => 'surfing-guide',                 'type' => 'Guide',    'emoji' => '🏄‍♂️', 'title' => 'Surfing Puerto Rico: A Complete Guide',  'desc' => 'From beginner breaks to world-class waves across the island.'],
                ['slug' => 'family-beach-vacation-planning', 'type' => 'Planning', 'emoji' => '👨‍👩‍👧', 'title' => 'Family Beach Vacation Planning',        'desc' => 'Kid-friendly beaches, safety tips, and what to pack.'],
                ['slug' => 'beach-safety-tips',             'type' => 'Safety',   'emoji' => '🛟', 'title' => 'Beach Safety Tips for Visitors',          'desc' => 'Currents, sun protection, and local safety guidelines.'],
            ];
            foreach ($guideCards as $guide):
                $typeColors = match($guide['type']) {
                    'Guide'    => 'bg-ocean-50 text-ocean-700',
                    'Planning' => 'bg-amber-50 text-amber-700',
                    'Safety'   => 'bg-rose-50 text-rose-700',
                    default    => 'bg-warm-100 text-warm-600',
                };
            ?>
            <a href="/guides/<?= h($guide['slug']) ?>"
               class="flex flex-col bg-white rounded-xl border border-warm-200 hover:shadow-lg hover:border-ocean-300 transition-all overflow-hidden group">
                <div class="p-5 flex flex-col flex-1">
                    <span class="inline-flex self-start items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium <?= $typeColors ?> mb-3">
                        <?= $guide['emoji'] ?> <?= h($guide['type']) ?>
                    </span>
                    <h3 class="font-semibold text-warm-900 mb-2 group-hover:text-ocean-700 transition-colors"><?= h($guide['title']) ?></h3>
                    <p class="text-sm text-warm-500 flex-1"><?= h($guide['desc']) ?></p>
                    <span class="mt-4 text-sm font-medium text-ocean-600 group-hover:text-ocean-700 inline-flex items-center gap-1 transition-colors">
                        <?= h(__('pages.home.resources_read_guide')) ?>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <a href="/guides" class="sm:hidden mt-6 inline-flex items-center gap-1 text-ocean-600 hover:text-ocean-700 font-medium transition-colors">
            <?= h(__('pages.home.resources_all_guides')) ?>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>
</section>

<!-- Pass data to JavaScript (beaches lazy-loaded for performance) -->
<script <?= cspNonceAttr() ?>>
window.BeachFinder = {
    beaches: [],
    beachesLoaded: false,
    selectedTags: <?= json_encode($selectedTags) ?>,
    selectedMunicipality: <?= json_encode($selectedMunicipality) ?>,
    selectedCollection: <?= json_encode($selectedCollection) ?>,
    includeAll: <?= $includeAll ? 'true' : 'false' ?>,
    hasLifeguard: <?= $hasLifeguard ? 'true' : 'false' ?>,
    searchQuery: <?= json_encode($searchQuery) ?>,
    sortBy: <?= json_encode($sortBy) ?>,
    viewMode: <?= json_encode($viewMode) ?>,
    userFavorites: <?= json_encode($userFavorites) ?>,
    isAuthenticated: <?= isAuthenticated() ? 'true' : 'false' ?>,
    csrfToken: <?= json_encode(csrfToken()) ?>,
    mapCenter: <?= json_encode(getPRCenter()) ?>,
    totalBeaches: <?= $totalBeaches ?>,
    tagLabels: <?= json_encode(array_combine(TAGS, array_map('getTagLabel', TAGS))) ?>,
    hasActiveFilters: <?= (!empty($selectedTags) || !empty($selectedMunicipality) || !empty($searchQuery) || !empty($selectedCollection) || $includeAll || $hasLifeguard) ? 'true' : 'false' ?>,
    loadBeaches: function() {
        if (this.beachesLoaded || this._loading) return Promise.resolve(this.beaches);
        this._loading = true;
        const mapParams = new URLSearchParams(window.location.search);
        mapParams.delete('view');
        mapParams.delete('page');
        const mapUrl = '/api/beaches-map.php' + (mapParams.toString() ? '?' + mapParams.toString() : '');
        return fetch(mapUrl)
            .then(r => r.json())
            .then(data => {
                this.beaches = data.beaches || [];
                this.beachesLoaded = true;
                this._loading = false;
                if (typeof state !== 'undefined') {
                    state.beaches = this.beaches;
                    state.filteredBeaches = [...this.beaches];
                }
                return this.beaches;
            })
            .catch(err => {
                console.warn('Failed to load beach data:', err);
                this._loading = false;
                return [];
            });
    }
};

// Auto-scroll to results when filters are active
(function() {
    function scrollToResults() {
        const beachesSection = document.getElementById('beaches');
        if (beachesSection) {
            beachesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // On page load: scroll if filters are active or hash is #beaches
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (window.BeachFinder.hasActiveFilters || window.location.hash === '#beaches' || urlParams.get('view') === 'map') {
            // Small delay to ensure page is rendered
            setTimeout(scrollToResults, 100);
        }
    });

    // After HTMX swaps beach grid (filter applied via HTMX)
    document.body.addEventListener('htmx:afterSwap', function(e) {
        if (e.detail.target?.id === 'beach-grid') {
            scrollToResults();
        }
    });
})();
</script>

<?php
// Extra scripts for map
$extraScripts = '<script defer src="/assets/js/map.js?v=2.3" ' . cspNonceAttr() . '></script>';
include APP_ROOT . '/components/footer.php';
?>
