<?php
/**
 * Best Family Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: family beaches puerto rico, kid-friendly beaches puerto rico
 *
 * Editorially ranked top 10 (collection_curated) plus natural pools, local
 * picks, and safety guidance. Prices/hours in the copy drift — re-verify
 * quarterly (parking fees, balneario hours, ferry logistics).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$lang = getCurrentLanguage();

// Page metadata
$pageTitle = __('pages.best_family_beaches.title');
$pageDescription = __('pages.best_family_beaches.description');
$canonicalUrl = absoluteUrl('/best-family-beaches');

$collectionKey = 'best-family-beaches';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$familyBeaches = $collectionData['beaches'];

// The editorial top 10 ignores user filters so the ranked write-ups stay stable.
$editorialBeaches = $collectionData['beaches'];
if (collectionHasUserFilters($collectionState) || !empty($collectionState['include_all'])) {
    $editorialData = fetchCollectionBeaches($collectionKey, []);
    $editorialBeaches = $editorialData['beaches'];
}

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Natural pools and local picks referenced below; link only slugs that exist.
$poolSlugs = ['montones-beach', 'pozo-teodoro', 'poza-del-obispo'];
$moreSlugs = [
    'combate-beach', 'caracas-beach', 'balneario-cerro-gordo', 'playa-dona-lala-beach',
    'la-posita-pinones', 'vacia-talega', 'playa-punta-caracoles', 'isla-verde-beach', 'ojo-de-agua-beach',
];
$linkableSlugs = [];
$slugPlaceholders = [];
$slugParams = [];
foreach (array_merge($poolSlugs, $moreSlugs) as $idx => $slug) {
    $slugPlaceholders[] = ':slug_' . $idx;
    $slugParams[':slug_' . $idx] = $slug;
}
$linkableRows = query(
    'SELECT slug FROM beaches WHERE publish_status = "published" AND slug IN (' . implode(', ', $slugPlaceholders) . ')',
    $slugParams
) ?: [];
$linkableSlugs = array_column($linkableRows, 'slug');

$guideEntryUrl = function (string $slug) use ($lang, $linkableSlugs): ?string {
    if (!in_array($slug, $linkableSlugs, true)) {
        return null;
    }
    return routeUrl('beach_detail', $lang, ['slug' => $slug]);
};

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-family-beaches',
    $editorialBeaches[0]['cover_image'] ?? null,
    '2026-01-01',
    '2026-08-21'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $editorialBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [];
for ($i = 1; $i <= 8; $i++) {
    $pageFaqs[] = [
        'question' => __('pages.best_family_beaches.faq_' . $i . '_q'),
        'answer' => __('pages.best_family_beaches.faq_' . $i . '_a')
    ];
}
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('footer.best_beaches'), 'url' => routeUrl('best_beaches', $lang)],
    ['name' => __('footer.family_beaches')]
];

$bodyVariant = 'collection-dark';
$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>


<!-- Quick Navigation -->
<section class="collection-content-nav bg-white border border-warm-200">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-warm-500"><?= h(__('pages.best_family_beaches.jump_to')) ?></span>
            <a href="#top-10" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_top_beaches')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#natural-pools" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_pools')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#more-spots" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_more')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#safety" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_safety')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#faq" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_faq')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#map" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_map')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none prose-brand">
            <p><?= __('pages.best_family_beaches.intro_p1') ?></p>

            <p><?= __('pages.best_family_beaches.intro_p2') ?></p>

            <p><?= __('pages.best_family_beaches.intro_p3') ?></p>
        </div>
    </div>
</section>

<!-- How We Rank -->
<section class="py-12 bg-white border-y border-warm-200">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-4 text-center">
            <?= h(__('pages.best_family_beaches.ranking_title')) ?>
        </h2>
        <div class="prose prose-lg max-w-none prose-brand">
            <p><?= __('pages.best_family_beaches.ranking_body') ?></p>
        </div>
    </div>
</section>

<!-- Top 10 Editorial List -->
<section id="top-10" class="py-12 scroll-mt-24">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.top_title')) ?>
        </h2>
        <div class="space-y-10">
            <?php $topRank = 0; foreach ($editorialBeaches as $topBeach):
                $topKey = 'pages.best_family_beaches.top.' . $topBeach['slug'];
                $topBody = __($topKey . '.body');
                if ($topBody === $topKey . '.body') { continue; }
                $topRank++;
                $topLabel = __($topKey . '.label');
                if ($topLabel === $topKey . '.label') { $topLabel = $topBeach['name']; }
                $topRegion = __($topKey . '.region');
                $topLifeguards = __($topKey . '.lifeguards');
                $topFacilities = __($topKey . '.facilities');
                $topWatch = __($topKey . '.watch');
            ?>
            <div class="border-l-4 border-ocean-200 pl-5">
                <h3 class="text-xl font-bold text-warm-900 mb-1">
                    <span class="text-ocean-500"><?= $topRank ?>.</span>
                    <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $topBeach['slug']])) ?>" class="hover:text-ocean-500"><?= h($topLabel) ?></a>
                    <span class="text-warm-500 font-normal text-base">· <?= h($topBeach['municipality']) ?></span>
                </h3>
                <?php if ($topRegion !== $topKey . '.region'): ?>
                <p class="text-sm text-warm-500 mb-3"><?= h($topRegion) ?></p>
                <?php endif; ?>
                <p class="text-warm-700"><?= $topBody ?></p>
                <p class="text-sm text-warm-700 mt-3">
                    <strong><?= h(__('pages.best_family_beaches.label_lifeguards')) ?>:</strong> <?= $topLifeguards ?>
                    <span class="text-warm-300 mx-1">·</span>
                    <strong><?= h(__('pages.best_family_beaches.label_facilities')) ?>:</strong> <?= $topFacilities ?>
                </p>
                <p class="text-sm text-amber-700 mt-1">
                    <strong><?= h(__('pages.best_family_beaches.label_watch')) ?>:</strong> <?= $topWatch ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Natural Pools -->
<section id="natural-pools" class="py-12 bg-white border-y border-warm-200 scroll-mt-24">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-4 text-center">
            <?= h(__('pages.best_family_beaches.pools_title')) ?>
        </h2>
        <div class="prose prose-lg max-w-none prose-brand mb-8">
            <p><?= __('pages.best_family_beaches.pools_intro') ?></p>
        </div>
        <div class="space-y-8">
            <?php foreach ($poolSlugs as $poolSlug):
                $poolKey = 'pages.best_family_beaches.pools.' . $poolSlug;
                $poolBody = __($poolKey . '.body');
                if ($poolBody === $poolKey . '.body') { continue; }
                $poolUrl = $guideEntryUrl($poolSlug);
                $poolFacilities = __($poolKey . '.facilities');
            ?>
            <div class="border-l-4 border-ocean-200 pl-5">
                <h3 class="text-xl font-bold text-warm-900 mb-1">
                    <?php if ($poolUrl !== null): ?>
                    <a href="<?= h($poolUrl) ?>" class="hover:text-ocean-500"><?= h(__($poolKey . '.label')) ?></a>
                    <?php else: ?>
                    <?= h(__($poolKey . '.label')) ?>
                    <?php endif; ?>
                    <span class="text-warm-500 font-normal text-base">· <?= h(__($poolKey . '.region')) ?></span>
                </h3>
                <p class="text-warm-700"><?= $poolBody ?></p>
                <p class="text-sm text-warm-700 mt-3">
                    <strong><?= h(__('pages.best_family_beaches.label_lifeguards')) ?>:</strong> <?= __($poolKey . '.lifeguards') ?>
                    <?php if ($poolFacilities !== $poolKey . '.facilities'): ?>
                    <span class="text-warm-300 mx-1">·</span>
                    <strong><?= h(__('pages.best_family_beaches.label_facilities')) ?>:</strong> <?= $poolFacilities ?>
                    <?php endif; ?>
                </p>
                <p class="text-sm text-amber-700 mt-1">
                    <strong><?= h(__('pages.best_family_beaches.label_watch')) ?>:</strong> <?= __($poolKey . '.watch') ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- More Local Spots -->
<section id="more-spots" class="py-12 scroll-mt-24">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.more_title')) ?>
        </h2>
        <div class="space-y-6">
            <?php foreach ($moreSlugs as $moreSlug):
                $moreKey = 'pages.best_family_beaches.more.' . $moreSlug;
                $moreBody = __($moreKey . '.body');
                if ($moreBody === $moreKey . '.body') { continue; }
                $moreUrl = $guideEntryUrl($moreSlug);
            ?>
            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <h3 class="text-lg font-bold text-warm-900 mb-2">
                    <?php if ($moreUrl !== null): ?>
                    <a href="<?= h($moreUrl) ?>" class="hover:text-ocean-500"><?= h(__($moreKey . '.label')) ?></a>
                    <?php else: ?>
                    <?= h(__($moreKey . '.label')) ?>
                    <?php endif; ?>
                    <span class="text-warm-500 font-normal text-base">· <?= h(__($moreKey . '.region')) ?></span>
                </h3>
                <p class="text-warm-700 text-sm"><?= $moreBody ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Safety Rules -->
<section id="safety" class="py-12 bg-white border-y border-warm-200 scroll-mt-24">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.safety_title')) ?>
        </h2>
        <div class="space-y-6">
            <?php for ($i = 1; $i <= 7; $i++): ?>
            <div>
                <h3 class="text-lg font-bold text-warm-900 mb-1"><?= h(__('pages.best_family_beaches.safety_' . $i . '_title')) ?></h3>
                <p class="text-warm-700"><?= __('pages.best_family_beaches.safety_' . $i . '_body') ?></p>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12 scroll-mt-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.faq_title')) ?>
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white border border-warm-200 rounded-lg shadow-card group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-warm-900">
                    <?= h($faq['question']) ?>
                    <span class="text-ocean-500 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-warm-700">
                    <?= h($faq['answer']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<section id="map" class="py-12 scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-family-beaches#top-beaches" class="inline-flex items-center gap-2 bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span><?= h(__('pages.best_family_beaches.map_button')) ?></span>
            </a>
            <p class="text-warm-500 mt-4"><?= h(__('pages.best_family_beaches.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-ocean-50 border border-ocean-200 text-ocean-500">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.best_family_beaches.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.best_family_beaches.cta_desc')) ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-sunset-400 text-ocean-900 hover:bg-sunset-300 px-8 py-3 rounded-lg font-semibold transition-colors">
            <?= h(__('pages.common.take_quiz')) ?>
        </a>
    </div>
</section>


<?php
$skipAppScripts = true;
$extraScripts = '<script defer src="/assets/js/collection-explorer.min.js?v=2.0" ' . cspNonceAttr() . '></script>';
?>
<?php include APP_ROOT . '/components/footer.php'; ?>
