<?php
/**
 * Dynamic "Beaches Near [Location]" Pages
 * URL: /beaches-near-{slug} or /es/playas-cerca-de-{slug}
 */
require_once $_SERVER["DOCUMENT_ROOT"] . "/../bootstrap.php";
require_once APP_ROOT . "/inc/db.php";
require_once APP_ROOT . "/inc/helpers.php";
require_once APP_ROOT . "/inc/constants.php";
require_once APP_ROOT . "/inc/locale_routes.php";
require_once APP_ROOT . "/inc/i18n.php";
require_once APP_ROOT . "/components/seo-schemas.php";

$lang = getCurrentLanguage();
$locSlug = trim($_GET['loc'] ?? '');

$locations = [
    'ponce' => ['name'=>'Ponce','lat'=>18.0111,'lng'=>-66.6141,'radius'=>25,'region'=>'the southern coast','slug_es'=>'ponce','drive'=>'1 hour 45 minutes via PR-52 south'],
    'aguadilla' => ['name'=>'Aguadilla','lat'=>18.4274,'lng'=>-67.1541,'radius'=>20,'region'=>'the northwest coast','slug_es'=>'aguadilla','drive'=>'2 hours via PR-22 west then PR-2'],
    'rincon' => ['name'=>'Rincón','lat'=>18.3404,'lng'=>-67.2500,'radius'=>20,'region'=>'the west coast','slug_es'=>'rincon','drive'=>'2.5 hours via PR-22 west then PR-2'],
    'fajardo' => ['name'=>'Fajardo','lat'=>18.3358,'lng'=>-65.6524,'radius'=>20,'region'=>'the east coast','slug_es'=>'fajardo','drive'=>'1 hour via PR-66 east then PR-3'],
    'mayaguez' => ['name'=>'Mayagüez','lat'=>18.2013,'lng'=>-67.1397,'radius'=>25,'region'=>'the west coast','slug_es'=>'mayaguez','drive'=>'2.5 hours via PR-22 west then PR-2'],
    'humacao' => ['name'=>'Humacao','lat'=>18.1498,'lng'=>-65.8275,'radius'=>20,'region'=>'the southeast coast','slug_es'=>'humacao','drive'=>'1 hour via PR-52 south then PR-30 east'],
    'arecibo' => ['name'=>'Arecibo','lat'=>18.4725,'lng'=>-66.7156,'radius'=>20,'region'=>'the north coast','slug_es'=>'arecibo','drive'=>'1 hour 15 minutes via PR-22 west'],
    'cabo-rojo' => ['name'=>'Cabo Rojo','lat'=>18.0866,'lng'=>-67.1457,'radius'=>20,'region'=>'the southwest coast','slug_es'=>'cabo-rojo','drive'=>'2.5 hours via PR-52 south then PR-2 west'],
    'vega-baja' => ['name'=>'Vega Baja','lat'=>18.4441,'lng'=>-66.3906,'radius'=>20,'region'=>'the north coast','slug_es'=>'vega-baja','drive'=>'40 minutes via PR-22 west'],
    'dorado' => ['name'=>'Dorado','lat'=>18.4589,'lng'=>-66.2677,'radius'=>15,'region'=>'the north coast near San Juan','slug_es'=>'dorado','drive'=>'30 minutes via PR-22 west'],
];

if (!$locSlug || !isset($locations[$locSlug])) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

$loc = $locations[$locSlug];
$lat = $loc['lat'];
$lng = $loc['lng'];
$radiusKm = $loc['radius'];

// Haversine query for beaches within radius
$beaches = query("
    SELECT * FROM (SELECT *,
        (6371 * acos(cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng)) + sin(radians(:lat2)) * sin(radians(lat)))) AS distance_km
    FROM beaches WHERE publish_status = 'published' AND lat IS NOT NULL AND (location_type = 'beach' OR location_type IS NULL)
    ) sub WHERE distance_km <= :radius
    ORDER BY distance_km ASC
", [':lat' => $lat, ':lng' => $lng, ':lat2' => $lat, ':radius' => $radiusKm]);

if (empty($beaches)) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

attachBeachMetadata($beaches);
$beachCount = count($beaches);
$locName = $loc['name'];

$pageTitle = "Beaches Near $locName, Puerto Rico | $beachCount Beaches Within {$radiusKm}km";
$pageH1 = "Beaches Near $locName";
$pageDescription = "Find $beachCount beaches near $locName on $loc[region] of Puerto Rico. Browse beaches sorted by distance with ratings, amenities, and directions.";
if ($lang === 'es') {
    $pageTitle = "Playas Cerca de $locName, Puerto Rico | $beachCount Playas";
    $pageH1 = "Playas Cerca de $locName";
    $pageDescription = "Encuentra $beachCount playas cerca de $locName en $loc[region] de Puerto Rico.";
}

$canonicalUrl = absoluteUrl('/beaches-near-' . $locSlug);
$extraHead = websiteSchema();

$breadcrumbs = [
    ['name' => $lang === 'es' ? 'Inicio' : 'Home', 'url' => routeUrl('home', $lang)],
    ['name' => $pageH1]
];

$bodyVariant = 'collection-light';
include APP_ROOT . '/components/header.php';

$ratedBeaches = array_filter($beaches, fn($b) => !empty($b['google_rating']));
$avgRating = !empty($ratedBeaches) ? array_sum(array_column($ratedBeaches, 'google_rating')) / count($ratedBeaches) : 0;
?>

<section class="relative bg-gradient-to-b from-slate-900 via-slate-800 to-slate-700 text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h($pageH1) ?></h1>
        <p class="text-lg md:text-xl text-warm-700 max-w-3xl mx-auto mb-6">
            <?= $lang === 'es' ? "Descubre $beachCount playas a menos de {$radiusKm}km de $locName, Puerto Rico." : "Discover $beachCount beaches within {$radiusKm}km of $locName on $loc[region] of Puerto Rico. From San Juan, $locName is about $loc[drive]." ?>
        </p>
        <div class="flex flex-wrap justify-center gap-4 text-sm text-warm-500">
            <span class="bg-slate-700/50 px-3 py-1 rounded-full"><?= $beachCount ?> <?= $lang === 'es' ? 'playas' : 'beaches' ?></span>
            <?php if ($avgRating > 0): ?>
            <span class="bg-slate-700/50 px-3 py-1 rounded-full">★ <?= number_format($avgRating, 1) ?> <?= $lang === 'es' ? 'promedio' : 'avg rating' ?></span>
            <?php endif; ?>
            <span class="bg-slate-700/50 px-3 py-1 rounded-full"><?= $lang === 'es' ? "~$loc[drive] desde San Juan" : "~$loc[drive] from San Juan" ?></span>
        </div>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2"><?= $lang === 'es' ? "Todas las $beachCount Playas" : "All $beachCount Beaches" ?></h2>
        <p class="text-gray-600 mb-8"><?= $lang === 'es' ? 'Ordenadas por distancia' : 'Sorted by distance from ' . h($locName) ?></p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php $beachIndex = 0;
foreach (array_slice($beaches, 0, 30) as $beach):
$beachIndex++;
                $distKm = round($beach['distance_km'], 1);
                $distMi = round($distKm * 0.621371, 1);
            ?>
            <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
               class="group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="aspect-[4/3] overflow-hidden relative">
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="<?= h($beach['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="<?= $beachIndex <= 6 ? "eager" : "lazy" ?>" width="400" height="300">
                    <span class="absolute top-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full">
                        <?= $distKm ?> km / <?= $distMi ?> mi
                    </span>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 group-hover:text-amber-700 transition-colors"><?= h($beach['name']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= h($beach['municipality']) ?></p>
                    <div class="flex items-center gap-2 mt-2">
                        <?php if (!empty($beach['google_rating'])): ?>
                        <span class="text-sm text-amber-600">★ <?= number_format($beach['google_rating'], 1) ?></span>
                        <?php if (!empty($beach['google_review_count'])): ?>
                        <span class="text-xs text-gray-400">(<?= number_format($beach['google_review_count']) ?>)</span>
                        <?php endif; endif; ?>
                    </div>
                    <?php if (!empty($beach['tags'])): ?>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <?php foreach (array_slice($beach['tags'], 0, 3) as $tag): ?>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"><?= h(ucwords(str_replace('-', ' ', $tag))) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($beachCount > 30): ?>
        <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach (array_slice($beaches, 30) as $beach):
                $distKm = round($beach['distance_km'], 1);
            ?>
            <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])) ?>"
               class="flex items-center gap-3 bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                <img src="<?= h($beach['cover_image']) ?>" alt="<?= h($beach['name']) ?>"
                     class="w-16 h-16 object-cover rounded-lg flex-shrink-0" loading="lazy" width="64" height="64">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate"><?= h($beach['name']) ?></p>
                    <p class="text-xs text-gray-500"><?= h($beach['municipality']) ?> · <?= $distKm ?> km</p>
                    <?php if (!empty($beach['google_rating'])): ?>
                    <span class="text-xs text-amber-600">★ <?= number_format($beach['google_rating'], 1) ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Other Locations -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center"><?= $lang === 'es' ? 'Explorar Otras Áreas' : 'Explore Other Areas' ?></h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($locations as $slug => $l):
                if ($slug === $locSlug) continue;
            ?>
            <a href="/beaches-near-<?= h($slug) ?>"
               class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow text-center">
                <span class="font-medium text-gray-900"><?= h($l['name']) ?></span>
                <p class="text-xs text-gray-500 mt-1"><?= h($l['region']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-12 bg-sunset-400 text-ocean-900">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= $lang === 'es' ? '¿No sabes cuál playa es para ti?' : 'Not sure which beach is right for you?' ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= $lang === 'es' ? 'Toma nuestro quiz de 60 segundos.' : 'Take our 60-second quiz and we\'ll recommend the perfect beaches for you.' ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-white text-amber-700 hover:bg-slate-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            <?= $lang === 'es' ? 'Tomar el Quiz' : 'Take the Beach Quiz' ?>
        </a>
    </div>
</section>

<?php include APP_ROOT . '/components/footer.php'; ?>
