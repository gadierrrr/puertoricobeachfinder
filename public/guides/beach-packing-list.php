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

$content = loadGuideContent('beach-packing-list');

$pageTitle = __('guide_packing.title');
$pageDescription = __('guide_packing.description');

$relatedGuides = [
    ['title' => __('related_guides.sunscreen'),   'url' => routeUrl('guide_sunscreen', $lang)],
    ['title' => __('related_guides.safety'),     'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.best_time'),   'url' => routeUrl('guide_best_time', $lang)],
    ['title' => __('related_guides.snorkeling'),  'url' => routeUrl('guide_snorkeling', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_packing.faq_{$i}_q"),
        'answer'   => __("guide_packing.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 7; $i++) {
    $howToSteps[] = [
        'name' => __("guide_packing.howto_step{$i}_name"),
        'text' => __("guide_packing.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_packing', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_packing.howto_title'),
    __('guide_packing.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_packing.breadcrumb'),       'url' => $guideUrl],
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
    ['name' => __('guide_packing.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#essentials" class="guide-toc-link"><?= h(__('guide_packing.toc_essentials')) ?></a><a href="#sun-protection" class="guide-toc-link"><?= h(__('guide_packing.toc_sun_protection')) ?></a><a href="#swim-gear" class="guide-toc-link"><?= h(__('guide_packing.toc_swim_gear')) ?></a><a href="#comfort" class="guide-toc-link"><?= h(__('guide_packing.toc_comfort')) ?></a><a href="#safety" class="guide-toc-link"><?= h(__('guide_packing.toc_safety')) ?></a><a href="#electronics" class="guide-toc-link"><?= h(__('guide_packing.toc_electronics')) ?></a><a href="#optional" class="guide-toc-link"><?= h(__('guide_packing.toc_optional')) ?></a><a href="#packing-tips" class="guide-toc-link"><?= h(__('guide_packing.toc_packing_tips')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_packing.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['essentials'] ?? '' ?>
<?= $content['sun_protection'] ?? '' ?>
<?= $content['swim_gear'] ?? '' ?>
<?= $content['comfort'] ?? '' ?>
<?= $content['safety'] ?? '' ?>
<?= $content['electronics'] ?? '' ?>
<?= $content['optional'] ?? '' ?>
<?= $content['packing_tips'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-yellow-400 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_packing.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_packing.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>" class="inline-block bg-sunset-400 text-ocean-900 px-6 py-3 rounded-lg font-semibold hover:bg-sunset-300 transition-colors"><?= h(__('guide_packing.cta_button')) ?></a>
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
