<?php
/**
 * Individual Beach Detail Page
 * SEO-friendly full page for each beach
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
// Session started conditionally in header.php (only when cookie exists)
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/referrals.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$lang = getCurrentLanguage();

// Get slug from URL (set by Nginx rewrite or query param)
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(404);
    $pageTitle = __('errors.beach_not_found');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">' . h(__('errors.beach_not_found')) . '</h1>
            <p class="text-gray-600 mb-6">' . h(__('errors.beach_not_found_message')) . '</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                ' . h(__('errors.browse_all_beaches')) . '
            </a>
          </div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

// Fetch beach
$beach = queryOne('SELECT * FROM beaches WHERE slug = :slug AND publish_status = "published"', [':slug' => $slug]);

if (!$beach) {
    // Slug not found in beaches — check the redirect table for an old slug.
    $redirect = queryOne(
        'SELECT b.slug FROM beach_slug_redirects r
         JOIN beaches b ON b.id = r.beach_id
         WHERE r.old_slug = :slug AND b.publish_status = "published"',
        [':slug' => $slug]
    );
    if ($redirect) {
        $isSpanish = $lang === 'es' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/es/');
        $prefix    = $isSpanish ? '/es/playa/' : '/beach/';
        // Drop the 'slug' query param — it's the nginx-rewrite artifact, not a real
        // user param. Anything else (utm, ref, etc.) is preserved.
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qsParams);
        unset($qsParams['slug']);
        $qs = http_build_query($qsParams);
        header('Location: ' . $prefix . $redirect['slug'] . ($qs !== '' ? '?' . $qs : ''), true, 301);
        exit;
    }

    http_response_code(404);
    $pageTitle = __('errors.beach_not_found');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">' . h(__('errors.beach_not_found')) . '</h1>
            <p class="text-gray-600 mb-6">' . h(__('errors.beach_not_found_message')) . '</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                ' . h(__('errors.browse_all_beaches')) . '
            </a>
          </div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

// Fetch related data
$beach['tags'] = array_column(
    query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
    'tag'
);
$beach['amenities'] = array_column(
    query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
    'amenity'
);
$beach['gallery'] = array_column(
    query('SELECT image_url FROM beach_gallery WHERE beach_id = :id ORDER BY position', [':id' => $beach['id']]),
    'image_url'
);
$beach['features'] = query(
    'SELECT title, title_es, description, description_es FROM beach_features WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);
$beach['tips'] = query(
    'SELECT category, tip, tip_es FROM beach_tips WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);

// Get extended content sections
$extendedSections = query("
    SELECT section_type, heading, heading_es, content, content_es, display_order
    FROM beach_content_sections
    WHERE beach_id = :id AND status = 'published'
    ORDER BY display_order ASC
", [':id' => $beach['id']]);

// Fetch user reviews
$reviews = query("
    SELECT
        r.id, r.rating, r.title, r.review_text, r.visit_date, r.visit_type,
        r.helpful_count, r.created_at, r.would_recommend, r.user_id,
        u.name as user_name, u.avatar_url
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.beach_id = :id AND r.status = 'published'
    ORDER BY r.created_at DESC
", [':id' => $beach['id']]);

$userReviewCount = count($reviews);
$avgUserRating = $beach['avg_user_rating'] ?? null;

// Page metadata
$siteLabel = $lang === 'es' ? 'Playas de Puerto Rico' : 'Puerto Rico Beach Finder';

// Hand-written per-beach SEO title override takes precedence over the
// auto-generated title. When present it is rendered verbatim (header.php is
// told not to append the " | $appName" brand suffix), giving full control of
// the SERP title and length in both languages.
$seoTitleOverride = ($lang === 'es' && !empty($beach['seo_title_es']))
    ? $beach['seo_title_es']
    : (!empty($beach['seo_title']) ? $beach['seo_title'] : null);

if ($seoTitleOverride !== null) {
    $pageTitle = $seoTitleOverride;
    $pageTitleNoBrandSuffix = true;
} else {
    $pageTitle = $beach['name'] . ' - ' . $beach['municipality'];

    // Enrich title with top activities if total <title> stays under 65 chars
    // header.php appends " | $appName" so account for that suffix
    $titleSuffix = ' | ' . $siteLabel;
    $topTags = array_slice($beach['tags'] ?? [], 0, 2);
    if (!empty($topTags)) {
        $labels = array_map('getTagLabel', $topTags);
        $candidate = $beach['name'] . ' - ' . $beach['municipality'] . ' | ' . implode(', ', $labels);
        if (mb_strlen($candidate . $titleSuffix) <= 65) {
            $pageTitle = $candidate;
        }
    }
}

// Hand-written per-beach meta description override takes precedence and is
// rendered verbatim (no 155-char truncation — overrides are authored to length).
$seoDescOverride = ($lang === 'es' && !empty($beach['seo_description_es']))
    ? $beach['seo_description_es']
    : (!empty($beach['seo_description']) ? $beach['seo_description'] : null);

if ($seoDescOverride !== null) {
    $pageDescription = $seoDescOverride;
} else {
    $_descSource = ($lang === 'es' && !empty($beach['description_es']))
        ? $beach['description_es']
        : ($beach['description'] ?? '');
    $_descFallback = $lang === 'es'
        ? 'Descubre ' . $beach['name'] . ' en ' . $beach['municipality'] . ', Puerto Rico.'
        : 'Discover ' . $beach['name'] . ' in ' . $beach['municipality'] . ', Puerto Rico. View beach conditions, amenities, photos, and directions.';
    $pageDescription = $_descSource
        ? (mb_strlen($_descSource) > 155
            ? mb_substr($_descSource, 0, strrpos(mb_substr($_descSource, 0, 155), ' ') ?: 155) . '...'
            : $_descSource)
        : $_descFallback;
}

// Generate structured data using SEO component (consolidated with reviews)
$extraHead = beachSchema($beach, $reviews);

// Add TouristAttraction schema for travel queries
$extraHead .= touristAttractionSchema($beach);

// Add breadcrumbs
$municipalitySlug = strtolower(str_replace(' ', '-', $beach['municipality']));
$extraHead .= breadcrumbSchema([
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => $beach['municipality'], 'url' => routeUrl('municipality', $lang, ['municipality' => $municipalitySlug])],
    ['name' => $beach['name'], 'url' => routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])]
]);

// Generate dynamic FAQ schema
$faqs = generateBeachFAQs($beach);
$extraHead .= faqSchema($faqs);

// Add speakable schema for voice assistants
$extraHead .= speakableSchema();

// Set Open Graph image
$ogImage = $beach['cover_image'] ? absoluteUrl($beach['cover_image']) : null;

// Get WebP version of cover image for optimized delivery
$webpImage = getWebPImage($beach['cover_image'] ?? '');

$referralLocale = $lang === 'es' ? 'es' : 'en';
$referralBaseCtx = [
    'page_type' => 'beach',
    'page_slug' => (string) $beach['slug'],
];
$beachReferralHero = referralRenderBeachAnchor(
    (string) $beach['id'],
    'hero',
    $referralLocale,
    $referralBaseCtx
);
$beachReferralMid = referralRenderBeachAnchor(
    (string) $beach['id'],
    'mid_content',
    $referralLocale,
    $referralBaseCtx
);
$beachReferralBottom = referralRenderBeachAnchor(
    (string) $beach['id'],
    'bottom',
    $referralLocale,
    $referralBaseCtx
);

$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';

if ($redesignLayout) {
    include APP_ROOT . '/templates/redesign/beach.php';
    include APP_ROOT . '/components/footer.php';
    return;
}
?>


<?php include APP_ROOT . '/components/beach/hero.php'; ?>

<?php include APP_ROOT . '/components/beach/info-bar.php'; ?>

<?php include APP_ROOT . '/components/beach/section-nav.php'; ?>

    <!-- Two-Column Layout -->
    <div class="lg:flex lg:gap-8">

        <!-- Left Column: Main Content -->
        <div class="lg:w-[63%] space-y-8">

            <div id="section-overview" class="scroll-mt-[120px]">
                <?php include APP_ROOT . "/components/beach/at-a-glance.php"; ?>
            </div>
            <!-- Mobile Weather Strip (hidden on desktop) -->
            <div class="lg:hidden weather-strip"
                 data-lat="<?= h($beach['lat']) ?>" data-lng="<?= h($beach['lng']) ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="sun" class="w-6 h-6 text-sunset-400" aria-hidden="true"></i>
                        <div>
                            <div class="text-warm-900 font-semibold text-sm" id="weather-strip-verdict"><?= h($lang === "es" ? "Cargando…" : "Loading…") ?></div>
                            <div class="text-warm-400 text-xs" id="weather-strip-desc">&nbsp;</div>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-warm-900" id="weather-strip-temp">&mdash;</div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-warm-50 rounded-lg py-1.5 px-2 text-center">
                        <div class="text-[10px] text-warm-400"><?= h($lang === "es" ? "Viento" : "Wind") ?></div>
                        <div class="text-xs text-warm-900 font-medium" id="weather-strip-wind">&mdash;</div>
                    </div>
                    <div class="flex-1 bg-warm-50 rounded-lg py-1.5 px-2 text-center">
                        <div class="text-[10px] text-warm-400">UV</div>
                        <div class="text-xs font-medium" id="weather-strip-uv">&mdash;</div>
                    </div>
                    <div class="flex-1 bg-warm-50 rounded-lg py-1.5 px-2 text-center">
                        <div class="text-[10px] text-warm-400"><?= h($lang === "es" ? "Humedad" : "Humidity") ?></div>
                        <div class="text-xs text-warm-900 font-medium" id="weather-strip-humidity">&mdash;</div>
                    </div>
                    <a href="#section-map" class="flex-shrink-0 bg-sunset-400/10 border border-sunset-400/20 rounded-lg py-1.5 px-3 text-center hover:bg-sunset-400/20 transition-colors">
                        <div class="text-[10px] text-sunset-400/60"><?= h($lang === "es" ? "Ver" : "View") ?></div>
                        <div class="text-xs text-sunset-400 font-medium"><?= h($lang === "es" ? "Mapa" : "Map") ?></div>
                    </a>
                </div>
            </div>

            <?php include APP_ROOT . '/components/beach/about.php'; ?>

            <?php include APP_ROOT . '/components/beach/extended-sections.php'; ?>

            <?php include APP_ROOT . '/components/beach/tours.php'; ?>

            <?php include APP_ROOT . '/components/beach/local-listings.php'; ?>

            <?php $hasPhotos = !empty($beach['gallery']) || !empty($userPhotos ?? []); ?>
            <?php if ($hasPhotos): ?>
            <?php include APP_ROOT . '/components/beach/photos.php'; ?>
            <?php endif; ?>

            <?php if (!empty($reviews)): ?>
            <?php include APP_ROOT . '/components/beach/reviews.php'; ?>
            <?php endif; ?>

            <?php include APP_ROOT . '/components/beach/faq.php'; ?>

        </div>

        <?php include APP_ROOT . '/components/beach/sidebar.php'; ?>

    </div>

    <?php include APP_ROOT . '/components/beach/related.php'; ?>

</div>

<?php include APP_ROOT . '/components/beach/sticky-bar.php'; ?>

<?php include APP_ROOT . '/components/beach/modals.php'; ?>

<?php include APP_ROOT . '/components/beach/scripts.php'; ?>

<?php include APP_ROOT . '/components/footer.php'; ?>
