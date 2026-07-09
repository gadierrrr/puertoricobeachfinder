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

$content = loadGuideContent('beach-safety-tips');

$pageTitle = __('guide_safety.title');
$pageDescription = __('guide_safety.description');

$featuredBeaches = query("
    SELECT id, name, municipality, slug
    FROM beaches
    WHERE slug LIKE 'balneario-la-monserrate-luquillo-luquillo%'
       OR slug LIKE 'seven-seas-beach-fajardo%'
       OR slug LIKE 'crash-boat-beach-aguadilla%'
    ORDER BY CASE
        WHEN slug LIKE 'balneario-la-monserrate-luquillo-luquillo%' THEN 1
        WHEN slug LIKE 'seven-seas-beach-fajardo%' THEN 2
        WHEN slug LIKE 'crash-boat-beach-aguadilla%' THEN 3
        ELSE 4
    END
    LIMIT 3
");

// Fallback to known guarded beaches if canonical slug variants are unavailable.
if (empty($featuredBeaches)) {
    $featuredBeaches = query("
        SELECT DISTINCT b.id, b.name, b.municipality, b.slug
        FROM beaches b
        INNER JOIN beach_amenities a ON a.beach_id = b.id
        WHERE a.amenity = 'lifeguard'
        ORDER BY b.name ASC
        LIMIT 3
    ");
}

$safetyMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($featuredBeaches, 'id'))));

$relatedGuides = [
    ['title' => __('related_guides.transport'), 'url' => routeUrl('guide_transportation', $lang)],
    ['title' => __('related_guides.packing'),   'url' => routeUrl('guide_packing', $lang)],
    ['title' => __('related_guides.snorkeling'), 'url' => routeUrl('guide_snorkeling', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 8; $i++) {
    $faqs[] = [
        'question' => __("guide_safety.faq_{$i}_q"),
        'answer'   => __("guide_safety.faq_{$i}_a"),
    ];
}

$howToSteps = [];
for ($i = 1; $i <= 7; $i++) {
    $howToSteps[] = [
        'name' => __("guide_safety.howto_step{$i}_name"),
        'text' => __("guide_safety.howto_step{$i}_text"),
    ];
}

$guideUrl = routeUrl('guide_safety', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2024-01-15');
$extraHead .= howToSchema(
    __('guide_safety.howto_title'),
    __('guide_safety.howto_desc'),
    $howToSteps
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_safety.breadcrumb'),        'url' => $guideUrl],
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
    ['name' => __('guide_safety.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#rip-currents" class="guide-toc-link"><?= h(__('guide_safety.toc_rip_currents')) ?></a><a href="#sun-protection" class="guide-toc-link"><?= h(__('guide_safety.toc_sun_protection')) ?></a><a href="#marine-life" class="guide-toc-link"><?= h(__('guide_safety.toc_marine_life')) ?></a><a href="#water-quality" class="guide-toc-link"><?= h(__('guide_safety.toc_water_quality')) ?></a><a href="#theft-security" class="guide-toc-link"><?= h(__('guide_safety.toc_theft')) ?></a><a href="#weather" class="guide-toc-link"><?= h(__('guide_safety.toc_weather')) ?></a><a href="#emergency" class="guide-toc-link"><?= h(__('guide_safety.toc_emergency')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_safety.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['rip_currents'] ?? '' ?>
<?php if (!empty($featuredBeaches[0])): ?>
<div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
<h4 class="font-bold text-green-900 mb-2"><?= h(__('guide_safety.callout_lifeguard_title')) ?></h4>
<p class="text-green-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[0]['slug']])) ?>" class="text-green-600 font-semibold hover:underline"><?= h($featuredBeaches[0]['name']) ?></a> <?= h(__('guide_safety.callout_lifeguard_desc')) ?></p>
</div>
<?php endif; ?>
<?= $content['sun_protection'] ?? '' ?>
<?= $content['marine_life'] ?? '' ?>
<?php if (!empty($featuredBeaches[1])): ?>
<div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
<h4 class="font-bold text-green-900 mb-2"><?= h(__('guide_safety.callout_calm_title')) ?></h4>
<p class="text-green-800 mb-3"><a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $featuredBeaches[1]['slug']])) ?>" class="text-green-600 font-semibold hover:underline"><?= h($featuredBeaches[1]['name']) ?></a> <?= h(__('guide_safety.callout_calm_desc')) ?></p>
</div>
<?php endif; ?>
<?= $content['water_quality'] ?? '' ?>
<?= $content['theft_security'] ?? '' ?>
<?= $content['weather'] ?? '' ?>
<?= $content['emergency'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<?php
$guideMapIds = $safetyMapBeachIds;
$guideMapTitle = __('guide_safety.map_title');
$guideMapDescription = __('guide_safety.map_desc');
$guideMapButtonLabel = __('guide_safety.map_button');
$guideMapEmptyNotice = __('guide_safety.map_empty');
include APP_ROOT . '/components/guide-map-panel.php';
?>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_safety.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_safety.cta_desc')) ?></p>
<a href="<?= h($homeUrl) ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"><?= h(__('guide_safety.cta_button')) ?></a>
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
