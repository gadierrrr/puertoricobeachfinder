<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/guide_i18n.php';

$lang = getCurrentLanguage();

$content = loadGuideContent('puerto-rico-sunscreen-rules');

$pageTitle = __('guide_sunscreen.title');
$pageDescription = __('guide_sunscreen.description');

$relatedGuides = [
    ['title' => __('related_guides.packing'),   'url' => routeUrl('guide_packing', $lang)],
    ['title' => __('related_guides.safety'),    'url' => routeUrl('guide_safety', $lang)],
    ['title' => __('related_guides.bio_bays'),  'url' => routeUrl('guide_bio_bays', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_sunscreen.faq_{$i}_q"),
        'answer'   => __("guide_sunscreen.faq_{$i}_a"),
    ];
}

$guideUrl = routeUrl('guide_sunscreen', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl = routeUrl('home', $lang);

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2026-08-20');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_sunscreen.breadcrumb'),     'url' => $guideUrl],
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
    ['name' => __('guide_sunscreen.breadcrumb')],
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
<nav class="space-y-2"><a href="#law" class="guide-toc-link"><?= h(__('guide_sunscreen.toc_law')) ?></a><a href="#where-required" class="guide-toc-link"><?= h(__('guide_sunscreen.toc_where')) ?></a><a href="#what-to-buy" class="guide-toc-link"><?= h(__('guide_sunscreen.toc_buy')) ?></a><a href="#practical" class="guide-toc-link"><?= h(__('guide_sunscreen.toc_practical')) ?></a><a href="#faq" class="guide-toc-link"><?= h(__('guide_sunscreen.toc_faq')) ?></a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<?= $content['intro'] ?? '' ?>
<?= $content['law'] ?? '' ?>
<?= $content['where_required'] ?? '' ?>
<?= $content['what_to_buy'] ?? '' ?>
<?= $content['practical'] ?? '' ?>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-yellow-400 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-700"><?= h($faq['answer']) ?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h(__('guide_sunscreen.cta_title')) ?></h2>
<p class="text-gray-700 mb-6"><?= h(__('guide_sunscreen.cta_desc')) ?></p>
<a href="<?= h(routeUrl('best_snorkeling_beaches', $lang)) ?>" class="inline-block bg-sunset-400 text-ocean-900 px-6 py-3 rounded-lg font-semibold hover:bg-sunset-300 transition-colors"><?= h(__('guide_sunscreen.cta_button')) ?></a>
</div></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="<?= h($guide['url']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach;?></div></div>
</article></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
