<?php
/**
 * best-beaches-cabo-rojo - SEO Landing Page
 * Auto-generated collection page
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
$pageTitle = __('pages.best_beaches_cabo_rojo.title');
$pageDescription = __('pages.best_beaches_cabo_rojo.description');
$canonicalUrl = absoluteUrl('/best-beaches-cabo-rojo');

$collectionKey = 'best-beaches-cabo-rojo';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$collectionBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-beaches-cabo-rojo',
    $collectionBeaches[0]['cover_image'] ?? null,
    '2026-04-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $collectionBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [];
for ($i = 1; $i <= 5; $i++) {
    $q = __("pages.best_beaches_cabo_rojo.faq_{$i}_q");
    $a = __("pages.best_beaches_cabo_rojo.faq_{$i}_a");
    if ($q && $a) $pageFaqs[] = ['question' => $q, 'answer' => $a];
}
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('footer.best_beaches'), 'url' => routeUrl('best_beaches', $lang)],
    ['name' => __('pages.best_beaches_cabo_rojo.breadcrumb')]
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
            <span class="text-warm-500"><?= h(__('pages.best_beaches_cabo_rojo.jump_to')) ?></span>
            <a href="#top-beaches" class="text-ocean-500 hover:underline"><?= h(__('pages.best_beaches_cabo_rojo.jump_top_spots')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#tips" class="text-ocean-500 hover:underline"><?= h(__('pages.best_beaches_cabo_rojo.jump_tips')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#faq" class="text-ocean-500 hover:underline"><?= h(__('pages.best_beaches_cabo_rojo.jump_faq')) ?></a>
            <span class="text-warm-300">|</span>
            <a href="#map" class="text-ocean-500 hover:underline"><?= h(__('pages.best_beaches_cabo_rojo.jump_map')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none prose-brand">
            <p><?= __('pages.best_beaches_cabo_rojo.intro_p1') ?></p>
            <p><?= __('pages.best_beaches_cabo_rojo.intro_p2') ?></p>
        </div>
    </div>
</section>

<!-- Tips -->
<section id="tips" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_beaches_cabo_rojo.tips_title')) ?>
        </h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="bg-white border border-warm-200 rounded-xl p-6 shadow-card">
                <div class="text-3xl mb-4"><?= h(__("pages.best_beaches_cabo_rojo.tip_{$i}_icon")) ?></div>
                <h3 class="text-lg font-bold text-warm-900 mb-2"><?= h(__("pages.best_beaches_cabo_rojo.tip_{$i}_title")) ?></h3>
                <p class="text-warm-500 text-sm"><?= h(__("pages.best_beaches_cabo_rojo.tip_{$i}_desc")) ?></p>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_beaches_cabo_rojo.faq_title')) ?>
        </h2>
        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white border border-warm-200 rounded-lg shadow-card group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-warm-900">
                    <?= h($faq['question']) ?>
                    <span class="text-ocean-500 group-open:rotate-180 transition-transform">&#9660;</span>
                </summary>
                <div class="px-6 pb-6 text-warm-700"><?= h($faq['answer']) ?></div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<section id="map" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-warm-900 mb-8 text-center">
            <?= h(__('pages.best_beaches_cabo_rojo.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-beaches-cabo-rojo#top-beaches" class="inline-flex items-center gap-2 bg-ocean-600 hover:bg-ocean-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span><?= h(__('pages.best_beaches_cabo_rojo.map_button')) ?></span>
            </a>
            <p class="text-warm-500 mt-4"><?= h(__('pages.best_beaches_cabo_rojo.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-ocean-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.best_beaches_cabo_rojo.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.best_beaches_cabo_rojo.cta_desc')) ?></p>
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
