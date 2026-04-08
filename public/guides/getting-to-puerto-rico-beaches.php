<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/guide_i18n.php';

$lang = getCurrentLanguage();

// Noindex Spanish guides until fully translated — remove when translation verified
// if ($lang === 'es') { $robotsOverride = 'noindex, nofollow'; }

$content = loadGuideContent('getting-to-puerto-rico-beaches');

$pageTitle = __('guide_transportation.title');
$pageDescription = __('guide_transportation.description');

// Fetch featured beaches for CTAs
$featuredBeaches = query("
    SELECT id, name, municipality, slug
    FROM beaches
    WHERE slug IN ('flamenco-beach-culebra', 'crash-boat-beach-aguadilla', 'seven-seas-beach-fajardo', 'balneario-de-carolina-carolina', 'sun-bay-vieques')
    LIMIT 5
");

$relatedGuides = [
    ['title' => __('related_guides.best_time'),        'url' => routeUrl('guide_best_time', $lang)],
    ['title' => __('related_guides.safety'),            'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.culebra_vieques'),   'url' => routeUrl('guide_culebra_vieques', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 8; $i++) {
    $faqs[] = [
        'question' => __("guide_transportation.faq_{$i}_q"),
        'answer'   => __("guide_transportation.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 7; $i++) {
    $howToSteps[] = [
        'name' => __("guide_transportation.howto_step{$i}_name"),
        'text' => __("guide_transportation.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_transportation', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_transportation.howto_title'),
    __('guide_transportation.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),     'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'),   'url' => $guidesUrl],
    ['name' => __('guide_transportation.breadcrumb'),  'url' => $guideUrl],
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
    ['name' => __('guide_transportation.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#car-rental" class="guide-toc-link"><?= h(__('guide_transportation.toc_car_rental')) ?></a><a href="#driving" class="guide-toc-link"><?= h(__('guide_transportation.toc_driving')) ?></a><a href="#uber-taxi" class="guide-toc-link"><?= h(__('guide_transportation.toc_uber_taxi')) ?></a><a href="#public-transit" class="guide-toc-link"><?= h(__('guide_transportation.toc_public_transit')) ?></a><a href="#ferries" class="guide-toc-link"><?= h(__('guide_transportation.toc_ferries')) ?></a><a href="#flights" class="guide-toc-link"><?= h(__('guide_transportation.toc_flights')) ?></a><a href="#costs" class="guide-toc-link"><?= h(__('guide_transportation.toc_costs')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_transportation.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['car_rental'] ?? '' ?>
<?php if (!empty($featuredBeaches[0])): ?>
<div class="bg-slate-50 border-l-4 border-yellow-400 p-6 my-8">
<h4 class="font-bold text-gray-900 mb-2"><?= h(__('guide_transportation.callout_nearby_title')) ?></h4>
<p class="text-gray-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[0]['slug']])) ?>" class="text-gray-700 font-semibold hover:underline"><?= h($featuredBeaches[0]['name']) ?></a> <?= h(str_replace(':municipality', $featuredBeaches[0]['municipality'], __('guide_transportation.callout_nearby_text'))) ?></p>
</div>
<?php endif; ?>
<?= $content['driving'] ?? '' ?>
<?= $content['uber_taxi'] ?? '' ?>
<?php if (!empty($featuredBeaches[1])): ?>
<div class="bg-slate-50 border-l-4 border-yellow-400 p-6 my-8">
<h4 class="font-bold text-gray-900 mb-2"><?= h(__('guide_transportation.callout_uber_title')) ?></h4>
<p class="text-gray-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[1]['slug']])) ?>" class="text-gray-700 font-semibold hover:underline"><?= h($featuredBeaches[1]['name']) ?></a> <?= h(__('guide_transportation.callout_uber_text')) ?></p>
</div>
<?php endif; ?>
<?= $content['public_transit'] ?? '' ?>
<?= $content['ferries'] ?? '' ?>
<?php if (!empty($featuredBeaches[2])): ?>
<div class="bg-slate-50 border-l-4 border-yellow-400 p-6 my-8">
<h4 class="font-bold text-gray-900 mb-2"><?= h(__('guide_transportation.callout_ferry_title')) ?></h4>
<p class="text-gray-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[2]['slug']])) ?>" class="text-gray-700 font-semibold hover:underline"><?= h($featuredBeaches[2]['name']) ?></a> <?= h(__('guide_transportation.callout_ferry_text')) ?></p>
</div>
<?php endif; ?>
<?= $content['flights'] ?? '' ?>
<?= $content['costs'] ?? '' ?>
<?php if (!empty($featuredBeaches[3])): ?>
<div class="bg-slate-50 border-l-4 border-yellow-400 p-6 my-8">
<h4 class="font-bold text-gray-900 mb-2"><?= h(__('guide_transportation.callout_budget_title')) ?></h4>
<p class="text-gray-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[3]['slug']])) ?>" class="text-gray-700 font-semibold hover:underline"><?= h($featuredBeaches[3]['name']) ?></a> <?= h(__('guide_transportation.callout_budget_text')) ?></p>
</div>
<?php endif; ?>
<?= $content['tips'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-yellow-400 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gray-800 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-white mb-4"><?= h(__('guide_transportation.cta_title')) ?></h2>
<p class="text-gray-100 mb-6"><?= h(__('guide_transportation.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>" class="inline-block bg-yellow-400 text-gray-900 px-6 py-3 rounded-lg font-semibold hover:bg-yellow-500 transition-colors"><?= h(__('guide_transportation.cta_button')) ?></a>
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
