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

$content = loadGuideContent('snorkeling-guide');

$pageTitle = __('guide_snorkeling.title');
$pageDescription = __('guide_snorkeling.description');

$snorkel_beaches = query("SELECT id, name, municipality, slug FROM beaches WHERE id IN (
    SELECT beach_id FROM beach_tags WHERE tag = 'snorkeling' LIMIT 5
)");
$snorkelMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($snorkel_beaches, 'id'))));

$relatedGuides = [
    ['title' => __('related_guides.safety'),          'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.packing'),          'url' => routeUrl('guide_packing', $lang)],
    ['title' => __('related_guides.culebra_vieques'),  'url' => routeUrl('guide_culebra_vieques', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_snorkeling.faq_{$i}_q"),
        'answer'   => __("guide_snorkeling.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 6; $i++) {
    $howToSteps[] = [
        'name' => __("guide_snorkeling.howto_step{$i}_name"),
        'text' => __("guide_snorkeling.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_snorkeling', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_snorkeling.howto_title'),
    __('guide_snorkeling.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_snorkeling.breadcrumb'),    'url' => $guideUrl],
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
    ['name' => __('guide_snorkeling.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#why" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_why')) ?></a><a href="#equipment" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_equipment')) ?></a><a href="#technique" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_technique')) ?></a><a href="#top-spots" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_top_spots')) ?></a><a href="#marine-life" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_marine_life')) ?></a><a href="#safety" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_safety')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_snorkeling.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['why'] ?? '' ?>
<?= $content['equipment'] ?? '' ?>
<?= $content['technique'] ?? '' ?>
<h2 id="top-spots" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_snorkeling.toc_top_spots')) ?></h2>
<?php if (!empty($snorkel_beaches)): ?>
<div class="space-y-4 mb-8">
<?php $counter = 1; foreach ($snorkel_beaches as $beach): ?>
<div class="bg-slate-50 border-l-4 border-green-600 p-4">
<h4 class="font-bold text-gray-900"><?= $counter ?>. <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>" class="hover:underline"><?= h($beach['name']) ?></a></h4>
<p class="text-amber-700 text-sm"><?= h($beach['municipality']) ?></p>
</div>
<?php $counter++; endforeach; ?>
</div>
<?php endif; ?>
<?= $content['marine_life'] ?? '' ?>
<?= $content['safety'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<?php
$guideMapIds = $snorkelMapBeachIds;
$guideMapTitle = __('guide_snorkeling.map_title');
$guideMapDescription = __('guide_snorkeling.map_desc');
$guideMapButtonLabel = __('guide_snorkeling.map_button');
$guideMapEmptyNotice = __('guide_snorkeling.map_empty');
include APP_ROOT . '/components/guide-map-panel.php';
?>
</div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
