<?php
/**
 * "Beaches Near Me" — /beaches-near-me and /es/playas-cerca-de-mi
 *
 * Why this page exists: GSC shows /beaches-near-san-juan taking ~86% of its
 * impressions from "beaches near me" and "beach near me" — queries with no city
 * in them — at 0.16-0.31% CTR, while San-Juan-specific queries on the same URL
 * convert at 2.75-6.25%. Google had no page matching the "wherever I am" intent,
 * so it substituted a city page and searchers correctly ignored it.
 *
 * The page must stand on its own without JavaScript, because that is what
 * Googlebot indexes: a real directory of every coastal municipality, the city
 * proximity pages, top-rated beaches, and FAQs answering the actual question.
 * Geolocation is a progressive enhancement layered on top (assets/js/near-me.js),
 * not the substance of the page.
 */
require_once $_SERVER["DOCUMENT_ROOT"] . "/../bootstrap.php";
require_once APP_ROOT . "/inc/db.php";
require_once APP_ROOT . "/inc/helpers.php";
require_once APP_ROOT . "/inc/constants.php";
require_once APP_ROOT . "/inc/locale_routes.php";
require_once APP_ROOT . "/inc/i18n.php";
require_once APP_ROOT . "/components/seo-schemas.php";

$lang = getCurrentLanguage();
$isEs = $lang === 'es';

// Highest-signal beaches island-wide. This is the no-JS default: a searcher who
// declines the location prompt still lands on something useful and crawlable.
$beaches = query(
    "SELECT * FROM beaches
     WHERE publish_status = 'published'
       AND lat IS NOT NULL AND lng IS NOT NULL
       AND (location_type = 'beach' OR location_type IS NULL)
     ORDER BY (google_rating IS NULL), google_rating DESC, google_review_count DESC
     LIMIT 30"
);
if (empty($beaches)) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}
attachBeachMetadata($beaches);

$totalBeaches = (int) (queryOne(
    "SELECT COUNT(*) AS c FROM beaches
     WHERE publish_status = 'published' AND lat IS NOT NULL
       AND (location_type = 'beach' OR location_type IS NULL)"
)['c'] ?? 0);

$muniRows = query(
    "SELECT municipality, COUNT(*) AS c FROM beaches
     WHERE publish_status = 'published' AND (location_type = 'beach' OR location_type IS NULL)
     GROUP BY municipality ORDER BY municipality ASC"
);

$pageH1 = $isEs ? 'Playas Cerca de Mí' : 'Beaches Near Me';
$pageTitle = $isEs
    ? "Playas Cerca de Mí — Encuentra la Playa Más Cercana en Puerto Rico"
    : "Beaches Near Me — Find the Closest Beach in Puerto Rico";
$pageDescription = $isEs
    ? "Encuentra las playas más cercanas a tu ubicación en Puerto Rico. Ordena $totalBeaches playas por distancia y mira condiciones, estacionamiento y cómo llegar."
    : "Find the closest beaches to your current location in Puerto Rico. Sort all $totalBeaches beaches by distance and see conditions, parking, and how to get there.";

$canonicalUrl = absoluteUrl(routeUrl('beaches_near_me', $lang));

$faqs = $isEs ? [
    ['question' => '¿Cómo encuentro la playa más cercana a mí?',
     'answer'   => 'Pulsa "Usar mi ubicación" en esta página y tu navegador le pedirá permiso para compartir tu posición. Ordenamos las ' . $totalBeaches . ' playas publicadas de Puerto Rico por distancia real desde donde estás. Tu ubicación se usa solo en tu navegador para calcular distancias; no la guardamos.'],
    ['question' => '¿Cuál es la playa más cercana a San Juan?',
     'answer'   => 'Desde el área metropolitana, las playas de Isla Verde, Ocean Park y Condado están a menos de 15 minutos. Balneario de Carolina y Escambrón son las opciones con más facilidades. Ve la página de playas cerca de San Juan para la lista completa ordenada por distancia.'],
    ['question' => '¿Necesito dar mi ubicación para usar esta página?',
     'answer'   => 'No. Sin permiso de ubicación puedes explorar por municipio o por área más abajo — cada municipio costero de Puerto Rico tiene su propia página con todas sus playas.'],
    ['question' => '¿Qué playas tienen estacionamiento y baños cerca?',
     'answer'   => 'Los balnearios públicos suelen tener estacionamiento, baños y salvavidas durante horas de operación. Cada página de playa indica estacionamiento, facilidades y acceso.'],
] : [
    ['question' => 'How do I find the beach closest to me?',
     'answer'   => 'Tap "Use my location" on this page and your browser will ask permission to share your position. We rank all ' . $totalBeaches . ' published Puerto Rico beaches by actual distance from where you are. Your location is used only in your browser to calculate distances — we never store it.'],
    ['question' => 'What is the closest beach to San Juan?',
     'answer'   => 'From the metro area, Isla Verde, Ocean Park and Condado beaches are all under 15 minutes away. Balneario de Carolina and Escambrón offer the most facilities. See the beaches near San Juan page for the full list sorted by distance.'],
    ['question' => 'Do I have to share my location to use this page?',
     'answer'   => 'No. Without location access you can browse by municipality or by area below — every coastal municipality in Puerto Rico has its own page listing all of its beaches.'],
    ['question' => 'Which beaches have parking and restrooms nearby?',
     'answer'   => 'Public balnearios generally have parking, restrooms and lifeguards during operating hours. Every beach page lists parking, facilities and access details.'],
];

$extraHead = websiteSchema() . faqSchema($faqs);

$breadcrumbs = [
    ['name' => $isEs ? 'Inicio' : 'Home', 'url' => routeUrl('home', $lang)],
    ['name' => $pageH1],
];

// Progressive-enhancement block. Rendered as a plain, readable prompt so the
// no-JS experience is a sentence rather than a dead button; near-me.js takes
// over on load and swaps in distance-sorted results.
$btnLabel   = $isEs ? 'Usar mi ubicación' : 'Use my location';
$helpText   = $isEs
    ? 'Calculamos las distancias en tu navegador. No guardamos tu ubicación.'
    : 'Distances are calculated in your browser. We never store your location.';
$listHeading = $isEs ? 'Playas más cercanas a ti' : 'Beaches closest to you';

$extraHtml = '<div id="near-me" class="rd-nearme" data-total="' . h((string) $totalBeaches) . '"'
    . ' data-lang="' . h($lang) . '"'
    . ' data-heading="' . h($listHeading) . '">'
    . '<button type="button" id="near-me-btn" class="rd-nearme__btn" hidden>' . h($btnLabel) . '</button>'
    . '<p class="rd-nearme__help">' . h($helpText) . '</p>'
    . '<div id="near-me-status" class="rd-nearme__status" role="status" aria-live="polite"></div>'
    . '<div id="near-me-results" class="rd-nearme__results"></div>'
    . '</div>';

$siblingSlugs = ['san-juan' => 'San Juan', 'ponce' => 'Ponce', 'rincon' => 'Rincón', 'fajardo' => 'Fajardo',
    'aguadilla' => 'Aguadilla', 'arecibo' => 'Arecibo', 'dorado' => 'Dorado', 'cabo-rojo' => 'Cabo Rojo',
    'humacao' => 'Humacao', 'mayaguez' => 'Mayagüez', 'vega-baja' => 'Vega Baja'];
$siblings = [];
foreach ($siblingSlugs as $slug => $label) {
    $siblings[] = [
        $isEs ? "Playas cerca de $label" : "Beaches near $label",
        $slug === 'san-juan' ? routeUrl('beaches_near_san_juan', $lang) : routeUrl('beaches_near', $lang, ['location' => $slug]),
        null,
    ];
}

$municipalities = [];
foreach ($muniRows ?: [] as $row) {
    $municipalities[] = [
        $row['municipality'],
        routeUrl('municipality', $lang, ['municipality' => slugify($row['municipality'])]),
        (int) $row['c'],
    ];
}

$listing = [
    'breadcrumbs' => [
        [$isEs ? 'Inicio' : 'Home', routeUrl('home', $lang)],
        [$pageH1, null],
    ],
    'eyebrow' => $isEs ? 'Encuentra tu playa' : 'Find your beach',
    'h1' => $pageH1,
    'intro' => [
        $isEs
            ? "Comparte tu ubicación y ordenamos las $totalBeaches playas publicadas de Puerto Rico por distancia real desde donde estás — con condiciones, estacionamiento y cómo llegar."
            : "Share your location and we'll rank all $totalBeaches published Puerto Rico beaches by real distance from where you are — with conditions, parking and directions.",
    ],
    'stats' => [
        [(string) $totalBeaches, $isEs ? 'playas' : 'beaches'],
        [(string) count($municipalities), $isEs ? 'municipios' : 'municipalities'],
    ],
    'extraHtml' => $extraHtml,
    'beachesHeading' => $isEs ? 'Playas mejor valoradas' : 'Top-rated beaches',
    'beachesSub' => $isEs
        ? 'Mientras tanto, estas son las mejor valoradas de la isla'
        : 'In the meantime, these are the island\'s highest rated',
    'beaches' => $beaches,
    'municipalities' => $municipalities,
    'municipalitiesHeading' => $isEs ? 'Explorar por municipio' : 'Browse by municipality',
    'siblings' => $siblings,
    'siblingsHeading' => $isEs ? 'Playas cerca de ciudades' : 'Beaches near cities',
    // listing.php destructures positionally ([$q, $a]) while faqSchema() needs
    // the associative shape, so $faqs stays associative and is mapped here.
    'faqs' => array_map(static fn(array $f): array => [$f['question'], $f['answer']], $faqs),
    'faqHeading' => $isEs ? 'Preguntas frecuentes' : 'Frequently asked questions',
    'quizCta' => true,
];

$bodyVariant = 'collection-dark';
$redesignLayout = useRedesign();
include APP_ROOT . '/components/header.php';

// Set after header.php so cspNonceAttr() is defined. Footer echoes $extraScripts.
$extraScripts = '<script defer src="/assets/js/near-me.js?v=1" ' . cspNonceAttr() . '></script>';

if ($redesignLayout) {
    include APP_ROOT . '/templates/redesign/listing.php';
    include APP_ROOT . '/components/footer.php';
    return;
}

// Classic fallback: same substance, unstyled shell.
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h($pageH1) ?></h1>
    <p class="text-lg mb-6"><?= h($listing['intro'][0]) ?></p>
    <?= $extraHtml ?>
    <h2 class="text-2xl font-bold mt-10 mb-4"><?= h($listing['municipalitiesHeading']) ?></h2>
    <ul class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <?php foreach ($municipalities as [$name, $url, $count]): ?>
            <li><a class="text-ocean-600 hover:underline" href="<?= h($url) ?>"><?= h($name) ?> (<?= (int) $count ?>)</a></li>
        <?php endforeach; ?>
    </ul>
    <h2 class="text-2xl font-bold mt-10 mb-4"><?= h($listing['siblingsHeading']) ?></h2>
    <ul class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($siblings as [$label, $url]): ?>
            <li><a class="text-ocean-600 hover:underline" href="<?= h($url) ?>"><?= h($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <h2 class="text-2xl font-bold mt-10 mb-4"><?= h($listing['faqHeading']) ?></h2>
    <?php foreach ($faqs as $faq): ?>
        <details class="mb-3"><summary class="font-semibold cursor-pointer"><?= h($faq['question']) ?></summary>
        <p class="mt-2"><?= h($faq['answer']) ?></p></details>
    <?php endforeach; ?>
</section>
<?php
include APP_ROOT . '/components/footer.php';
