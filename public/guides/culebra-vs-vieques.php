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

$content = loadGuideContent('culebra-vs-vieques');

$pageTitle = __('guide_culebra_vieques.title');
$pageDescription = __('guide_culebra_vieques.description');

$culebra_beaches = query("SELECT id, name, slug FROM beaches WHERE municipality = 'Culebra' LIMIT 3");
$vieques_beaches = query("SELECT id, name, slug FROM beaches WHERE municipality = 'Vieques' LIMIT 3");

$relatedGuides = [
    ['title' => __('related_guides.transport'),  'url' => routeUrl('guide_transportation', $lang)],
    ['title' => __('related_guides.bio_bays'),   'url' => routeUrl('guide_bio_bays', $lang)],
    ['title' => __('related_guides.snorkeling'), 'url' => routeUrl('guide_snorkeling', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_culebra_vieques.faq_{$i}_q"),
        'answer'   => __("guide_culebra_vieques.faq_{$i}_a"),
    ];
}

$guideUrl  = routeUrl('guide_culebra_vieques', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl   = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_culebra_vieques.breadcrumb'), 'url' => $guideUrl],
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
    ['name' => __('guide_culebra_vieques.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#overview" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_overview')) ?></a><a href="#comparison" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_comparison')) ?></a><a href="#beaches" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_beaches')) ?></a><a href="#transportation" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_transportation')) ?></a><a href="#accommodation" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_accommodation')) ?></a><a href="#activities" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_activities')) ?></a><a href="#costs" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_costs')) ?></a><a href="#verdict" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_verdict')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_culebra_vieques.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['overview'] ?? '' ?>
<?= $content['comparison'] ?? '' ?>

<h2 id="beaches" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_culebra_vieques.toc_beaches')) ?></h2>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Culebra</h3>
<?php if (!empty($culebra_beaches)): ?>
<div class="space-y-4 mb-6">
    <?php foreach ($culebra_beaches as $beach): ?>
    <div class="bg-blue-50 border-l-4 border-blue-600 p-4">
        <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>" class="text-blue-900 font-bold hover:underline">
            <?= h($beach['name']) ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $content['beaches_culebra_desc'] ?? '' ?>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vieques</h3>
<?php if (!empty($vieques_beaches)): ?>
<div class="space-y-4 mb-6">
    <?php foreach ($vieques_beaches as $beach): ?>
    <div class="bg-green-50 border-l-4 border-green-600 p-4">
        <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>" class="text-green-900 font-bold hover:underline">
            <?= h($beach['name']) ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $content['beaches_vieques_desc'] ?? '' ?>

<?= $content['transportation'] ?? '' ?>
<?= $content['accommodation'] ?? '' ?>
<?= $content['activities'] ?? '' ?>
<?= $content['costs'] ?? '' ?>
<?= $content['verdict'] ?? '' ?>

<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_culebra_vieques.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_culebra_vieques.cta_desc')) ?></p>
<div class="flex gap-4">
<a href="<?= h($homeUrl) ?>?municipality=Culebra" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"><?= h(__('guide_culebra_vieques.cta_button_culebra')) ?></a>
<a href="<?= h($homeUrl) ?>?municipality=Vieques" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"><?= h(__('guide_culebra_vieques.cta_button_vieques')) ?></a>
</div></div></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
