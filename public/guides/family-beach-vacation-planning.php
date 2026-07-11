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

$content = loadGuideContent('family-beach-vacation-planning');

$pageTitle = __('guide_family.title');
$pageDescription = __('guide_family.description');

$family_beaches = query("SELECT id, name, municipality, slug FROM beaches WHERE id IN (SELECT beach_id FROM beach_amenities WHERE amenity IN ('lifeguards','restrooms','showers')) LIMIT 5");
$familyMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($family_beaches, 'id'))));

$relatedGuides = [
    ['title' => __('related_guides.safety'),    'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.packing'),    'url' => routeUrl('guide_packing', $lang)],
    ['title' => __('related_guides.transport'),  'url' => routeUrl('guide_transportation', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_family.faq_{$i}_q"),
        'answer'   => __("guide_family.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 7; $i++) {
    $howToSteps[] = [
        'name' => __("guide_family.howto_step{$i}_name"),
        'text' => __("guide_family.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_family_planning', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_family.howto_title'),
    __('guide_family.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_family.breadcrumb'),        'url' => $guideUrl],
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
    ['name' => __('guide_family.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#why-pr" class="guide-toc-link"><?= h(__('guide_family.toc_why_pr')) ?></a><a href="#best-beaches" class="guide-toc-link"><?= h(__('guide_family.toc_best_beaches')) ?></a><a href="#where-stay" class="guide-toc-link"><?= h(__('guide_family.toc_where_stay')) ?></a><a href="#itineraries" class="guide-toc-link"><?= h(__('guide_family.toc_itineraries')) ?></a><a href="#budget" class="guide-toc-link"><?= h(__('guide_family.toc_budget')) ?></a><a href="#activities" class="guide-toc-link"><?= h(__('guide_family.toc_activities')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_family.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['why_pr'] ?? '' ?>
<?= $content['best_beaches'] ?? '' ?>
<?php if(!empty($family_beaches)):?>
<div class="space-y-4 mb-8"><?php foreach($family_beaches as $beach):?>
<div class="bg-green-50 border-l-4 border-green-600 p-4"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>" class="text-green-900 font-bold hover:underline"><?= h($beach['name']) ?></a><p class="text-green-800 text-sm"><?= h($beach['municipality']) ?></p></div>
<?php endforeach;?></div>
<?php endif;?>
<?= $content['best_beaches_after'] ?? '' ?>
<?= $content['where_stay'] ?? '' ?>
<?= $content['itineraries'] ?? '' ?>
<?= $content['budget'] ?? '' ?>
<?= $content['activities'] ?? '' ?>
<?= $content['tips'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<?php
$guideMapIds = $familyMapBeachIds;
$guideMapTitle = __('guide_family.map_title');
$guideMapDescription = __('guide_family.map_desc');
$guideMapButtonLabel = __('guide_family.map_button');
$guideMapEmptyNotice = __('guide_family.map_empty');
include APP_ROOT . '/components/guide-map-panel.php';
?></div>
<?php $guideToursSlug = 'family-beach-vacation-planning'; include APP_ROOT . '/components/guide/tours.php'; ?>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
