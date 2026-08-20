<?php
/**
 * Redesign v2 — homepage template.
 * Rendered inside <main> (header.php output <head>+<body>, nav skipped).
 * In scope from index.php: $siteStats, $publishedCount, $popularBeaches, $lang.
 */
require_once APP_ROOT . '/inc/beach_score.php';
require_once APP_ROOT . '/inc/island_chart.php';
require_once APP_ROOT . '/inc/weather.php';

$lang = $lang ?? (function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en');
$isEs = $lang === 'es';
$rdMapMode = (($viewMode ?? '') === 'map');

// ---- build the beach dataset (real, published) ----
$rows = query(
    "SELECT id, slug, name, municipality, cover_image, surf, sargassum, access_label,
            google_rating, avg_user_rating, google_review_count, safe_for_children, lat, lng
     FROM beaches
     WHERE publish_status = 'published' AND (location_type = 'beach' OR location_type IS NULL)"
);
attachBeachMetadata($rows);

$surfWord = ['calm' => 'flat', 'small' => '1–2 ft', 'medium' => '3–4 ft', 'large' => 'head-high'];
$regionCounts = array_fill_keys(['metro', 'north', 'west', 'south', 'east', 'cays'], 0);
$rd = [];
foreach ($rows as $b) {
    $sc = computeBeachScore($b, $b['tags'] ?? [], $b['amenities'] ?? []);
    $region = islandRegionForMunicipality($b['municipality'] ?? '');
    if ($region) { $regionCounts[$region]++; }
    $surf = strtolower((string) ($b['surf'] ?? ''));
    // crowd derived from the seclusion sub-score
    $secl = 55; foreach ($sc['bars'] as $bar) { if ($bar[0] === 'Seclusion') { $secl = $bar[1]; } }
    $crowd = $secl >= 74 ? 'low' : ($secl >= 45 ? 'med' : 'high');
    $imageUrl = function_exists('getBeachImageUrl')
        ? getBeachImageUrl($b, 'medium')
        : (($b['cover_image'] ?? '') ?: '/images/beaches/placeholder-beach.webp');
    $rd[] = [
        'id' => (string) $b['id'], 'n' => $b['name'], 'slug' => $b['slug'], 'm' => $b['municipality'],
        'rg' => $region, 'img' => $imageUrl,
        'sc' => $sc['overall'], 'rt' => round((float) $sc['rating'], 1),
        'water' => $surf ?: 'calm', 'surf' => $surfWord[$surf] ?? 'flat', 'crowd' => $crowd,
        'bars' => $sc['bars'], 't' => array_values($b['tags'] ?? []),
        'lat' => (float) ($b['lat'] ?? 0), 'lng' => (float) ($b['lng'] ?? 0),
    ];
}
// default order: Beach Score desc
usort($rd, fn($a, $b) => $b['sc'] <=> $a['sc']);
$total = count($rd);
$municipios = count(array_unique(array_column($rd, 'm')));

// Hero category chips mirror the classic homepage category copy. Each one is
// still backed by a real tag so the JS filter works.
$chipTags = [
    'surfing', 'snorkeling', 'family-friendly', 'secluded', 'swimming', 'scenic',
];
$chipLabels = [
    'surfing' => __('pages.home.category_surfing'),
    'snorkeling' => __('pages.home.category_snorkeling'),
    'family-friendly' => __('pages.home.category_family'),
    'secluded' => __('pages.home.category_secluded'),
    'swimming' => __('tags.swimming'),
    'scenic' => __('tags.scenic'),
];

// Strings shared between the PHP-rendered first page of tiles and the JS
// re-renders (filters, sort, load-more) — keep the two in sync.
$rdI18n = [
    'beach' => $isEs ? 'playa' : 'beach',
    'beaches' => $isEs ? 'playas' : 'beaches',
    'viewBeach' => $isEs ? 'Ver playa →' : 'View beach →',
    'noMatch' => $isEs ? 'Ninguna playa coincide — prueba otra costa o búsqueda.' : 'No beaches match — try another coast or search.',
    'findYourBeach' => $isEs ? 'Encuentra tu playa' : 'Find your beach',
    'wholeIsland' => $isEs ? 'Toda la isla' : 'The whole island',
    'byCoast' => $isEs ? 'Filtrado por costa' : 'Filtered by coast',
    'manyMunicipios' => $isEs ? '· varios municipios' : '· many municipalities',
    'municipalities' => $isEs ? 'municipios' : 'municipalities',
    'save' => $isEs ? 'Guardar' : 'Save',
    'beachScore' => $isEs ? 'Puntaje' : 'Score',
    'showMore' => $isEs ? 'Ver 12 más' : 'Show 12 more',
    'showRemaining' => $isEs ? 'Quedan %s de %s playas' : '%s of %s beaches left',
    'browseDirectory' => $isEs ? 'Ver directorio' : 'Browse directory',
    'closestBeaches' => $isEs ? 'Playas cercanas' : 'Closest beaches',
    'sortedByLocation' => $isEs ? 'Ordenadas por tu ubicación' : 'Sorted by your location',
    'mapTitle' => $isEs ? 'Mapa de Playas de Puerto Rico' : 'Puerto Rico Beach Map',
    'mapIntro' => $isEs ? 'Explora playas por costa, pueblo y ambiente sin salir del mapa.' : 'Explore beaches by coast, town, and vibe without leaving the map.',
    'mapSearch' => $isEs ? 'Buscar en el mapa...' : 'Search the map...',
    'mapAllCoasts' => $isEs ? 'Todas las costas' : 'All coasts',
    'mapSelected' => $isEs ? 'Seleccionada' : 'Selected',
    'mapOpen' => $isEs ? 'Abrir playa' : 'Open beach',
    'mapClusterHint' => $isEs ? 'Mostrando costas. Elige una costa o busca una playa para ver puntos individuales.' : 'Showing coast groups. Pick a coast or search a beach to reveal individual points.',
    'mapPinHint' => $isEs ? 'Mostrando playas individuales en el mapa.' : 'Showing individual beaches on the map.',
    'mapBest' => $isEs ? 'mejor' : 'best',
    'nearMe' => $isEs ? '⌖ Cerca de mí' : '⌖ Near me',
    'useMyLocation' => $isEs ? '⌖ Cerca de mí' : '⌖ Use my location',
    'locating' => $isEs ? '⌖ Ubicando…' : '⌖ Locating…',
    'overall' => $isEs ? '⭐ General' : '⭐ Overall',
    'water' => ['calm' => $isEs ? 'calmada' : 'calm', 'small' => $isEs ? 'suave' : 'small', 'medium' => $isEs ? 'media' : 'medium', 'large' => $isEs ? 'fuerte' : 'large'],
    'crowd' => ['low' => $isEs ? 'poco' : 'low', 'med' => $isEs ? 'medio' : 'med', 'high' => $isEs ? 'mucho' : 'high'],
    'barLabels' => [
        'Calm water' => $isEs ? '🌊 Calma' : '🌊 Calm', 'Snorkeling' => '🤿 Snorkel',
        'Seclusion' => $isEs ? '🌾 Tranquila' : '🌾 Quiet', 'Family' => $isEs ? '👨‍👩‍👧 Familia' : '👨‍👩‍👧 Family',
        'Facilities' => $isEs ? '🚻 Servicios' : '🚻 Facilities',
    ],
];
$regionNames = [
    'north' => $isEs ? 'Costa Norte' : 'North Coast', 'metro' => $isEs ? 'Metro · San Juan' : 'Metro · San Juan',
    'west' => 'Porta del Sol', 'south' => $isEs ? 'Costa Sur' : 'South Coast',
    'east' => $isEs ? 'Este · Fajardo' : 'East · Fajardo', 'cays' => $isEs ? 'Los Cayos' : 'The Cays',
];
$regionShortNames = [
    'north' => $isEs ? 'Norte' : 'North',
    'metro' => 'Metro',
    'west' => $isEs ? 'Oeste' : 'West',
    'south' => $isEs ? 'Sur' : 'South',
    'east' => $isEs ? 'Este' : 'East',
    'cays' => $isEs ? 'Cayos' : 'Cays',
];
$heroSuggestions = [
    ['label' => 'Flamenco', 'query' => 'Flamenco'],
    ['label' => 'Culebra', 'query' => 'Culebra'],
    ['label' => $isEs ? 'Snorkel' : 'Snorkeling', 'tag' => 'snorkeling'],
    ['label' => $isEs ? 'Agua calmada' : 'Calm water', 'sort' => 'calm'],
];
$beachUrlPrefix = $isEs ? '/es/playa/' : '/beach/';

/** Server-rendered beach tile — MUST mirror tile() in redesign-home.js. */
$rdTile = function (array $b, int $rank) use ($rdI18n, $beachUrlPrefix, &$favIds): string {
    $url = $beachUrlPrefix . $b['slug'];
    // Honest signals only: the corner badge and hover show the real Google
    // rating (never the internal heuristic score, which stays ranking-only).
    $rt = !empty($b['rt']) ? number_format($b['rt'], 1) : '';
    $fav = in_array($b['id'], $favIds, true);
    return '<div class="btile">'
        . '<a class="btile-link" href="' . h($url) . '">'
        . '<div class="btile-photo" style="background-image:url(\'' . h($b['img']) . '\')"></div><div class="btile-grad"></div>'
        . '<div class="btile-rest"><div class="bt-top"><span class="bt-rank">' . $rank . '</span>'
        . ($rt !== '' ? '<span class="bt-score-mini">⭐ ' . $rt . '</span>' : '')
        . '</div>'
        . '<div class="bt-name">' . h($b['n']) . '</div><div class="bt-muni">' . h($b['m']) . '</div>'
        . '<div class="bt-rest-stats"><span>🌊 ' . h($rdI18n['water'][$b['water']] ?? $b['water']) . '</span><span>👥 ' . h($rdI18n['crowd'][$b['crowd']] ?? $b['crowd']) . '</span></div></div>'
        . '<div class="btile-hover"><div class="bt-hovtop"><span>' . ($rt !== '' ? '⭐ ' . $rt . ' Google' : h($b['m'])) . '</span></div>'
        . '<span class="bt-view">' . h($rdI18n['viewBeach']) . '</span></div></a>'
        . '<button class="bt-fav' . ($fav ? ' on' : '') . '" type="button" data-id="' . h($b['id']) . '" title="' . h($rdI18n['save']) . '">' . ($fav ? '♥' : '♡') . '</button></div>';
};

// island-representative conditions (San Juan) — cached
$w = null; try { $w = getWeatherForLocation(18.46, -66.11); } catch (\Throwable $e) {}
$wc = $w['current'] ?? [];

$regionMeta = [
    'north' => ['North Coast', '31%', '21%', [200, 170]],
    'metro' => ['Metro · San Juan', '55%', '25%', [340, 172]],
    'west'  => ['Porta del Sol', '10%', '50%', [90, 210]],
    'south' => ['South Coast', '37%', '80%', [290, 250]],
    'east'  => ['East · Fajardo', '71%', '65%', [470, 208]],
    'cays'  => ['The Cays', '88%', '25%', [508, 196]],
];
$popular = array_slice($popularBeaches ?? [], 0, 3);
$rdBySlug = [];
foreach ($rd as $b) { $rdBySlug[$b['slug']] = $b; }
$barValue = static function (array $b, string $label): int {
    foreach ($b['bars'] as $bar) {
        if (($bar[0] ?? '') === $label) { return (int) ($bar[1] ?? 0); }
    }
    return 0;
};
$pickByBar = static function (array $items, string $label, array $exclude = []) use ($barValue): ?array {
    $pool = array_values(array_filter($items, static fn($b) => !in_array($b['id'], $exclude, true)));
    usort($pool, static function ($a, $b) use ($label, $barValue) {
        $cmp = $barValue($b, $label) <=> $barValue($a, $label);
        return $cmp !== 0 ? $cmp : (($b['sc'] ?? 0) <=> ($a['sc'] ?? 0));
    });
    return $pool[0] ?? null;
};
$todayPicks = [];
$usedPickIds = [];
foreach ([
    ['Calm water', $isEs ? 'Agua calmada' : 'Calm water', $isEs ? 'Mejor opción para un baño fácil.' : 'Best shot for an easier swim.'],
    ['Family', $isEs ? 'Familias' : 'Families', $isEs ? 'Mezcla agua tranquila, acceso y servicios.' : 'Balances calmer water, access, and facilities.'],
    ['Snorkeling', $isEs ? 'Snorkel' : 'Snorkeling', $isEs ? 'Mejor potencial para agua clara y arrecife.' : 'Best clear-water and reef potential.'],
] as $pickMeta) {
    $picked = $pickByBar($rd, $pickMeta[0], $usedPickIds);
    if ($picked) {
        $picked['pick_label'] = $pickMeta[1];
        $picked['pick_reason'] = $pickMeta[2];
        $picked['pick_score'] = $barValue($picked, $pickMeta[0]);
        $todayPicks[] = $picked;
        $usedPickIds[] = $picked['id'];
    }
}
$windSpeed = isset($wc['wind_speed']) ? (int) round((float) $wc['wind_speed']) : null;
$weatherTemp = isset($wc['temperature']) ? (int) round((float) $wc['temperature']) : null;
$weatherDesc = trim((string) ($wc['description'] ?? ''));
$todayRead = $isEs
    ? 'Compara agua calmada, acceso y ambiente antes de salir.'
    : 'Compare calm water, access, and vibe before you head out.';
if ($windSpeed !== null) {
    if ($windSpeed >= 22) {
        $todayRead = $isEs
            ? 'El viento está fuerte. Empieza por playas protegidas y revisa el mapa.'
            : 'Wind is up. Start with protected beaches and check the map.';
    } elseif ($windSpeed >= 15) {
        $todayRead = $isEs
            ? 'Brisa moderada. Las mañanas y bahías protegidas suelen ser más cómodas.'
            : 'Moderate breeze. Mornings and protected bays are usually easier.';
    } else {
        $todayRead = $isEs
            ? 'Viento suave. Buen día para comparar opciones de nado y snorkel.'
            : 'Light wind. Good day to compare swim and snorkel picks.';
    }
}
$popularReason = static function (array $b) use ($isEs): string {
    $tags = $b['t'] ?? [];
    $labels = [
        'swimming' => $isEs ? 'Buena para nadar' : 'Good for swimming',
        'snorkeling' => $isEs ? 'Snorkel' : 'Snorkeling',
        'surfing' => $isEs ? 'Surf' : 'Surf break',
        'family-friendly' => $isEs ? 'Familiar' : 'Family-friendly',
        'scenic' => $isEs ? 'Escénica' : 'Scenic',
        'popular' => $isEs ? 'Popular' : 'Popular',
    ];
    foreach ($labels as $tag => $label) {
        if (in_array($tag, $tags, true)) { return $label; }
    }
    return $isEs ? 'Muy reseñada' : 'Highly reviewed';
};
$calmUrl = getLocalizedTagPageUrl('calm-waters', $lang);
$mapUrl = ($isEs ? '/es/' : '/') . '?view=map';
$guideCards = [
    ['slug' => 'snorkeling-guide', 'type' => 'Guide', 'emoji' => '🤿', 'title' => 'Best Snorkeling Spots & What to Bring', 'desc' => 'Crystal-clear waters, vibrant reefs, and the gear you need.'],
    ['slug' => 'surfing-guide', 'type' => 'Guide', 'emoji' => '🏄‍♂️', 'title' => 'Surfing Puerto Rico: A Complete Guide', 'desc' => 'From beginner breaks to world-class waves across the island.'],
    ['slug' => 'family-beach-vacation-planning', 'type' => 'Planning', 'emoji' => '👨‍👩‍👧', 'title' => 'Family Beach Vacation Planning', 'desc' => 'Kid-friendly beaches, safety tips, and what to pack.'],
    ['slug' => 'beach-safety-tips', 'type' => 'Safety', 'emoji' => '🛟', 'title' => 'Beach Safety Tips for Visitors', 'desc' => 'Currents, sun protection, and local safety guidelines.'],
];

// Favorites for the tile hearts. index.php's lookup runs before header.php
// starts the session, so re-query here now that the session is active.
$favIds = [];
if (isAuthenticated() && !empty($_SESSION['user_id'])) {
    $favIds = array_map('strval', array_column(
        query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]),
        'beach_id'
    ));
}

// ---- homepage design settings (admin-editable: /admin/homepage-design) ----
require_once APP_ROOT . '/inc/settings.php';
require_once APP_ROOT . '/inc/homepage_fonts.php';
$hpDesign = $rdDesign ?? getHomepageDesign();
$hpEditor = $rdEditorMode ?? false;
$managedHomeHero = pageHeroResolve('home');
$managedHomeAttrs = pageHeroAttributes('home');
$heroStyle = '';
if ($managedHomeHero === null && $hpDesign['bg_mode'] === 'color' && $hpDesign['bg_color'] !== 'default') {
    $heroStyle = 'background:' . h($hpDesign['bg_color']);
}
$heroClasses = 'hero-band managed-page-hero' . ($managedHomeHero === null && homepageHeroIsLight($hpDesign) ? ' dark-hero' : '');
// sticker text color mirrors the design-workbench palette rules
$stickerInk = function (string $c): string {
    $light = ['#F7E14C', '#E9A81F', '#F5EFDF'];
    if (in_array(strtoupper($c), array_map('strtoupper', $light), true)) { return '#D2352A'; }
    return strtoupper($c) === '#EE3640' ? '#F8E14A' : '#FFFFFF';
};
$stickerSvg = [
    'star'  => "<svg viewBox='0 0 100 100'><path d='M50 0 L59 41 L100 50 L59 59 L50 100 L41 59 L0 50 L41 41 Z'/></svg>",
    'swash' => "<svg viewBox='0 0 190 32' preserveAspectRatio='none'><path d='M7 20 C45 30 75 6 105 15 S160 30 183 12'/></svg>",
];
?>
<div class="rd rd-home">

<?php if ($rdMapMode): ?>
<!-- ===== MAP VIEW ===== -->
<header class="mapview-hero managed-page-hero"<?= $managedHomeAttrs ?>>
  <div class="wrap">
    <div class="mapview-head">
      <div>
        <p class="eyebrow"><?= h($isEs ? 'Mapa interactivo' : 'Interactive map') ?></p>
        <h1><?= h($rdI18n['mapTitle']) ?></h1>
        <p><?= h($rdI18n['mapIntro']) ?></p>
      </div>
      <a class="mapview-back" href="<?= h(routeUrl('home', $lang)) ?>#beaches"><?= h($rdI18n['browseDirectory']) ?></a>
    </div>
    <div class="mapview-shell" id="rdMapView">
      <section class="mapview-map" aria-label="<?= h($rdI18n['mapTitle']) ?>">
        <div class="mapview-toolbar">
          <div class="dir-search map-search">
            <svg class="mag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
            <input id="rdMapSearch" placeholder="<?= h($rdI18n['mapSearch']) ?>" aria-label="<?= h($rdI18n['mapSearch']) ?>">
          </div>
          <select class="dir-select" id="rdMapSort" aria-label="<?= h($isEs ? 'Ordenar mapa' : 'Sort map') ?>">
            <option value="score"><?= h($isEs ? 'Puntuación' : 'Beach Score') ?></option>
            <option value="rating"><?= h($isEs ? 'Mejor calificadas' : 'Top rated') ?></option>
            <option value="calm"><?= h($isEs ? 'Agua calmada' : 'Calmest water') ?></option>
            <option value="crowd"><?= h($isEs ? 'Menos gentío' : 'Least crowded') ?></option>
          </select>
        </div>
        <p class="map-mode-note" id="rdMapModeNote"><?= h($rdI18n['mapClusterHint']) ?></p>
        <div class="mapcanvas">
          <svg class="mapcanvas-chart" viewBox="0 0 560 360" role="img" aria-hidden="true">
            <defs>
              <linearGradient id="rdMapSea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E6F3FC"/><stop offset="1" stop-color="#BFDFF3"/></linearGradient>
              <linearGradient id="rdMapSand" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F0C452"/><stop offset="1" stop-color="#D0982A"/></linearGradient>
            </defs>
            <rect class="rd-rect" x="6" y="8" width="548" height="344" rx="14"/>
            <line class="grat" x1="150" y1="30" x2="150" y2="345"/><line class="grat" x1="430" y1="30" x2="430" y2="345"/>
            <text class="coord" x="134" y="26">67°W</text><text class="coord" x="412" y="26">66°W</text>
            <path class="contour" style="stroke:#8FC6EE;stroke-width:1.1;opacity:.9" transform="translate(247.5,201.7) scale(1.02) translate(-247.5,-201.7)" d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
            <path class="contour" style="stroke:#4093CE;stroke-width:.85;opacity:.5" transform="translate(247.5,201.7) scale(1.075) translate(-247.5,-201.7)" d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
            <path class="island" d="<?= ISLAND_CHART_ISLAND_D ?>"/>
            <path class="cay" d="<?= ISLAND_CHART_CUL_D ?>"/><path class="cay" d="<?= ISLAND_CHART_VIE_D ?>"/>
          </svg>
          <div class="map-pin-layer" id="rdMapPins" aria-label="<?= h($isEs ? 'Marcadores de playas' : 'Beach markers') ?>"></div>
        </div>
        <div class="mapview-regions" role="group" aria-label="<?= h($isEs ? 'Filtrar por región' : 'Filter by region') ?>">
          <button type="button" class="map-region is-on" data-map-region=""><?= h($rdI18n['mapAllCoasts']) ?></button>
          <?php foreach ($regionShortNames as $key => $label): ?>
          <button type="button" class="map-region" data-map-region="<?= h($key) ?>"><?= h($label) ?></button>
          <?php endforeach; ?>
        </div>
      </section>
      <aside class="mapview-list" aria-live="polite">
        <div class="mapview-list-head">
          <span id="rdMapCount"><?= number_format($total) ?> <?= h($rdI18n['beaches']) ?></span>
          <small id="rdMapSummary"><?= h($rdI18n['wholeIsland']) ?></small>
        </div>
        <div id="rdMapList" class="mapview-results">
          <?php foreach (array_slice($rd, 0, 8) as $i => $b): ?>
          <a class="map-result" href="<?= h($beachUrlPrefix . $b['slug']) ?>" data-id="<?= h($b['id']) ?>">
            <span class="map-result-rank"><?= $i + 1 ?></span>
            <span class="map-result-copy"><b><?= h($b['n']) ?></b><small><?= h($b['m']) ?> · <?= h($rdI18n['beachScore']) ?> <?= (int) $b['sc'] ?></small></span>
          </a>
          <?php endforeach; ?>
        </div>
      </aside>
    </div>
  </div>
</header>
<?php else: ?>
<!-- ===== HERO BAND ===== -->
<header class="<?= $heroClasses ?>"<?= $managedHomeAttrs ?><?= $heroStyle ? ' style="' . $heroStyle . '"' : '' ?>>
  <?php if ($hpDesign['bg_mode'] === 'photo' && $hpDesign['bg_photo'] !== ''): ?>
  <div class="hero-bg" style="background-image:url('<?= h($hpDesign['bg_photo']) ?>');opacity:<?= $hpDesign['photo_opacity'] / 100 ?>"></div>
  <div class="hero-scrim" style="background:rgba(9,22,32,<?= $hpDesign['darken'] / 100 ?>)"></div>
  <?php endif; ?>
  <div class="hero-grain" style="opacity:<?= $hpDesign['texture'] / 100 ?>"></div>
  <div class="sticker-layer" id="rdStickerLayer">
    <?php foreach ($hpDesign['stickers'] as $s): ?>
    <div class="sticker sticker-<?= h($s['type']) ?>" data-type="<?= h($s['type']) ?>"
         style="left:<?= $s['x'] ?>%;top:<?= $s['y'] ?>%;--rot:<?= $s['rot'] ?>deg;--sc:<?= $s['sc'] ?>;--stk:<?= h($s['color']) ?>;--stkt:<?= $stickerInk($s['color']) ?>">
      <?php if (isset($stickerSvg[$s['type']])): ?><?= $stickerSvg[$s['type']] ?>
      <?php else: ?><div class="st-text"><?= h($s['text']) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="wrap">
    <div class="hero-grid">
      <div class="hero-copy">
        <p class="eyebrow"><?= h(__('pages.home.hero_eyebrow')) ?></p>
        <h1 class="headline"><?= h(__('pages.home.hero_headline_1')) ?><br><span class="em"><?= h(__('pages.home.hero_headline_2')) ?></span></h1>
        <p class="lede"><?= h(__('pages.home.hero_subtitle', ['count' => number_format($total)])) ?></p>
        <form class="search hero-search" role="search">
          <svg class="mag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
          <input id="heroSearch" type="text" placeholder="<?= h($isEs ? 'Busca playas, pueblos o actividades' : 'Search beaches, towns, or activities') ?>" aria-label="<?= h(__('common.search')) ?>">
          <button id="heroSearchGo" type="submit" aria-label="<?= h(__('common.search')) ?>">→</button>
        </form>
        <div class="hero-quick">
          <div class="suggestions" aria-label="<?= h($isEs ? 'Búsquedas sugeridas' : 'Suggested searches') ?>">
            <?php foreach ($heroSuggestions as $suggestion): ?>
            <button type="button"
                    data-query="<?= h($suggestion['query'] ?? '') ?>"
                    data-tag="<?= h($suggestion['tag'] ?? '') ?>"
                    data-sort="<?= h($suggestion['sort'] ?? '') ?>"><?= h($suggestion['label']) ?></button>
            <?php endforeach; ?>
          </div>
          <button class="near-me" type="button" id="heroNearMe" aria-label="<?= h($isEs ? 'Cerca de mí' : 'Use my location') ?>" title="<?= h($isEs ? 'Cerca de mí' : 'Use my location') ?>"><span class="near-icon">⌖</span><span class="near-text"><?= h($isEs ? 'Cerca de mí' : 'Use my location') ?></span></button>
        </div>
        <a class="hero-jump" href="#beaches"><?= h($rdI18n['browseDirectory']) ?></a>
        <div class="trustbar" aria-label="<?= h($isEs ? 'Resumen del directorio' : 'Directory summary') ?>">
          <span><b>★ <?= number_format((float)($siteStats['avg_rating'] ?? 4.5), 1) ?></b> <?= h($isEs ? 'promedio' : 'avg') ?></span>
          <span><b><?= h(number_format((int)(($siteStats['total_reviews'] ?? 0) / 1000)) . 'K+') ?></b> <?= h($isEs ? 'reseñas' : 'reviews') ?></span>
          <span><b><?= number_format($municipios) ?></b> <?= h($isEs ? 'pueblos' : 'towns') ?></span>
          <span><b><?= number_format($total) ?></b> <?= h($rdI18n['beaches']) ?></span>
        </div>
        <div class="vibe-block">
          <span class="vibe-label"><?= h($isEs ? 'Explora por ambiente' : 'Browse by vibe') ?></span>
          <div class="chips" role="group" aria-label="<?= h($isEs ? 'Filtrar por actividad' : 'Filter by activity') ?>">
            <?php foreach ($chipTags as $chipTag): ?>
            <button class="chip" type="button" data-tag="<?= h($chipTag) ?>" aria-pressed="false"><?= h($chipLabels[$chipTag] ?? __('tags.' . $chipTag)) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="region-panel">
        <div class="region-head">
          <span><?= h($isEs ? 'Explora por región' : 'Browse by region') ?></span>
          <a href="#beaches"><?= h($isEs ? 'Ver todas' : 'View all') ?></a>
        </div>
        <div class="map">
          <svg class="chart-svg" viewBox="0 0 560 360" role="img" aria-label="Interactive chart of Puerto Rico's coasts">
          <defs>
            <linearGradient id="rdSand" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F0C452"/><stop offset="1" stop-color="#D0982A"/></linearGradient>
            <linearGradient id="rdSea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E6F3FC"/><stop offset="1" stop-color="#C6E4F6"/></linearGradient>
          </defs>
          <rect class="rd-rect" x="6" y="8" width="548" height="344" rx="14"/>
          <line class="grat" x1="150" y1="30" x2="150" y2="345"/><line class="grat" x1="430" y1="30" x2="430" y2="345"/>
          <text class="coord" x="134" y="26">67°W</text><text class="coord" x="412" y="26">66°W</text>
          <path class="contour" style="stroke:#8FC6EE;stroke-width:1.1;opacity:.9" transform="translate(247.5,201.7) scale(1.02) translate(-247.5,-201.7)" d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
          <path class="contour" style="stroke:#4093CE;stroke-width:.85;opacity:.5" transform="translate(247.5,201.7) scale(1.075) translate(-247.5,-201.7)" d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
          <path class="island" d="<?= ISLAND_CHART_ISLAND_D ?>"/>
          <path class="cay" d="<?= ISLAND_CHART_CUL_D ?>"/><path class="cay" d="<?= ISLAND_CHART_VIE_D ?>"/>
          <g class="marker" id="rdMarker"><circle class="ring" id="rdRing" cx="0" cy="0" r="6"/><circle class="pin" id="rdPin" cx="0" cy="0" r="4.2"/></g>
          </svg>
          <?php foreach ($regionMeta as $key => $m): ?>
          <button class="region<?= $key === 'cays' ? ' cays' : '' ?>" data-region="<?= $key ?>" data-pt="<?= $m[3][0] ?>,<?= $m[3][1] ?>" style="left:<?= $m[1] ?>;top:<?= $m[2] ?>" aria-pressed="false">
            <span class="rname"><span class="rfull"><?= h($regionNames[$key] ?? $m[0]) ?></span><span class="rshort"><?= h($regionShortNames[$key] ?? ($regionNames[$key] ?? $m[0])) ?></span></span><span class="rcount"><?= $regionCounts[$key] ?> <?= h($rdI18n['beaches']) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</header>
<?php endif; ?>

<!-- ===== DIRECTORY ===== -->
<div class="wrap">
<section id="beaches" class="beachdir">
  <div class="dir-head">
    <div><span class="sub" id="rdDirSub"><?= h($rdI18n['wholeIsland']) ?></span><h2 id="rdDirTitle"><?= h($rdI18n['findYourBeach']) ?></h2></div>
    <span class="dir-count" id="rdDirCount"><?= number_format($total) ?> <?= h($rdI18n['beaches']) ?></span>
  </div>
  <div class="dir-toolbar">
    <div class="dir-search"><svg class="mag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg><input id="rdSearch" placeholder="<?= h($isEs ? 'Busca una playa o pueblo…' : 'Search a beach or town…') ?>" aria-label="<?= h($isEs ? 'Buscar playas' : 'Search beaches') ?>"></div>
    <select class="dir-select" id="rdSort" aria-label="<?= h($isEs ? 'Ordenar playas' : 'Sort beaches') ?>">
      <option value="score"><?= h($isEs ? 'Orden: Puntuación' : 'Sort: Beach Score') ?></option>
      <option value="rating"><?= h($isEs ? 'Mejor calificadas' : 'Top rated') ?></option>
      <option value="calm"><?= h($isEs ? 'Aguas más calmadas' : 'Calmest water') ?></option>
      <option value="crowd"><?= h($isEs ? 'Menos gentío' : 'Least crowded') ?></option>
    </select>
  </div>
  <div class="dir-body">
    <div>
      <div class="dir-grid" id="rdGrid"><?php
        // First page server-rendered so the beach links are crawlable without
        // JS; redesign-home.js re-renders the same markup on filter/sort.
        foreach (array_slice($rd, 0, 9) as $i => $b) { echo $rdTile($b, $i + 1); }
      ?></div>
      <div class="dir-more">
        <button id="rdMore"><?= h($rdI18n['showMore']) ?></button>
        <p id="rdMoreNote"><?= h(sprintf($rdI18n['showRemaining'], number_format(max(0, $total - 9)), number_format($total))) ?></p>
      </div>
    </div>
    <aside class="dir-side">
      <div class="card-w decision-card today-card">
        <div class="decision-head">
          <h4><?= h($isEs ? 'Mejores opciones hoy' : 'Best picks today') ?></h4>
          <?php if ($weatherTemp !== null): ?>
          <span class="weather-pill"><?= $weatherTemp ?>°<?= $weatherDesc !== '' ? ' · ' . h($weatherDesc) : '' ?></span>
          <?php endif; ?>
        </div>
        <p class="today-read"><?= h($todayRead) ?></p>
        <div class="today-picks">
          <?php foreach ($todayPicks as $pick): ?>
          <a class="today-pick" href="<?= h($beachUrlPrefix . $pick['slug']) ?>">
            <span class="pick-score"><?= (int) $pick['pick_score'] ?></span>
            <span class="pick-copy">
              <b><?= h($pick['pick_label']) ?></b>
              <strong><?= h($pick['n']) ?></strong>
              <small><?= h($pick['m']) ?> · <?= h($pick['pick_reason']) ?></small>
            </span>
          </a>
          <?php endforeach; ?>
        </div>
        <div class="decision-actions">
          <a href="<?= h($calmUrl) ?>"><?= h($isEs ? 'Ver aguas calmadas' : 'Find calm water') ?></a>
          <a href="<?= h($mapUrl) ?>"><?= h($isEs ? 'Abrir mapa' : 'Open map') ?></a>
        </div>
      </div>
      <div class="card-w decision-card"><h4><?= h($isEs ? 'Populares ahora' : 'Popular now') ?></h4>
        <?php foreach ($popular as $p):
            $matched = $rdBySlug[$p['slug']] ?? [];
            $pImg = $matched['img'] ?? (function_exists('getBeachImageUrl')
                ? getBeachImageUrl($p, 'thumb')
                : (($p['cover_image'] ?? '') ?: '/images/beaches/placeholder-beach.webp'));
            $pScore = (int) ($matched['sc'] ?? 0);
            $pViews = isset($p['yesterday_views']) ? (int) $p['yesterday_views'] : 0;
            if ($pViews > 0) {
                $pReason = $isEs
                    ? number_format($pViews) . ' visitas ayer'
                    : number_format($pViews) . ' visits yesterday';
            } else {
                $pReason = $matched ? $popularReason($matched) : ($isEs ? 'Muy reseñada' : 'Highly reviewed');
            }
        ?>
        <a class="nearby nearby-rich" href="<?= h(($isEs ? '/es/playa/' : '/beach/') . $p['slug']) ?>">
          <div class="ph" style="background-image:url('<?= h($pImg) ?>')"></div>
          <div class="nearby-copy">
            <div class="nm"><?= h($p['name']) ?></div>
            <div class="mi"><?= h($p['municipality']) ?><?= $pScore ? ' · ' . h($rdI18n['beachScore']) . ' ' . $pScore : '' ?></div>
            <small><?= h($pReason) ?></small>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="card-w decision-card rank-card"><h4><?= h($isEs ? 'Cómo elegimos' : 'How we rank') ?></h4>
        <div class="rank-row"><span><?= h($isEs ? 'Compara' : 'Compares') ?></span><b><?= number_format($publishedCount ?? $total) ?></b><small><?= h($isEs ? 'playas publicadas' : 'published beaches') ?></small></div>
        <div class="rank-row"><span><?= h($isEs ? 'Analiza' : 'Reads') ?></span><b><?= h(number_format((int)($siteStats['total_reviews'] ?? 0))) ?></b><small><?= h($isEs ? 'reseñas y calificaciones' : 'reviews and ratings') ?></small></div>
        <div class="rank-row"><span><?= h($isEs ? 'Puntúa' : 'Scores') ?></span><b>5</b><small><?= h($isEs ? 'señales: agua, snorkel, calma, familia y servicios' : 'signals: water, snorkel, quiet, family, and facilities') ?></small></div>
      </div>
    </aside>
  </div>
</section>

<!-- Browse-by-activity links to the 12 tag landing pages (server-rendered) -->
<section class="actlinks" aria-label="<?= h($isEs ? 'Playas por actividad' : 'Beaches by activity') ?>">
  <span class="sub"><?= h($isEs ? 'Por actividad' : 'By activity') ?></span>
  <div class="actrow">
    <?php foreach (['swimming', 'snorkeling', 'surfing', 'family-friendly', 'calm-waters', 'secluded', 'scenic', 'diving', 'accessible', 'fishing', 'camping', 'popular'] as $actTag): ?>
    <a href="<?= h(getLocalizedTagPageUrl($actTag, $lang)) ?>"><?= h(__('tags.' . $actTag)) ?><?php if (!empty($tagCounts[$actTag])): ?> <b><?= (int) $tagCounts[$actTag] ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section id="experiences" class="home-quiz">
  <div class="home-quiz-inner">
    <h2><?= h(__('pages.home.quiz_headline')) ?></h2>
    <p><?= h(__('pages.home.quiz_subtitle')) ?></p>
    <a href="<?= h(routeUrl('quiz', $lang)) ?>"><?= h(__('pages.home.quiz_button')) ?></a>
  </div>
</section>

<section class="home-resources" aria-labelledby="rdResourcesHeading">
  <div class="resource-head">
    <div>
      <h2 id="rdResourcesHeading"><?= h(__('pages.home.resources_heading')) ?></h2>
      <p><?= h(__('pages.home.resources_subtitle')) ?></p>
    </div>
    <a class="all-guides" href="<?= h(routeUrl('guides_index', $lang)) ?>"><?= h(__('pages.home.resources_all_guides')) ?> →</a>
  </div>
  <div class="resource-grid">
    <?php foreach ($guideCards as $guide): ?>
    <a class="resource-card" href="/guides/<?= h($guide['slug']) ?>">
      <span class="resource-type"><?= h($guide['emoji'] . ' ' . $guide['type']) ?></span>
      <h3><?= h($guide['title']) ?></h3>
      <p><?= h($guide['desc']) ?></p>
      <span class="resource-read"><?= h(__('pages.home.resources_read_guide')) ?> →</span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
</div>
</div>

<script <?= cspNonceAttr() ?>>
window.RD_BEACHES = <?= json_encode($rd, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
window.RD_CFG = <?= json_encode([
    'urlPrefix' => $beachUrlPrefix,
    'authed' => isAuthenticated(),
    'csrf' => function_exists('csrfToken') ? csrfToken() : '',
    'favs' => $favIds,
    'i18n' => $rdI18n,
    'regionNames' => $regionNames,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script <?= cspNonceAttr() ?> src="/assets/js/redesign-home.js?v=17"></script>
<?php if ($hpEditor): ?>
<!-- Admin homepage-design editor preview (loaded only inside /admin/homepage-design iframe) -->
<script <?= cspNonceAttr() ?> src="/assets/js/redesign-editor-preview.js?v=1"></script>
<?php endif; ?>
