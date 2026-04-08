<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/affiliate.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/guide_i18n.php';

$lang = getCurrentLanguage();

// Noindex Spanish guides until fully translated — remove when translation verified
// if ($lang === 'es') { $robotsOverride = 'noindex, nofollow'; }

$content = loadGuideContent('spring-break-beaches-puerto-rico');

$pageTitle = __('guide_spring_break.title');
$pageDescription = __('guide_spring_break.description');

// Party & Nightlife Beaches (San Juan)
$party_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('condado-beach', 'ocean-park-beach-san-juan-18452-6606', 'escambron-beach')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Surf Beaches (Rincón & West Coast)
$surf_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('playa-do-a-lala-beach', 'playa-c-rcega', 'jobos-beach-isabela-18513-67085', 'montones-beach-isabela-18506-67081')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Day Trips (Culebra)
$culebra_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('flamenco-beach-culebra-18329-65318', 'carlos-rosario-beach-culebra-18327-65308', 'tamarindo-beach-culebra-culebra-18326-65313')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Off the Beaten Path (Vieques)
$vieques_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('sun-bay-vieques-18097-65457', 'pata-prieta-secret-beach-vieques-18098-65412', 'mosquito-bay-beach')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Attach tags & amenities to all groups (avoids N+1 queries)
attachBeachMetadata($party_beaches);
attachBeachMetadata($surf_beaches);
attachBeachMetadata($culebra_beaches);
attachBeachMetadata($vieques_beaches);

// Semantic tag colors — used across all 4 beach sections
$tagColor = static fn(string $tag): string => match($tag) {
    'snorkeling'      => 'bg-teal-50 text-teal-700',
    'surfing'         => 'bg-amber-50 text-amber-700',
    'popular'         => 'bg-red-50 text-red-700',
    'calm-waters'     => 'bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'scenic'          => 'bg-purple-50 text-purple-700',
    'family-friendly' => 'bg-pink-50 text-pink-700',
    'swimming'        => 'bg-sky-50 text-sky-700',
    'diving'          => 'bg-indigo-50 text-indigo-700',
    'accessible'      => 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-200',
    default           => 'bg-gray-100 text-gray-600',
};

// Hero background: use Condado beach cover image if available
$heroBeach = queryOne(
    "SELECT cover_image FROM beaches WHERE slug = 'condado-beach' AND publish_status = 'published'"
);
$heroImage = $heroBeach['cover_image'] ?? '';

// Build combined map IDs from all groups
$toMapIds = static function (array $beaches): array {
    return array_values(array_filter(array_map(static function ($id): string {
        if (!is_scalar($id)) {
            return '';
        }
        return trim((string)$id);
    }, array_column($beaches, 'id'))));
};

$allMapIds = array_merge(
    $toMapIds($party_beaches),
    $toMapIds($surf_beaches),
    $toMapIds($culebra_beaches),
    $toMapIds($vieques_beaches)
);

$relatedGuides = [
    ['title' => __('related_guides.culebra_vieques'), 'url' => routeUrl('guide_culebra_vieques', $lang)],
    ['title' => __('related_guides.surfing'),          'url' => routeUrl('guide_surfing', $lang)],
    ['title' => __('related_guides.bio_bays'),         'url' => routeUrl('guide_bio_bays', $lang)],
];

$faqs = [];
for ($i = 1; $i <= 5; $i++) {
    $faqs[] = [
        'question' => __("guide_spring_break.faq_{$i}_q"),
        'answer'   => __("guide_spring_break.faq_{$i}_a"),
    ];
}

$guideUrl  = routeUrl('guide_spring_break', $lang);
$guidesUrl = routeUrl('guides_index', $lang);
$homeUrl   = routeUrl('home', $lang);
$mapUrl    = routeUrl('best_beaches', $lang);

$extraHead = $extraHead ?? '';
$extraHead .= articleSchema($pageTitle, $pageDescription, $guideUrl, null, '2026-02-22');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_spring_break.breadcrumb'),  'url' => $guideUrl],
]);

$pageTheme = "guide";
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => __('guide_common.breadcrumb_home'),   'url' => $homeUrl],
    ['name' => __('guide_common.breadcrumb_guides'), 'url' => $guidesUrl],
    ['name' => __('guide_spring_break.breadcrumb')],
];
$heroCtas = '<div class="flex gap-3 flex-wrap mt-6">'
    . '<a href="' . h(AFFILIATE_LINKS['flights_sju']) . '" class="inline-flex items-center gap-2 bg-blue-600 text-white font-semibold text-sm px-5 py-3 rounded-lg hover:bg-blue-700 transition-colors" rel="nofollow sponsored" target="_blank">' . h(__('guide_spring_break.hero_cta_flights')) . '</a>'
    . '<a href="' . h(AFFILIATE_LINKS['hotels_sanjuan']) . '" class="inline-flex items-center gap-2 font-semibold text-sm px-5 py-3 rounded-lg hover:opacity-90 transition-colors" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);" rel="nofollow sponsored" target="_blank">' . h(__('guide_spring_break.hero_cta_hotels')) . '</a>'
    . '<a href="' . h($mapUrl) . '" class="inline-flex items-center gap-2 font-semibold text-sm px-5 py-3 rounded-lg hover:opacity-90 transition-colors" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);">' . h(__('guide_spring_break.hero_cta_map')) . '</a>'
    . '</div>';
include APP_ROOT . '/components/hero-guide.php';
?>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                <h2 class="text-lg font-bold text-gray-900 mb-4"><?= h(__('guide_common.toc_heading')) ?></h2>
                <nav class="space-y-2">
                    <a href="#why-pr"  class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#60a5fa;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_why_pr')) ?></a>
                    <a href="#party"   class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_party')) ?></a>
                    <a href="#surf"    class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#06b6d4;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_surf')) ?></a>
                    <a href="#culebra" class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_culebra')) ?></a>
                    <a href="#vieques" class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#8b5cf6;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_vieques')) ?></a>
                    <a href="#tips"    class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#9ca3af;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_tips')) ?></a>
                    <a href="#faq"     class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#9ca3af;display:inline-block;flex-shrink:0;"></span><?= h(__('guide_spring_break.toc_faq')) ?></a>
                </nav>
            </div>
        </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
            <div class="prose prose-lg max-w-none">

                <p class="text-xs text-gray-400 italic mb-6">
                    <?= h(__('guide_spring_break.affiliate_disclosure')) ?>
                </p>

                <?= $content['intro'] ?? '' ?>

                <?= $content['why_pr'] ?? '' ?>

                <?php
                $stripFlight  = affiliateCTA('flights_sju',    __('guide_spring_break.strip_cta_flights'),  'primary');
                $stripSJ      = affiliateCTA('hotels_sanjuan', __('guide_spring_break.strip_cta_sj_hotels'), 'primary');
                $stripRincon  = affiliateCTA('hotels_rincon',  __('guide_spring_break.strip_cta_rincon_hotels'),   'primary');
                $stripCars    = affiliateCTA('cars_sju',       __('guide_spring_break.strip_cta_rent_car'),      'primary');
                if ($stripFlight || $stripSJ || $stripRincon || $stripCars): ?>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 my-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm text-amber-500 dark:text-amber-400 font-medium flex-1 min-w-40">
                            <?= h(__('guide_spring_break.strip_urgency')) ?>
                        </p>
                        <?= $stripFlight ?>
                        <?= $stripSJ ?>
                        <?= $stripRincon ?>
                        <?= $stripCars ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 text-base">🎉</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400"><?= h(__('guide_spring_break.section_party_label')) ?></span>
                </div>
                <h2 id="party" class="text-3xl font-bold text-gray-900 mb-4"><?= h(__('guide_spring_break.section_party_heading')) ?></h2>

                <?= $content['party_intro'] ?? '' ?>

                <?php if (!empty(AFFILIATE_LINKS['hotels_sanjuan'])): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.where_to_stay')) ?></p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 my-4">
                    <?php foreach ([
                        ['La Concha Resort',  '4.4', 'Condado',    '199', '/images/thumbnails/LaConchaOffersBG-1758068284593.webp'],
                        ['AC Hotel San Juan', '4.5', 'Condado',    '159', '/images/thumbnails/trypHotelDeals-1757619761075.webp'],
                        ['El San Juan Hotel', '4.2', 'Isla Verde', '229', '/images/thumbnails/FairmontHotel-1757084065163.webp'],
                    ] as [$hotel, $rating, $area, $price, $photo]): ?>
                    <a href="<?= h(AFFILIATE_LINKS['hotels_sanjuan']) ?>"
                       rel="nofollow sponsored" target="_blank"
                       class="flex flex-col border border-gray-200 rounded-xl overflow-hidden hover:border-blue-400 hover:shadow-md transition-all bg-white group no-underline">
                        <div class="relative h-36 overflow-hidden">
                            <img src="<?= h($photo) ?>"
                                 alt="<?= h($hotel) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 data-fallback-src="/images/beaches/placeholder-beach.webp">
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full shadow-sm">
                                <?= h($rating) ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="font-semibold text-gray-900 text-sm group-hover:text-blue-700"><?= h($hotel) ?></p>
                            <p class="text-xs text-gray-500"><?= h($area) ?></p>
                            <p class="text-xs text-green-700 font-semibold mt-1"><?= h(str_replace(':price', $price, __('guide_spring_break.from_per_night'))) ?></p>
                            <span class="block mt-2 text-center bg-blue-600 text-white text-xs font-semibold py-2 px-3 rounded-lg"><?= h(__('guide_spring_break.book_on_expedia')) ?> &rarr;</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($party_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.beaches_label')) ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($party_beaches);
                    $i = 0;
                    foreach ($party_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-blue-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h(getTagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong><?= h(__('guide_spring_break.tip_local_label')) ?></strong> <?= h(__('guide_spring_break.tip_local_text')) ?></p>
                </div>

                <?php $sjCta = affiliateCTA('hotels_sanjuan', __('guide_spring_break.cta_book_sj_hotels')); ?>
                <?php if ($sjCta): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4 mb-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= h(__('guide_spring_break.cta_sj_price_note')) ?></p>
                        <p class="text-xs text-gray-500"><?= h(__('guide_spring_break.cta_sj_urgency')) ?></p>
                    </div>
                    <?= $sjCta ?>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 text-base">🏄</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400"><?= h(__('guide_spring_break.section_surf_label')) ?></span>
                </div>
                <h2 id="surf" class="text-3xl font-bold text-gray-900 mb-4"><?= h(__('guide_spring_break.section_surf_heading')) ?></h2>

                <?= $content['surf_intro'] ?? '' ?>

                <?php if (!empty(AFFILIATE_LINKS['hotels_rincon'])): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.where_to_stay')) ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-4">
                    <?php foreach ([
                        ['Rincon Beach Resort', '4.3', 'Rincón', '149', '/images/thumbnails/Lazy-Parrot-Hotel-Rincon-1761899804209.webp'],
                        ['Casa Isleña Inn',     '4.6', 'Rincón', '125', '/images/thumbnails/ClubCalaResort-1757079250606.webp'],
                    ] as [$hotel, $rating, $area, $price, $photo]): ?>
                    <a href="<?= h(AFFILIATE_LINKS['hotels_rincon']) ?>"
                       rel="nofollow sponsored" target="_blank"
                       class="flex flex-col border border-gray-200 rounded-xl overflow-hidden hover:border-green-400 hover:shadow-md transition-all bg-white group no-underline">
                        <div class="relative h-36 overflow-hidden">
                            <img src="<?= h($photo) ?>"
                                 alt="<?= h($hotel) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 data-fallback-src="/images/beaches/placeholder-beach.webp">
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full shadow-sm">
                                <?= h($rating) ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="font-semibold text-gray-900 text-sm group-hover:text-green-700"><?= h($hotel) ?></p>
                            <p class="text-xs text-gray-500"><?= h($area) ?></p>
                            <p class="text-xs text-green-700 font-semibold mt-1"><?= h(str_replace(':price', $price, __('guide_spring_break.from_per_night'))) ?></p>
                            <span class="block mt-2 text-center bg-blue-600 text-white text-xs font-semibold py-2 px-3 rounded-lg"><?= h(__('guide_spring_break.book_on_expedia')) ?> &rarr;</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($surf_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.beaches_label')) ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($surf_beaches);
                    $i = 0;
                    foreach ($surf_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-green-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-green-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h(getTagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong><?= h(__('guide_spring_break.tip_surf_label')) ?></strong> <?= h(__('guide_spring_break.tip_surf_text')) ?></p>
                </div>

                <?php $rinconCta = affiliateCTA('hotels_rincon', __('guide_spring_break.cta_book_rincon_hotels')); ?>
                <?php if ($rinconCta): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4 mb-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= h(__('guide_spring_break.cta_rincon_price_note')) ?></p>
                        <p class="text-xs text-gray-500"><?= h(__('guide_spring_break.cta_rincon_urgency')) ?></p>
                    </div>
                    <?= $rinconCta ?>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-base">🐠</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-green-600"><?= h(__('guide_spring_break.section_culebra_label')) ?></span>
                </div>
                <h2 id="culebra" class="text-3xl font-bold text-gray-900 mb-4"><?= h(__('guide_spring_break.section_culebra_heading')) ?></h2>

                <?= $content['culebra_intro'] ?? '' ?>

                <?php if (!empty($culebra_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.beaches_label')) ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachPhotoOverrides = [
                        'flamenco-beach-culebra-18329-65318' =>
                            '/images/beaches/flamenco-beach-culebra.webp',
                    ];
                    $beachCount = count($culebra_beaches);
                    $i = 0;
                    foreach ($culebra_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = $beachPhotoOverrides[$beach['slug']]
                            ?? (!empty($beach['cover_image'])
                                ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                                : '/images/beaches/placeholder-beach.webp');
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-cyan-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-cyan-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h(getTagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-8">
                    <p class="text-amber-800 text-sm"><strong><?= h(__('guide_spring_break.tip_critical_label')) ?></strong> <?= h(__('guide_spring_break.tip_critical_text')) ?></p>
                </div>

                <?php $culebraCarCta = affiliateCTA('cars_sju', __('guide_spring_break.cta_rent_car_ceiba')); ?>
                <?php if ($culebraCarCta): ?><div class="mt-2 mb-8"><?= $culebraCarCta ?></div><?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 text-base">🌿</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-purple-600 dark:text-violet-300"><?= h(__('guide_spring_break.section_vieques_label')) ?></span>
                </div>
                <h2 id="vieques" class="text-3xl font-bold text-gray-900 mb-4"><?= h(__('guide_spring_break.section_vieques_heading')) ?></h2>

                <?= $content['vieques_intro'] ?? '' ?>

                <?php if (!empty($vieques_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6"><?= h(__('guide_spring_break.beaches_label')) ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($vieques_beaches);
                    $i = 0;
                    foreach ($vieques_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-purple-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-purple-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h(getTagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong><?= h(__('guide_spring_break.tip_vieques_label')) ?></strong> <?= h(__('guide_spring_break.tip_vieques_text')) ?></p>
                </div>

                <?php $viequesCarCta = affiliateCTA('cars_sju', __('guide_spring_break.cta_rent_jeep')); ?>
                <?php if ($viequesCarCta): ?><div class="mt-2 mb-8"><?= $viequesCarCta ?></div><?php endif; ?>

                <?php $viequesCta = affiliateCTA('hotels_vieques', __('guide_spring_break.cta_find_vieques_hotels')); ?>
                <?php if ($viequesCta): ?>
                <div class="mt-4 mb-8"><?= $viequesCta ?></div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 text-base">📅</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-500"><?= h(__('guide_spring_break.section_tips_label')) ?></span>
                </div>
                <h2 id="tips" class="text-3xl font-bold text-gray-900 mb-6"><?= h(__('guide_spring_break.section_tips_heading')) ?></h2>

                <?php $tips = [
                    ['📅', __('guide_spring_break.tip_peak_dates_label'),  __('guide_spring_break.tip_peak_dates_value')],
                    ['🚢', __('guide_spring_break.tip_ferry_label'),       __('guide_spring_break.tip_ferry_value')],
                    ['🏖', __('guide_spring_break.tip_flamenco_label'),   __('guide_spring_break.tip_flamenco_value')],
                    ['🏄', __('guide_spring_break.tip_surf_window_label'), __('guide_spring_break.tip_surf_window_value')],
                    ['🏨', __('guide_spring_break.tip_hotel_cost_label'),  __('guide_spring_break.tip_hotel_cost_value')],
                    ['☀️', __('guide_spring_break.tip_weather_label'),     __('guide_spring_break.tip_weather_value')],
                    ['🌊', __('guide_spring_break.tip_water_temp_label'),  __('guide_spring_break.tip_water_temp_value')],
                    ['🧴', __('guide_spring_break.tip_sunscreen_label'),   __('guide_spring_break.tip_sunscreen_value')],
                ]; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                    <?php foreach ($tips as [$icon, $label, $value]): ?>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <p class="font-semibold text-gray-900 text-sm"><?= $icon ?> <?= h($label) ?></p>
                        <p class="text-gray-600 text-sm mt-1"><?= h($value) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php
                $bookingCtaFlight  = affiliateCTA('flights_sju',    __('guide_spring_break.strip_cta_flights'),    'yellow');
                $bookingCtaSj      = affiliateCTA('hotels_sanjuan', __('guide_spring_break.strip_cta_sj_hotels'),   'secondary');
                $bookingCtaRincon  = affiliateCTA('hotels_rincon',  __('guide_spring_break.strip_cta_rincon_hotels'),     'secondary');
                $bookingCtaCars    = affiliateCTA('cars_sju',       __('guide_spring_break.strip_cta_rent_car'),        'secondary');
                $hasBookingCtas    = $bookingCtaFlight || $bookingCtaSj || $bookingCtaRincon || $bookingCtaCars;
                ?>
                <?php if ($hasBookingCtas): ?>
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 my-8 text-white">
                    <h3 class="text-xl font-bold mb-2"><?= h(__('guide_spring_break.ready_to_book')) ?></h3>
                    <p class="text-blue-100 text-sm mb-4">
                        <?= h(__('guide_spring_break.ready_to_book_desc')) ?>
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <?= $bookingCtaFlight ?>
                        <?= $bookingCtaSj ?>
                        <?= $bookingCtaRincon ?>
                        <?= $bookingCtaCars ?>
                    </div>
                </div>
                <?php endif; ?>

                <h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6"><?= h(__('guide_common.faq_heading')) ?></h2>

                <div class="space-y-6">
                    <?php foreach ($faqs as $faq): ?>
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3>
                        <p class="text-gray-700"><?= h($faq['answer']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php
                $guideMapIds = $allMapIds;
                $guideMapTitle = __('guide_spring_break.map_title');
                $guideMapDescription = __('guide_spring_break.map_desc');
                $guideMapButtonLabel = __('guide_spring_break.map_button');
                $guideMapEmptyNotice = __('guide_spring_break.map_empty');
                include APP_ROOT . '/components/guide-map-panel.php';
                ?>

            </div>

            <div class="mt-12 pt-8 border-t border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('guide_common.related_heading')) ?></h3>
                <div class="related-guides-grid">
                    <?php foreach ($relatedGuides as $guide): ?>
                    <a href="<?= h($guide['url']) ?>" class="related-guide-card">
                        <span class="related-guide-title"><?= h($guide['title']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
