<?php
/**
 * Best Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: best beaches puerto rico, top beaches puerto rico
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
$pageTitle = __('pages.best_beaches.title');
$pageDescription = __('pages.best_beaches.description');
$canonicalUrl = absoluteUrl('/best-beaches');

$collectionKey = 'best-beaches';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$topBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-beaches',
    $topBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $topBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => __('pages.best_beaches.faq_1_q'),
        'answer' => __('pages.best_beaches.faq_1_a')
    ],
    [
        'question' => __('pages.best_beaches.faq_2_q'),
        'answer' => __('pages.best_beaches.faq_2_a')
    ],
    [
        'question' => __('pages.best_beaches.faq_3_q'),
        'answer' => __('pages.best_beaches.faq_3_a')
    ],
    [
        'question' => __('pages.best_beaches.faq_4_q'),
        'answer' => __('pages.best_beaches.faq_4_a')
    ],
    [
        'question' => __('pages.best_beaches.faq_5_q'),
        'answer' => __('pages.best_beaches.faq_5_a')
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('pages.best_beaches.breadcrumb')]
];

$bodyVariant = 'collection-dark';
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>


<!-- Quick Navigation -->
<section class="collection-content-nav bg-white/5 border border-white/10">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-slate-400"><?= h(__('pages.best_beaches.jump_to')) ?></span>
            <a href="#top-beaches" class="text-yellow-300 hover:underline"><?= h(__('pages.best_beaches.jump_top_list')) ?></a>
            <span class="text-white/20">|</span>
            <a href="#by-activity" class="text-yellow-300 hover:underline"><?= h(__('pages.best_beaches.jump_by_activity')) ?></a>
            <span class="text-white/20">|</span>
            <a href="#faq" class="text-yellow-300 hover:underline"><?= h(__('pages.best_beaches.jump_faq')) ?></a>
            <span class="text-white/20">|</span>
            <a href="#map" class="text-yellow-300 hover:underline"><?= h(__('pages.best_beaches.jump_map')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none prose-brand">
            <p><?= __('pages.best_beaches.intro_p1') ?></p>

            <p><?= __('pages.best_beaches.intro_p2') ?></p>
        </div>
    </div>
</section>

<!-- Beaches by Activity -->
<section id="by-activity" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-100 mb-8 text-center">
            <?= h(__('pages.best_beaches.by_activity_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="<?= h(routeUrl('best_snorkeling_beaches', $lang)) ?>" class="bg-white/5 border border-white/10 rounded-xl p-6 shadow-glass hover:shadow-glass transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-slate-100 group-hover:text-yellow-300"><?= h(__('pages.best_snorkeling.card_title')) ?></h3>
                <p class="text-slate-400 text-sm mt-2"><?= h(__('pages.best_snorkeling.card_desc')) ?></p>
            </a>

            <a href="<?= h(routeUrl('best_surfing_beaches', $lang)) ?>" class="bg-white/5 border border-white/10 rounded-xl p-6 shadow-glass hover:shadow-glass transition-shadow group">
                <div class="text-4xl mb-4">🏄</div>
                <h3 class="text-lg font-bold text-slate-100 group-hover:text-yellow-300"><?= h(__('pages.best_surfing.card_title')) ?></h3>
                <p class="text-slate-400 text-sm mt-2"><?= h(__('pages.best_surfing.card_desc')) ?></p>
            </a>

            <a href="<?= h(routeUrl('best_family_beaches', $lang)) ?>" class="bg-white/5 border border-white/10 rounded-xl p-6 shadow-glass hover:shadow-glass transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-slate-100 group-hover:text-yellow-300"><?= h(__('pages.best_family.card_title')) ?></h3>
                <p class="text-slate-400 text-sm mt-2"><?= h(__('pages.best_family.card_desc')) ?></p>
            </a>

            <a href="<?= h(routeUrl('beaches_near_san_juan', $lang)) ?>" class="bg-white/5 border border-white/10 rounded-xl p-6 shadow-glass hover:shadow-glass transition-shadow group">
                <div class="text-4xl mb-4">🏙️</div>
                <h3 class="text-lg font-bold text-slate-100 group-hover:text-yellow-300"><?= h(__('pages.beaches_near_san_juan.card_title')) ?></h3>
                <p class="text-slate-400 text-sm mt-2"><?= h(__('pages.beaches_near_san_juan.card_desc')) ?></p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-100 mb-8 text-center">
            <?= h(__('pages.best_beaches.faq_title')) ?>
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white/5 border border-white/10 rounded-lg shadow-glass group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-slate-100">
                    <?= h($faq['question']) ?>
                    <span class="text-yellow-300 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-slate-300">
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
        <h2 class="text-2xl md:text-3xl font-bold text-slate-100 mb-8 text-center">
            <?= h(__('pages.best_beaches.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-beaches#top-beaches" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span><?= h(__('pages.best_beaches.map_button')) ?></span>
            </a>
            <p class="text-slate-400 mt-4"><?= h(__('pages.best_beaches.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-yellow-400/[0.18] border border-yellow-400/[0.38] text-yellow-300">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.best_beaches.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.best_beaches.cta_desc')) ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-brand-yellow text-brand-darker hover:bg-white/10 px-8 py-3 rounded-lg font-semibold transition-colors">
            <?= h(__('pages.common.take_quiz')) ?>
        </a>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>


<?php
$skipAppScripts = true;
$extraScripts = '<script defer src="/assets/js/collection-explorer.min.js?v=2.0" ' . cspNonceAttr() . '></script>';
?>
<?php include APP_ROOT . '/components/footer.php'; ?>
