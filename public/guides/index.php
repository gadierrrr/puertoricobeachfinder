<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';

$lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
$pageTitle = __('guides_index.page_title');
$pageDescription = __('guides_index.page_description');

$guides = [
    [
        'title' => __('guides_index.guide_transport_title'),
        'slug' => 'getting-to-puerto-rico-beaches',
        'route' => 'guide_transportation',
        'description' => __('guides_index.guide_transport_desc'),
        'icon' => '🚗',
        'kicker' => $lang === 'es' ? 'Logistica' : 'Logistics',
        'readTime' => __('guides_index.guide_transport_time')
    ],
    [
        'title' => __('guides_index.guide_safety_title'),
        'slug' => 'beach-safety-tips',
        'route' => 'guide_safety',
        'description' => __('guides_index.guide_safety_desc'),
        'icon' => '🛟',
        'kicker' => $lang === 'es' ? 'Seguridad' : 'Safety',
        'readTime' => __('guides_index.guide_safety_time')
    ],
    [
        'title' => __('guides_index.guide_besttime_title'),
        'slug' => 'best-time-visit-puerto-rico-beaches',
        'route' => 'guide_best_time',
        'description' => __('guides_index.guide_besttime_desc'),
        'icon' => '📅',
        'kicker' => $lang === 'es' ? 'Temporada' : 'Season',
        'readTime' => __('guides_index.guide_besttime_time')
    ],
    [
        'title' => __('guides_index.guide_packing_title'),
        'slug' => 'beach-packing-list',
        'route' => 'guide_packing',
        'description' => __('guides_index.guide_packing_desc'),
        'icon' => '🎒',
        'kicker' => $lang === 'es' ? 'Preparacion' : 'Prep',
        'readTime' => __('guides_index.guide_packing_time')
    ],
    [
        'title' => __('guides_index.guide_islands_title'),
        'slug' => 'culebra-vs-vieques',
        'route' => 'guide_culebra_vieques',
        'description' => __('guides_index.guide_islands_desc'),
        'icon' => '🏝️',
        'kicker' => $lang === 'es' ? 'Islas' : 'Islands',
        'readTime' => __('guides_index.guide_islands_time')
    ],
    [
        'title' => __('guides_index.guide_bio_title'),
        'slug' => 'bioluminescent-bays',
        'route' => 'guide_bio_bays',
        'description' => __('guides_index.guide_bio_desc'),
        'icon' => '✨',
        'kicker' => $lang === 'es' ? 'Noche' : 'Night',
        'readTime' => __('guides_index.guide_bio_time')
    ],
    [
        'title' => __('guides_index.guide_snorkeling_title'),
        'slug' => 'snorkeling-guide',
        'route' => 'guide_snorkeling',
        'description' => __('guides_index.guide_snorkeling_desc'),
        'icon' => '🤿',
        'kicker' => $lang === 'es' ? 'Agua' : 'Water',
        'readTime' => __('guides_index.guide_snorkeling_time')
    ],
    [
        'title' => __('guides_index.guide_surfing_title'),
        'slug' => 'surfing-guide',
        'route' => 'guide_surfing',
        'description' => __('guides_index.guide_surfing_desc'),
        'icon' => '🏄',
        'kicker' => $lang === 'es' ? 'Olas' : 'Surf',
        'readTime' => __('guides_index.guide_surfing_time')
    ],
    [
        'title' => __('guides_index.guide_photo_title'),
        'slug' => 'beach-photography-tips',
        'route' => 'guide_photography',
        'description' => __('guides_index.guide_photo_desc'),
        'icon' => '📸',
        'kicker' => $lang === 'es' ? 'Luz' : 'Light',
        'readTime' => __('guides_index.guide_photo_time')
    ],
    [
        'title' => __('guides_index.guide_family_title'),
        'slug' => 'family-beach-vacation-planning',
        'route' => 'guide_family_planning',
        'description' => __('guides_index.guide_family_desc'),
        'icon' => '👨‍👩‍👧‍👦',
        'kicker' => $lang === 'es' ? 'Familia' : 'Family',
        'readTime' => __('guides_index.guide_family_time')
    ],
    [
        'title' => 'Kid-Friendly Beaches',
        'slug' => 'kid-friendly-beaches',
        'route' => 'guide_kid_friendly',
        'description' => 'The best beaches in Puerto Rico for kids — calm water, facilities, and tips for safe beach days with toddlers and children.',
        'icon' => '👶',
        'kicker' => $lang === 'es' ? 'Ninos' : 'Kids',
        'readTime' => '18 min read'
    ],
    [
        'title' => $lang === 'es' ? 'Playas para Spring Break' : 'Spring Break Beaches',
        'slug' => 'spring-break-beaches-puerto-rico',
        'route' => 'guide_spring_break',
        'description' => $lang === 'es'
            ? 'Las mejores playas de Puerto Rico para spring break: ambiente, música, y dónde quedarte cerca de la acción.'
            : 'The best Puerto Rico beaches for spring break — vibes, nightlife proximity, and where to stay near the action.',
        'icon' => '🎉',
        'kicker' => $lang === 'es' ? 'Temporada' : 'Season',
        'readTime' => $lang === 'es' ? '15 min de lectura' : '15 min read'
    ]
];

foreach ($guides as &$guide) {
    $guide['url'] = !empty($guide['route']) && function_exists('routeUrl')
        ? routeUrl($guide['route'], $lang)
        : '/guides/' . $guide['slug'];
}
unset($guide);

$collectionPageSchema = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => $pageTitle,
    "description" => $pageDescription,
    "url" => absoluteUrl('/guides/'),
    "breadcrumb" => [
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Home",
                "item" => absoluteUrl('/')
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => "Guides",
                "item" => absoluteUrl('/guides/')
            ]
        ]
    ]
];
$extraHead = $extraHead ?? "";
$extraHead .= '<script type="application/ld+json">' . json_encode($collectionPageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

$pageTheme = "guide";
$skipMapCSS = true;
$skipMapScripts = true;
$redesignLayout = useRedesign();
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>

<?php if ($redesignLayout): ?>
    <div class="rd rd-guides">
        <section class="guides-hero managed-page-hero"<?= pageHeroAttributes('guides') ?>>
            <div class="wrap guides-hero-grid">
                <div class="guides-hero-copy">
                    <nav class="guides-crumb" aria-label="Breadcrumb">
                        <a href="<?= h(routeUrl('home', $lang)) ?>"><?= h(__('guides_index.breadcrumb_home')) ?></a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page"><?= h(__('guides_index.breadcrumb_guides')) ?></span>
                    </nav>
                    <p class="eyebrow"><?= h($lang === 'es' ? 'Guias de campo' : 'Field guides') ?></p>
                    <h1><?= h($pageTitle) ?></h1>
                    <p class="lede"><?= h($pageDescription) ?></p>
                    <div class="guide-stats" aria-label="<?= h($lang === 'es' ? 'Resumen de guias' : 'Guide summary') ?>">
                        <span><b><?= count($guides) ?></b> <?= h($lang === 'es' ? 'guias' : 'guides') ?></span>
                        <span><b>4</b> <?= h($lang === 'es' ? 'formas de planificar' : 'planning modes') ?></span>
                        <span><b>PR</b> <?= h($lang === 'es' ? 'playas y costas' : 'beaches and coasts') ?></span>
                    </div>
                </div>
                <div class="guides-postcards" aria-hidden="true">
                    <div class="postcard pc1" style="background-image:url('/images/beaches/flamenco-beach-culebra.webp')"><span>Culebra</span></div>
                    <div class="postcard pc2" style="background-image:url('/images/beaches/crash-boat-beach-aguadilla-18458-67164.webp')"><span>Aguadilla</span></div>
                    <div class="postcard pc3" style="background-image:url('/images/beaches/playa-negra-black-sand-beach-vieques-18119-65507.webp')"><span>Vieques</span></div>
                </div>
            </div>
        </section>

        <section class="guides-index wrap" aria-labelledby="guidesGridHeading">
            <div class="guides-index-head">
                <div>
                    <p class="eyebrow"><?= h($lang === 'es' ? 'Elige el tipo de ayuda' : 'Choose the kind of help') ?></p>
                    <h2 id="guidesGridHeading"><?= h($lang === 'es' ? 'Planifica por decision' : 'Plan by decision') ?></h2>
                </div>
                <a class="guide-home-link" href="<?= h(routeUrl('home', $lang)) ?>#beaches"><?= h(__('guides_index.cta_button')) ?></a>
            </div>

            <div class="guide-grid">
                <?php foreach ($guides as $index => $guide): ?>
                <a class="guide-card<?= $index === 0 ? ' guide-card-feature' : '' ?>" href="<?= h($guide['url']) ?>">
                    <span class="guide-num"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="guide-icon" aria-hidden="true"><?= $guide['icon'] ?></span>
                    <span class="guide-kicker"><?= h($guide['kicker']) ?></span>
                    <h3><?= h($guide['title']) ?></h3>
                    <p><?= h($guide['description']) ?></p>
                    <span class="guide-read"><span><?= h($guide['readTime']) ?></span><span><?= h(__('guides_index.read_guide')) ?> →</span></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="guides-cta wrap">
            <div>
                <p class="eyebrow"><?= h($lang === 'es' ? 'Despues de leer' : 'After the reading') ?></p>
                <h2><?= h(__('guides_index.cta_title')) ?></h2>
                <p><?= h(__('guides_index.cta_desc')) ?></p>
            </div>
            <a href="<?= h(routeUrl('home', $lang)) ?>#beaches"><?= h(__('guides_index.cta_button')) ?></a>
        </section>
    </div>
<?php else: ?>
    <!-- Hero Section -->
    <?php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Guides']
    ];
    include APP_ROOT . '/components/hero-guide.php';
    ?>

    <!-- Guides Grid -->
    <main class="container mx-auto px-4 container-padding py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($guides as $guide): ?>
                <a href="/guides/<?php echo h($guide['slug']); ?>"
                   class="block bg-white rounded-lg shadow-card hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <div class="p-6">
                        <div class="text-5xl mb-4"><?php echo $guide['icon']; ?></div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                            <?php echo h($guide['title']); ?>
                        </h2>
                        <p class="text-gray-600 mb-4 leading-relaxed">
                            <?php echo h($guide['description']); ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-green-600 font-medium"><?php echo h($guide['readTime']); ?></span>
                            <span class="text-gray-400 group-hover:text-green-600 transition-colors">Read guide →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- CTA Section -->
        <div class="mt-16 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Explore?</h2>
            <p class="text-lg text-gray-700 mb-6 max-w-2xl mx-auto">
                Browse our collection of 230+ beaches across Puerto Rico and find your perfect beach destination.
            </p>
            <a href="/" class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                Browse All Beaches
            </a>
        </div>
    </main>
<?php endif; ?>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
