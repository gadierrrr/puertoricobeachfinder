<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/guide_i18n.php';

$lang = getCurrentLanguage();

// Noindex Spanish guides until fully translated — remove when translation verified
// if ($lang === 'es') { $robotsOverride = 'noindex, nofollow'; }

$content = loadGuideContent('bioluminescent-bays');

$pageTitle = __('guide_bio_bays.title');
$pageDescription = __('guide_bio_bays.description');

$relatedGuides = [
    ['title' => __('related_guides.culebra_vieques'), 'url' => routeUrl('guide_culebra_vieques', $lang)],
    ['title' => __('related_guides.transport'),       'url' => routeUrl('guide_transportation', $lang)],
    ['title' => __('related_guides.best_time'),       'url' => routeUrl('guide_best_time', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_bio_bays.faq_{$i}_q"),
        'answer'   => __("guide_bio_bays.faq_{$i}_a"),
    ];
}

$guideUrl = routeUrl('guide_bio_bays', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_bio_bays.breadcrumb'),      'url' => $guideUrl],
]);

$pageTheme = "guide";
$skipMapCSS = true;
$skipMapScripts = true;
$redesignLayout = useRedesign();
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_bio_bays.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#overview" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_overview')) ?></a><a href="#mosquito-bay" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_mosquito_bay')) ?></a><a href="#laguna-grande" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_laguna_grande')) ?></a><a href="#la-parguera" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_la_parguera')) ?></a><a href="#comparison" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_comparison')) ?></a><a href="#tips" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_tips')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_bio_bays.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['overview'] ?? '' ?>
<?= $content['mosquito_bay'] ?? '' ?>
<?= $content['laguna_grande'] ?? '' ?>
<?= $content['la_parguera'] ?? '' ?>
<?= $content['comparison'] ?? '' ?>
<?= $content['tips'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_bio_bays.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_bio_bays.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>?municipality=Vieques" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"><?= h(__('guide_bio_bays.cta_button')) ?></a>
</div></div>
<?php $guideToursSlug = 'bioluminescent-bays'; include APP_ROOT . '/components/guide/tours.php'; ?>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
