<?php
/**
 * Best Family Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: family beaches puerto rico, kid-friendly beaches puerto rico
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

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-family-beaches',
    $familyBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $familyBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => __('pages.best_family_beaches.faq_1_q'),
        'answer' => __('pages.best_family_beaches.faq_1_a')
    ],
    [
        'question' => __('pages.best_family_beaches.faq_2_q'),
        'answer' => __('pages.best_family_beaches.faq_2_a')
    ],
    [
        'question' => __('pages.best_family_beaches.faq_3_q'),
        'answer' => __('pages.best_family_beaches.faq_3_a')
    ],
    [
        'question' => __('pages.best_family_beaches.faq_4_q'),
        'answer' => __('pages.best_family_beaches.faq_4_a')
    ],
    [
        'question' => __('pages.best_family_beaches.faq_5_q'),
        'answer' => __('pages.best_family_beaches.faq_5_a')
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('footer.best_beaches'), 'url' => routeUrl('best_beaches', $lang)],
    ['name' => __('footer.family_beaches')]
];

$bodyVariant = 'collection-light';
$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>


<!-- Quick Navigation -->
<section class="collection-content-nav bg-white border border-warm-200">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-warm-500"><?= h(__('pages.best_family_beaches.jump_to')) ?></span>
            <a href="#top-beaches" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_top_beaches')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#tips" class="text-ocean-500 hover:underline"><?= h(__('pages.best_family_beaches.jump_tips')) ?></a>
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
        </div>
    </div>
</section>

<!-- Family Beach Tips -->
<section id="tips" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.tips_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">🕘</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_1_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_1_desc')) ?></p>
            </div>

            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">🧴</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_2_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_2_desc')) ?></p>
            </div>

            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">👟</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_3_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_3_desc')) ?></p>
            </div>

            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">⛱️</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_4_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_4_desc')) ?></p>
            </div>

            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">🥤</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_5_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_5_desc')) ?></p>
            </div>

            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4">🏊</div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__('pages.best_family_beaches.tip_6_title')) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__('pages.best_family_beaches.tip_6_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- What to Look For -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_family_beaches.features_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-warm-900 mb-4"><?= h(__('pages.best_family_beaches.must_have_title')) ?></h3>
                <ul class="text-warm-700 space-y-3">
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">✓</span>
                        <span><?= __('pages.best_family_beaches.must_have_1') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">✓</span>
                        <span><?= __('pages.best_family_beaches.must_have_2') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">✓</span>
                        <span><?= __('pages.best_family_beaches.must_have_3') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">✓</span>
                        <span><?= __('pages.best_family_beaches.must_have_4') ?></span>
                    </li>
                </ul>
            </div>

            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-warm-900 mb-4"><?= h(__('pages.best_family_beaches.nice_to_have_title')) ?></h3>
                <ul class="text-warm-700 space-y-3">
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">★</span>
                        <span><?= __('pages.best_family_beaches.nice_to_have_1') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">★</span>
                        <span><?= __('pages.best_family_beaches.nice_to_have_2') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">★</span>
                        <span><?= __('pages.best_family_beaches.nice_to_have_3') ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-600">★</span>
                        <span><?= __('pages.best_family_beaches.nice_to_have_4') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12">
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
<section id="map" class="py-12">
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
