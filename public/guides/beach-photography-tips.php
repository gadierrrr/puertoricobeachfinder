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

$content = loadGuideContent('beach-photography-tips');

$pageTitle = __('guide_photography.title');
$pageDescription = __('guide_photography.description');

$relatedGuides = [
    ['title' => __('related_guides.best_time'), 'url' => routeUrl('guide_best_time', $lang)],
    ['title' => __('related_guides.packing'),   'url' => routeUrl('guide_packing', $lang)],
    ['title' => __('related_guides.snorkeling'), 'url' => routeUrl('guide_snorkeling', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_photography.faq_{$i}_q"),
        'answer'   => __("guide_photography.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 6; $i++) {
    $howToSteps[] = [
        'name' => __("guide_photography.howto_step{$i}_name"),
        'text' => __("guide_photography.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_photography', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_photography.howto_title'),
    __('guide_photography.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_photography.breadcrumb'),   'url' => $guideUrl],
]);

$pageTheme = "guide";
$skipMapCSS = true;
$skipMapScripts = true;
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_photography.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#golden-hour" class="guide-toc-link"><?= h(__('guide_photography.toc_golden_hour')) ?></a><a href="#composition" class="guide-toc-link"><?= h(__('guide_photography.toc_composition')) ?></a><a href="#equipment" class="guide-toc-link"><?= h(__('guide_photography.toc_equipment')) ?></a><a href="#settings" class="guide-toc-link"><?= h(__('guide_photography.toc_settings')) ?></a><a href="#underwater" class="guide-toc-link"><?= h(__('guide_photography.toc_underwater')) ?></a><a href="#drones" class="guide-toc-link"><?= h(__('guide_photography.toc_drones')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_photography.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['golden_hour'] ?? '' ?>
<?= $content['composition'] ?? '' ?>
<?= $content['equipment'] ?? '' ?>
<?= $content['settings'] ?? '' ?>
<?= $content['underwater'] ?? '' ?>
<?= $content['drones'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_photography.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_photography.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"><?= h(__('guide_photography.cta_button')) ?></a>
</div></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
