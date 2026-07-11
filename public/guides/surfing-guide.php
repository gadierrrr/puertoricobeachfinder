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

$content = loadGuideContent('surfing-guide');

$pageTitle = __('guide_surfing.title');
$pageDescription = __('guide_surfing.description');

$surf_beaches = query("SELECT id, name, municipality, slug FROM beaches WHERE id IN (SELECT beach_id FROM beach_tags WHERE tag = 'surfing') LIMIT 5");
$surfMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($surf_beaches, 'id'))));

$relatedGuides = [
    ['title' => __('related_guides.safety'), 'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.best_time'), 'url' => routeUrl('guide_best_time', $lang)],
    ['title' => __('related_guides.transport'), 'url' => routeUrl('guide_transportation', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_surfing.faq_{$i}_q"),
        'answer'   => __("guide_surfing.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 6; $i++) {
    $howToSteps[] = [
        'name' => __("guide_surfing.howto_step{$i}_name"),
        'text' => __("guide_surfing.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_surfing', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_surfing.howto_title'),
    __('guide_surfing.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_surfing.breadcrumb'),        'url' => $guideUrl],
]);

$pageTheme = "guide";
$redesignLayout = useRedesign();
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_surfing.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#why" class="guide-toc-link"><?= h(__('guide_surfing.toc_why')) ?></a><a href="#regions" class="guide-toc-link"><?= h(__('guide_surfing.toc_regions')) ?></a><a href="#breaks" class="guide-toc-link"><?= h(__('guide_surfing.toc_breaks')) ?></a><a href="#seasons" class="guide-toc-link"><?= h(__('guide_surfing.toc_seasons')) ?></a><a href="#rentals" class="guide-toc-link"><?= h(__('guide_surfing.toc_rentals')) ?></a><a href="#etiquette" class="guide-toc-link"><?= h(__('guide_surfing.toc_etiquette')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_surfing.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['why'] ?? '' ?>
<?= $content['regions'] ?? '' ?>
<h2 id="breaks" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_surfing.toc_breaks')) ?></h2>
<?php if (!empty($surf_beaches)): ?>
<div class="space-y-4 mb-8">
<?php foreach ($surf_beaches as $beach): ?>
<div class="bg-slate-50 border-l-4 border-green-600 p-4">
<a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>" class="text-gray-900 font-bold hover:underline">
<?= h($beach['name']) ?>
</a>
<p class="text-amber-700 text-sm"><?= h($beach['municipality']) ?></p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?= $content['seasons'] ?? '' ?>
<?= $content['rentals'] ?? '' ?>
<?= $content['etiquette'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<?php
$guideMapIds = $surfMapBeachIds;
$guideMapTitle = __('guide_surfing.map_title');
$guideMapDescription = __('guide_surfing.map_desc');
$guideMapButtonLabel = __('guide_surfing.map_button');
$guideMapEmptyNotice = __('guide_surfing.map_empty');
include APP_ROOT . '/components/guide-map-panel.php';
?>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_surfing.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_surfing.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"><?= h(__('guide_surfing.cta_button')) ?></a>
</div></div>
<?php $guideToursSlug = 'surfing-guide'; include APP_ROOT . '/components/guide/tours.php'; ?>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
