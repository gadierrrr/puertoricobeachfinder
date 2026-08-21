<?php
/**
 * Best Family Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: family beaches puerto rico, kid-friendly beaches puerto rico
 *
 * Guide template: the curated top 10 renders as editorial guide cards inside
 * the collection explorer (see collection/card.php + lead-best-family-beaches),
 * followed by natural pools, local picks, and safety guidance. Prices/hours in
 * the copy drift — re-verify quarterly (parking fees, balneario hours, ferry).
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

// Structured data reflects the editorial top 10 regardless of user filters.
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

// Resolve a beach slug to its canonical published slug, following merge
// redirects (e.g. escambron-beach -> balneario-el-escambron on merged DBs).
$resolveGuideSlug = function (string $slug): ?string {
    $row = queryOne('SELECT slug FROM beaches WHERE slug = :slug AND publish_status = "published"', [':slug' => $slug]);
    if ($row) {
        return (string) $row['slug'];
    }
    $row = queryOne(
        'SELECT b.slug FROM beach_slug_redirects r
         JOIN beaches b ON b.id = r.beach_id
         WHERE r.old_slug = :slug AND b.publish_status = "published"',
        [':slug' => $slug]
    );
    return $row ? (string) $row['slug'] : null;
};
$guideEntryUrl = function (string $slug) use ($lang, $resolveGuideSlug): ?string {
    $canonical = $resolveGuideSlug($slug);
    return $canonical === null ? null : routeUrl('beach_detail', $lang, ['slug' => $canonical]);
};

// Natural pools and local picks (i18n entries keyed by these slugs)
$poolSlugs = ['montones-beach', 'pozo-teodoro', 'poza-del-obispo'];
$moreSlugs = [
    'combate-beach', 'caracas-beach', 'balneario-cerro-gordo', 'playa-dona-lala-beach',
    'la-posita-pinones', 'vacia-talega', 'playa-punta-caracoles', 'isla-verde-beach', 'ojo-de-agua-beach',
];

// Condado-warning alternatives (proper names, same in EN/ES)
$condadoAlternatives = [
    'playita-del-condado' => 'Playita del Condado',
    'escambron-beach' => 'Balneario El Escambrón',
    'balneario-de-carolina' => 'Balneario de Carolina',
];

// Safety rules: the Condado warning (5) renders as the callout; the rest as cards.
$safetyRuleEmojis = [1 => '🚩', 2 => '🌊', 3 => '📅', 4 => '🛟', 6 => '🕘', 7 => '🎒'];

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
<?php
$collectionLeadInclude = APP_ROOT . '/components/collection/lead-best-family-beaches.php';
include APP_ROOT . '/components/collection/explorer.php';
?>


<!-- Natural Pools -->
<section id="natural-pools" class="py-12 bg-white border-y border-warm-200 scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-4 text-center">
            <?= h(__('pages.best_family_beaches.pools_title')) ?>
        </h2>
        <div class="prose prose-lg max-w-3xl mx-auto prose-brand mb-8">
            <p><?= __('pages.best_family_beaches.pools_intro') ?></p>
        </div>
        <div class="guide-pool-grid">
            <?php foreach ($poolSlugs as $poolSlug):
                $poolKey = 'pages.best_family_beaches.pools.' . $poolSlug;
                $poolBody = __($poolKey . '.body');
                if ($poolBody === $poolKey . '.body') { continue; }
                $poolUrl = $guideEntryUrl($poolSlug);
                $poolFacilities = __($poolKey . '.facilities');
            ?>
            <div class="guide-pool">
                <h3>
                    <?php if ($poolUrl !== null): ?>
                    <a href="<?= h($poolUrl) ?>"><?= h(__($poolKey . '.label')) ?></a>
                    <?php else: ?>
                    <?= h(__($poolKey . '.label')) ?>
                    <?php endif; ?>
                </h3>
                <p class="guide-pool__muni"><?= h(__($poolKey . '.region')) ?></p>
                <p class="guide-pool__body"><?= $poolBody ?></p>
                <?php if ($poolFacilities !== $poolKey . '.facilities'): ?>
                <p class="guide-pool__facilities"><strong><?= h(__('pages.best_family_beaches.label_facilities')) ?>:</strong> <?= $poolFacilities ?></p>
                <?php endif; ?>
                <p class="guide-pool__watch"><strong>⚠ <?= h(__('pages.best_family_beaches.label_watch')) ?>:</strong> <?= __($poolKey . '.watch') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- More Local Spots -->
<section id="more-spots" class="py-12 scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.more_title')) ?>
        </h2>
        <div class="guide-spot-grid">
            <?php foreach ($moreSlugs as $moreSlug):
                $moreKey = 'pages.best_family_beaches.more.' . $moreSlug;
                $moreBody = __($moreKey . '.body');
                if ($moreBody === $moreKey . '.body') { continue; }
                $moreUrl = $guideEntryUrl($moreSlug);
            ?>
            <div class="guide-spot<?= $moreSlug === 'ojo-de-agua-beach' ? ' guide-spot--fresh' : '' ?>">
                <h3>
                    <?php if ($moreUrl !== null): ?>
                    <a href="<?= h($moreUrl) ?>"><?= h(__($moreKey . '.label')) ?></a>
                    <?php else: ?>
                    <?= h(__($moreKey . '.label')) ?>
                    <?php endif; ?>
                    <span class="guide-spot__muni">· <?= h(__($moreKey . '.region')) ?></span>
                </h3>
                <p><?= $moreBody ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Safety Rules -->
<section id="safety" class="py-12 bg-white border-y border-warm-200 scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.safety_title')) ?>
        </h2>
        <div class="guide-safety-grid">
            <div class="guide-callout">
                <h3>⚠️ <?= h(__('pages.best_family_beaches.safety_5_title')) ?></h3>
                <p><?= __('pages.best_family_beaches.safety_5_body') ?></p>
                <p class="guide-callout__alts">
                    <?= h(__('pages.best_family_beaches.safety_swim_instead')) ?>
                    <?php $altLinks = [];
                    foreach ($condadoAlternatives as $altSlug => $altName) {
                        $altUrl = $guideEntryUrl($altSlug);
                        $altLinks[] = $altUrl !== null
                            ? '<a href="' . h($altUrl) . '">' . h($altName) . '</a>'
                            : h($altName);
                    }
                    echo implode(' · ', $altLinks); ?>
                </p>
            </div>
            <?php foreach ($safetyRuleEmojis as $ruleIndex => $ruleEmoji): ?>
            <div class="guide-rule">
                <h3><?= $ruleEmoji ?> <?= h(__('pages.best_family_beaches.safety_' . $ruleIndex . '_title')) ?></h3>
                <p><?= __('pages.best_family_beaches.safety_' . $ruleIndex . '_body') ?></p>
            </div>
            <?php endforeach; ?>
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
