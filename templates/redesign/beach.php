<?php
/**
 * Redesign v2 — beach profile template.
 * Rendered inside <main> (header.php already output <head> + body + rd nav).
 * Expects from beach.php scope: $beach (with tags/amenities/tips/gallery/
 * features), $faqs, $lang, $extendedSections, $reviews, $userReviewCount,
 * $avgUserRating, $beachReferralHero, $beachReferralBottom.
 *
 * Content parity with the classic beach page is a launch requirement — every
 * classic block (extended CMS sections, gallery, visitor photos, reviews,
 * amenities, related guides/similar beaches/tag links, referral slots) must
 * render here too. Interactive dialogs are the shared components/beach/
 * modals.php, wired via the same data-action hooks as classic.
 */
require_once APP_ROOT . '/inc/beach_score.php';
require_once APP_ROOT . '/inc/island_chart.php';
require_once APP_ROOT . '/inc/weather.php';
require_once APP_ROOT . '/inc/tours.php';
require_once APP_ROOT . '/inc/listings.php';

$isEs = ($lang ?? 'en') === 'es';
$tags = $beach['tags'] ?? [];
$amenities = $beach['amenities'] ?? [];
$score = computeBeachScore($beach, $tags, $amenities);
$lat = (float) ($beach['lat'] ?? 0);
$lng = (float) ($beach['lng'] ?? 0);
$region = islandRegionForMunicipality($beach['municipality'] ?? '');
$regionLabel = [
    'metro' => 'Metro · San Juan', 'north' => $isEs ? 'Costa Norte' : 'North Coast', 'west' => 'Porta del Sol',
    'south' => $isEs ? 'Costa Sur' : 'South Coast', 'east' => $isEs ? 'Este · Fajardo' : 'East · Fajardo', 'cays' => $isEs ? 'Los Cayos' : 'The Cays',
][$region] ?? '';
$access = strtolower((string) ($beach['access_label'] ?? ''));
$isBoat = str_contains($access, 'boat') || str_contains($access, 'kayak');
$surf = strtolower((string) ($beach['surf'] ?? ''));
$rating = $score['rating'];
$reviewCountGoogle = (int) ($beach['google_review_count'] ?? 0);
$dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;

// hero image (real <img> with srcset for LCP + image search, like classic)
$heroSrcset = getBeachImageSrcset($beach);
if (!$heroSrcset) {
    $heroAttrs = getResponsiveImageAttrs(getBeachImageUrl($beach, 'large'), '100vw');
    $heroSrcset = $heroAttrs['srcset'] ?? '';
}

// hero chips from real tags (top 3) + boat-only badge
$chipTags = array_slice($tags, 0, 3);

// weather (cached; degrade gracefully)
$weather = null;
try { if ($lat && $lng) { $weather = getWeatherForLocation($lat, $lng); } } catch (\Throwable $e) { $weather = null; }
$cur = $weather['current'] ?? [];
$fmtTime = function ($iso) {
    if (!$iso) return '—';
    $ts = strtotime($iso);
    return $ts ? date('g:i a', $ts) : '—';
};
$uvLabel = function ($uv) use ($isEs) {
    $uv = (float) $uv;
    if ($uv < 3) return [$isEs ? 'Bajo' : 'Low', 'g'];
    if ($uv < 6) return [$isEs ? 'Moderado' : 'Moderate', 'a'];
    if ($uv < 8) return [$isEs ? 'Alto' : 'High', 'r'];
    return [$isEs ? 'Muy alto' : 'Very high', 'r'];
};

// at-a-glance card derived from fields (localized; data-conditional).
// One card, four zones; every fact renders exactly once: status facts →
// best-for/amenity chips → getting-there/best-time prose rows.
$swimTile = in_array($surf, ['calm', 'small'], true)
    ? [$isEs ? 'Fácil — agua calmada' : 'Easy — calm water', 'g']
    : ($surf === 'large'
        ? [$isEs ? 'Avanzado — oleaje fuerte' : 'Advanced — strong surf', 'r']
        : [$isEs ? 'Moderado — verifica condiciones' : 'Moderate — check conditions', 'a']);
$snorkelGood = (bool) array_filter($tags, fn($t) => str_contains(strtolower($t), 'snorkel') || str_contains(strtolower($t), 'reef'));
$glanceFacts = [
    ['🏊', $isEs ? 'Nadar' : 'Swimming', $swimTile[0], $swimTile[1]],
    ['🤿', 'Snorkel', $snorkelGood ? ($isEs ? 'Bueno — arrecife cerca' : 'Great — reef access') : ($isEs ? 'Limitado' : 'Limited'), $snorkelGood ? 'g' : 'a'],
    ['👨‍👩‍👧‍👦', $isEs ? 'Familia' : 'Family', !empty($beach['safe_for_children']) ? ($isEs ? 'Sí — segura para niños' : 'Yes — safe for kids') : ($isEs ? 'Verifica condiciones' : 'Check conditions'), !empty($beach['safe_for_children']) ? 'g' : 'a'],
];
if ($access !== '') {
    $glanceFacts[] = ['🧭', $isEs ? 'Acceso' : 'Access', ucfirst((string) $beach['access_label']), $isBoat ? 'r' : (str_contains($access, 'hike') || str_contains($access, 'walk') ? 'a' : 'g')];
}

$heroAccess = trim((string) ($beach['access_label'] ?? ''));
$heroParking = trim((string) ($beach['parking_details'] ?? ''));

// AI at-a-glance summary (same generator as classic); falls back to nothing
$aiSummary = trim((string) generateAtAGlanceSummary($beach, $lang));

// about text — real content only, no fabricated fallback
$about = $isEs && !empty($beach['description_es']) ? $beach['description_es'] : ($beach['description'] ?? '');
$aboutParas = array_values(array_filter(array_map('trim', preg_split('/\n{2,}|\r\n\r\n/', $about) ?: [$about]), fn($p) => $p !== ''));

// tips — real tips only; section is skipped entirely when there are none
$tipList = [];
foreach (($beach['tips'] ?? []) as $t) {
    $txt = $isEs && !empty($t['tip_es']) ? $t['tip_es'] : ($t['tip'] ?? '');
    if ($txt) { $tipList[] = $txt; }
}
if (!$tipList && !empty($beach['local_tips'])) {
    $tipList = array_values(array_filter(array_map('trim', preg_split('/\n|•|\r/', (string) $beach['local_tips']))));
}
$tipList = array_slice($tipList, 0, 6);

// extended CMS sections — same grouping/order as classic extended-sections.php
$xPlanTypes = ['best_time', 'what_to_bring'];
$xAboutTypes = ['history', 'nearby', 'local_tips'];
$xPlan = []; $xAbout = []; $xOther = [];
foreach (($extendedSections ?? []) as $s) {
    if ($s['section_type'] === 'getting_there') continue;
    if (in_array($s['section_type'], $xPlanTypes, true)) $xPlan[] = $s;
    elseif (in_array($s['section_type'], $xAboutTypes, true)) $xAbout[] = $s;
    else $xOther[] = $s;
}
$xGroups = array_filter([
    [$isEs ? 'Planifica Tu Visita' : 'Plan Your Visit', $xPlan],
    [$isEs ? 'Sobre Esta Playa' : 'About This Beach', array_merge($xAbout, $xOther)],
], fn($g) => !empty($g[1]));

// related content
$relatedGuides = getRelatedGuides($tags, 3);
$similarBeaches = getSimilarBeaches($beach['id'], $tags, 4);
$nearby = array_slice(getNearbyBeaches((string) $beach['id'], $lat, $lng, 4) ?: [], 0, 4);
$cardImageUrl = function (array $row, string $size = 'medium'): string {
    return getBeachImageUrl($row, $size);
};

// visitor photos (published) — same source as classic photos.php
$userPhotos = query("SELECT p.id, p.filename, p.caption, p.created_at, u.name as user_name FROM beach_photos p LEFT JOIN users u ON p.user_id = u.id WHERE p.beach_id = :beach_id AND p.status = 'published' ORDER BY p.created_at DESC LIMIT 12", [':beach_id' => $beach['id']]);
$totalUserPhotos = (int) (queryOne("SELECT COUNT(*) as count FROM beach_photos WHERE beach_id = :beach_id AND status = 'published'", [':beach_id' => $beach['id']])['count'] ?? 0);
$hasGallery = !empty($beach['gallery']);
$hasPhotosContent = $hasGallery || !empty($userPhotos);
$hasReviewsContent = !empty($reviews);
$beachDetailUrl = routeUrl('beach_detail', $lang, ['slug' => $beach['slug']]);
$photoLoginUrl = routeUrl('login', $lang) . '?redirect=' . urlencode($beachDetailUrl . '#photos');
$reviewLoginUrl = routeUrl('login', $lang) . '?redirect=' . urlencode($beachDetailUrl . '#reviews');
$photoActionArgs = '["' . h($beach['id']) . '","' . h(addslashes($beach['name'])) . '"]';
$reviewActionArgs = $photoActionArgs;
// best-for chips deduped against the status facts above — calm/snorkel/
// family/swimming signals already render as tone-colored tiles
$factCoveredTags = ['calm-waters', 'snorkeling', 'family-friendly', 'swimming'];
$bestForChips = array_slice(array_map(fn($t) => getTagLabel($t), array_values(array_diff($tags, $factCoveredTags))), 0, 4);
// getting-there row merges the access label with the parking prose; the lead
// is dropped when the prose already states the access mode (e.g. "only
// accessible by boat")
$accessLead = $heroAccess !== '' ? ucfirst($heroAccess) : '';
if ($accessLead !== '' && $heroParking !== '') {
    $accessWord = strtolower(strtok($heroAccess, ' ') ?: '');
    if ($accessWord !== '' && str_contains(strtolower($heroParking), $accessWord)) {
        $accessLead = '';
    }
}
$gettingBody = trim(($accessLead !== '' ? $accessLead . '. ' : '') . $heroParking);
if ($gettingBody === '') {
    $gettingBody = $isEs ? 'Verifica acceso y parking antes de ir.' : 'Check access and parking before you go.';
}
$facilityScore = null;
foreach (($score['bars'] ?? []) as $scoreBar) {
    if (($scoreBar[0] ?? '') === 'Facilities') {
        $facilityScore = (int) ($scoreBar[1] ?? 0);
        break;
    }
}
$similarReason = function (array $row) use ($tags, $isEs): string {
    $shared = array_values(array_intersect($tags, $row['tags'] ?? []));
    if (!empty($shared)) {
        $labels = array_map(fn($t) => getTagLabel($t), array_slice($shared, 0, 2));
        return ($isEs ? 'Similar: ' : 'Similar: ') . implode(' · ', $labels);
    }
    return $isEs ? 'Ambiente parecido' : 'Similar beach feel';
};
$renderConditionsCard = function (string $extraClass = '') use ($cur, $uvLabel, $weather, $fmtTime, $isEs): void {
    ?>
    <div class="card conditions-card <?= h($extraClass) ?>">
      <h4><?= h($isEs ? 'Condiciones hoy' : 'Conditions today') ?></h4>
      <?php if ($cur): $uv = $uvLabel($cur['uv_index'] ?? 0); ?>
      <div class="cond-now"><span class="t"><?= round((float) ($cur['temperature'] ?? 0)) ?>°</span><span class="d"><?= h($cur['description'] ?? '') ?></span></div>
      <div class="cond-grid">
        <div><div class="k"><?= h($isEs ? 'Viento' : 'Wind') ?></div><div class="v"><?= round((float) ($cur['wind_speed'] ?? 0)) ?> <span style="font-size:.7rem;color:var(--ink-60)">km/h</span></div></div>
        <div><div class="k"><?= h($isEs ? 'Índice UV' : 'UV index') ?></div><div class="v" style="color:<?= $uv[1] === 'r' ? 'var(--coral)' : ($uv[1] === 'a' ? '#B7860B' : 'var(--green)') ?>"><?= h($uv[0]) ?></div></div>
        <div><div class="k"><?= h($isEs ? 'Humedad' : 'Humidity') ?></div><div class="v"><?= round((float) ($cur['humidity'] ?? 0)) ?>%</div></div>
        <div><div class="k"><?= h($isEs ? 'Se siente' : 'Feels like') ?></div><div class="v"><?= round((float) ($cur['feels_like'] ?? ($cur['temperature'] ?? 0))) ?>°</div></div>
      </div>
      <div class="suntimes"><span>☀ <?= h($isEs ? 'Amanecer' : 'Sunrise') ?> <b><?= $fmtTime($weather['sunrise'] ?? null) ?></b></span><span>🌙 <?= h($isEs ? 'Atardecer' : 'Sunset') ?> <b><?= $fmtTime($weather['sunset'] ?? null) ?></b></span></div>
      <?php else: ?>
      <p style="font-size:.85rem;color:var(--ink-60)"><?= h($isEs ? 'Clima no disponible ahora.' : 'Live conditions unavailable right now.') ?></p>
      <?php endif; ?>
    </div>
    <?php
};
$renderLocationCard = function (string $extraClass = '') use ($isEs, $lat, $lng, $beach, $regionLabel): void {
    ?>
    <div class="card loc-card <?= h($extraClass) ?>">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:13px">
        <h4 style="margin:0"><?= h($isEs ? 'Dónde queda' : 'Where it is') ?></h4>
        <div class="locseg"><button class="on" data-loc="chart" type="button"><?= h($isEs ? 'Mapa' : 'Chart') ?></button><button data-loc="sat" type="button"><?= h($isEs ? 'Satélite' : 'Satellite') ?></button></div>
      </div>
      <div class="loc-chart"><?= renderIslandLocator($lat, $lng) ?></div>
      <div class="loc-sat" style="display:none"></div>
      <div class="loc-note"><span><?= h($beach['municipality']) ?><?= $regionLabel ? ' · ' . h($regionLabel) : '' ?></span><b><?= number_format($lat, 2) ?>°N <?= number_format(abs($lng), 2) ?>°W</b></div>
    </div>
    <?php
};
$renderScoreCard = function (string $extraClass = '') use ($isEs, $score, $facilityScore): void {
    ?>
    <div class="scorecard <?= h($extraClass) ?>">
      <h4 style="margin:0 0 13px"><?= h($isEs ? 'Puntuación' : 'Beach Score') ?></h4>
      <div class="top"><div class="big"><?= $score['overall'] ?><small>/100</small></div><div style="font-family:var(--data);text-transform:uppercase;letter-spacing:.1em;font-size:.72rem;color:var(--ink-60);text-align:right"><?= $isEs ? 'Cómo puntúa esta playa<br>para pasar el día' : 'How this beach<br>rates for a day out' ?></div></div>
      <div class="scores">
        <?php
          $bars = array_merge([[$isEs ? 'General' : 'Overall', $score['overall'], bsColor($score['overall'])]], $score['bars']);
          $icons = ['Overall' => '⭐', 'General' => '⭐', 'Calm water' => '🌊', 'Snorkeling' => '🤿', 'Seclusion' => '🌾', 'Family' => '👨‍👩‍👧', 'Facilities' => '🚻'];
          $barNames = $isEs ? ['Calm water' => 'Agua calmada', 'Snorkeling' => 'Snorkel', 'Seclusion' => 'Tranquilidad', 'Family' => 'Familia', 'Facilities' => 'Servicios'] : [];
          foreach ($bars as $b): ?>
        <div class="score"><span><?= ($icons[$b[0]] ?? '•') . ' ' . h($barNames[$b[0]] ?? $b[0]) ?></span><div class="bar"><i class="<?= $b[2] ?>" style="width:<?= $b[1] ?>%"></i></div><span class="pct"><?= $b[1] ?></span></div>
        <?php endforeach; ?>
      </div>
      <?php if ($facilityScore !== null && $facilityScore < 50): ?>
      <div class="score-note">
        <b><?= h($isEs ? 'Servicios limitados' : 'Limited facilities') ?></b>
        <span><?= h($isEs ? 'Lleva agua, sombra, comida y todo lo necesario para el día.' : 'Bring water, shade, food, and everything you need for the day.') ?></span>
      </div>
      <?php endif; ?>
    </div>
    <?php
};

// favorite state for the hero Save button (session is active here)
$isFavorite = false;
if (isAuthenticated() && !empty($_SESSION['user_id'])) {
    $isFavorite = (bool) queryOne(
        'SELECT 1 AS f FROM user_favorites WHERE user_id = :u AND beach_id = :b',
        [':u' => $_SESSION['user_id'], ':b' => $beach['id']]
    );
}

$subnav = array_values(array_filter([
    ['overview', $isEs ? 'Vistazo' : 'Overview', true],
    ['tours', 'Tours', true],
    ['about', $isEs ? 'Sobre' : 'About', !empty($aboutParas) || !empty($beach['features'])],
    ['tips', $isEs ? 'Consejos' : 'Tips', !empty($tipList)],
    ['getting', $isEs ? 'Cómo llegar' : 'Getting there', true],
    ['photos', $isEs ? 'Fotos' : 'Photos', true],
    ['reviews', $isEs ? 'Reseñas' : 'Reviews', true],
    ['nearby', $isEs ? 'Cercanas' : 'Nearby', !empty($nearby)],
    ['faq', $isEs ? 'Preguntas' : 'FAQ', !empty($faqs)],
], fn($i) => $i[2]));
?>
<div class="rd rd-beach">

<header class="hero">
  <?php if (!empty($beach['cover_image'])): ?>
  <img class="hero-photo" src="<?= h(getBeachImageUrl($beach, 'large')) ?>"
       data-fallback-src="/images/beaches/placeholder-beach.webp"
       <?php if ($heroSrcset): ?>srcset="<?= h($heroSrcset) ?>" sizes="100vw"<?php endif; ?>
       alt="<?= h(getBeachImageAlt($beach, 'scenic beach view')) ?>"
       fetchpriority="high">
  <?php else: ?>
  <div class="hero-photo hero-photo-fallback"></div>
  <?php endif; ?>
  <div class="hero-scrim"></div>
  <?php if ($isBoat): ?><div class="h-sticker">solo en bote<small>boat access only</small></div><?php endif; ?>

  <div class="wrap" style="width:100%">
    <div class="crumb">
      <a href="<?= h(routeUrl('home', $lang)) ?>"><?= h($isEs ? 'Inicio' : 'Home') ?></a> /
      <a href="<?= h(routeUrl('home', $lang)) ?>#beaches"><?= h($isEs ? 'Playas' : 'Beaches') ?></a> /
      <a href="<?= h(routeUrl('municipality', $lang, ['municipality' => strtolower(str_replace(' ', '-', stripAccents($beach['municipality'])))])) ?>"><?= h($beach['municipality']) ?></a> /
      <?= h($beach['name']) ?>
    </div>
  </div>

  <div class="wrap hero-body" style="width:100%">
    <div class="h-tags">
      <?php foreach ($chipTags as $t): ?><span class="h-tag"><?= h(getTagLabel($t)) ?></span><?php endforeach; ?>
      <?php if ($isBoat): ?><span class="h-tag boat">🛥️ <?= h($isEs ? 'Solo en bote' : 'Boat-only') ?></span><?php endif; ?>
    </div>
    <h1 class="h-title"><?= h($beach['name']) ?></h1>
    <div class="h-sub"><?= h($beach['municipality']) ?><?= $regionLabel ? ' · ' . h($regionLabel) : '' ?> <span class="coord">· <?= number_format($lat, 2) ?>°N <?= number_format(abs($lng), 2) ?>°W</span></div>
    <?php if ($rating > 0): ?>
    <div class="h-rating">
      <span class="stars"><?= str_repeat('★', (int) round($rating)) . str_repeat('☆', 5 - (int) round($rating)) ?></span>
      <span><?= $reviewCountGoogle ? 'Google ' : '' ?><?= number_format($rating, 1) ?><?= $reviewCountGoogle ? ' · ' . number_format($reviewCountGoogle) . ' ' . ($isEs ? 'reseñas' : 'reviews') : '' ?></span>
    </div>
    <?php endif; ?>
    <div class="h-command">
      <div class="h-snap score" aria-label="<?= h(($isEs ? 'Puntuación de playa: ' : 'Beach score: ') . $score['overall'] . ' / 100') ?>">
        <span class="v"><?= $score['overall'] ?></span>
        <span class="k"><?= h($isEs ? 'Puntuación' : 'Score') ?></span>
      </div>
      <div class="h-actions">
        <a class="btn coral" href="<?= h($dirUrl) ?>" target="_blank" rel="noopener" data-bf-track="directions"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 21l9-4 9 4z"/></svg><?= h($isEs ? 'Cómo llegar' : 'Directions') ?></a>
        <button class="btn" type="button" id="sticky-favorite-btn"
                data-action="toggleStickyFavorite"
                aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
          <span id="sticky-favorite-icon" aria-hidden="true"><?= $isFavorite ? '❤️' : '🤍' ?></span> <?= h($isEs ? 'Guardar' : 'Save') ?>
        </button>
        <button class="btn" type="button" data-action="shareBeach" data-action-args='["<?= h($beach['slug']) ?>","<?= h(addslashes($beach['name'])) ?>"]'>↗ <?= h($isEs ? 'Compartir' : 'Share') ?></button>
      </div>
    </div>
  </div>
</header>

<nav class="subnav"><div class="wrap">
  <?php foreach ($subnav as $i => $s): ?>
  <a href="#<?= h($s[0]) ?>"<?= $i === 0 ? ' class="on"' : '' ?>><?= h($s[1]) ?></a>
  <?php endforeach; ?>
</div></nav>

<?php if (($beachReferralHero ?? '') !== ''): ?>
<div class="wrap" style="margin-top:18px"><?= $beachReferralHero ?></div>
<?php endif; ?>

<div class="wrap"><div class="body">
  <main>
    <section id="overview" class="block">
      <span class="eyebrow"><?= h($isEs ? 'Vistazo' : 'At a glance') ?></span>
      <?php if ($aiSummary !== ''): ?><p class="lead" style="margin:8px 0 18px"><?= h($aiSummary) ?></p>
      <?php elseif (!empty($aboutParas)): ?><p class="lead" style="margin:8px 0 18px"><?= h(mb_strlen($aboutParas[0]) > 220 ? mb_substr($aboutParas[0], 0, 220) . '…' : $aboutParas[0]) ?></p><?php endif; ?>
      <div class="glance-card" aria-label="<?= h($isEs ? 'Resumen rápido' : 'Quick snapshot') ?>">
        <div class="glance-facts">
          <?php foreach ($glanceFacts as $g): ?>
          <div class="gfact"><span class="ic"><?= $g[0] ?></span><div><div class="k"><?= h($g[1]) ?></div><div class="v <?= $g[3] ?>"><?= h($g[2]) ?></div></div></div>
          <?php endforeach; ?>
        </div>
        <div class="glance-chips">
          <?php if (!empty($bestForChips)): ?>
          <div class="chiprow">
            <h3><?= h($isEs ? 'Ideal para' : 'Best for') ?></h3>
            <ul><?php foreach ($bestForChips as $chip): ?><li><?= h($chip) ?></li><?php endforeach; ?></ul>
          </div>
          <?php endif; ?>
          <div class="chiprow">
            <h3><?= h($isEs ? 'Servicios' : 'Amenities') ?></h3>
            <?php if (!empty($amenities)): ?>
            <ul><?php foreach ($amenities as $amenity): ?><li>✓ <?= h(getAmenityLabel($amenity)) ?></li><?php endforeach; ?></ul>
            <?php else: ?>
            <p class="none"><?= h($isEs ? 'Pocos servicios listados — lleva lo necesario' : 'Limited listed — bring what you need') ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="glance-plan">
          <div class="gplan"><span class="ic"><?= $isBoat ? '🛥️' : '🧭' ?></span><div><h3><?= h($isEs ? 'Cómo llegar' : 'Getting there') ?></h3><p><?= h($gettingBody) ?></p></div></div>
          <?php if (!empty($beach['best_time'])): ?>
          <div class="gplan"><span class="ic">📅</span><div><h3><?= h($isEs ? 'Mejor época' : 'Best time') ?></h3><p><?= h($beach['best_time']) ?></p></div></div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="mobile-planning block" aria-label="<?= h($isEs ? 'Planificación rápida' : 'Quick planning') ?>">
      <?php $renderScoreCard('mobile-card'); ?>
      <?php $renderConditionsCard('mobile-card'); ?>
      <?php $renderLocationCard('mobile-card'); ?>
    </section>

    <?= renderToursSection($beach, $lang, 'redesign') ?>

    <?php if (!empty($aboutParas) || !empty($beach['features'])): ?>
    <section id="about" class="block prose">
      <h2 class="h2"><?= h($isEs ? 'Sobre ' : 'About ') . h($beach['name']) ?></h2>
      <?php foreach ($aboutParas as $p): ?><p><?= h($p) ?></p><?php endforeach; ?>
      <?php if (!empty($beach['features'])): ?>
      <div class="feats">
        <?php foreach ($beach['features'] as $feature): ?>
        <span class="feat">✦ <?= h(($isEs && !empty($feature['title_es'])) ? $feature['title_es'] : $feature['title']) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($tipList)): ?>
    <section id="tips" class="block">
      <h2 class="h2"><?= h($isEs ? 'Consejos' : 'Local tips') ?></h2>
      <div class="tips">
        <?php foreach ($tipList as $t): ?><div class="tip"><span class="ck">✔</span><span><?= h($t) ?></span></div><?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($xGroups)): ?>
    <section class="block xsecs">
      <?php foreach ($xGroups as [$groupLabel, $groupSections]): ?>
      <div class="grouplab"><span><?= h($groupLabel) ?></span><i></i></div>
      <?php foreach ($groupSections as $section):
          $xHeading = ($isEs && !empty($section['heading_es'])) ? $section['heading_es'] : $section['heading'];
          $xContent = ($isEs && !empty($section['content_es'])) ? $section['content_es'] : $section['content'];
          $xIdMap = ['local_tips' => 'extended-tips'];
          $xId = 'section-' . str_replace('_', '-', $xIdMap[$section['section_type']] ?? $section['section_type']);
      ?>
      <details id="<?= h($xId) ?>" class="xsec">
        <summary><span><?= h($xHeading) ?></span><b>+</b></summary>
        <div class="xbody prose"><?= sanitizeContentHtml($xContent) ?></div>
      </details>
      <?php endforeach; ?>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section id="getting" class="block">
      <h2 class="h2"><?= h($isEs ? 'Cómo llegar y seguridad' : 'Getting there & safety') ?></h2>
      <div style="display:grid;gap:12px">
        <?php if ($access !== '' || !empty($beach['parking_details'])): ?>
        <div class="callout"><span class="ic"><?= $isBoat ? '🛥️' : '🧭' ?></span><div><h4><?= h($isEs ? 'Acceso' : 'Access') ?></h4><p><?= h(ucfirst((string) ($beach['access_label'] ?? ''))) ?><?= !empty($beach['parking_details']) ? '. ' . h($beach['parking_details']) : '' ?></p></div></div>
        <?php endif; ?>
        <?php if (!empty($beach['safety_info'])): ?>
        <div class="callout warn"><span class="ic">⚠️</span><div><h4><?= h($isEs ? 'Seguridad' : 'Swim smart') ?></h4><p><?= h($beach['safety_info']) ?></p></div></div>
        <?php endif; ?>
      </div>
    </section>

    <section class="contrib-band block" aria-labelledby="contrib-heading">
      <div>
        <span class="eyebrow"><?= h($isEs ? 'Comparte lo que viste' : 'Share what you saw') ?></span>
        <h2 id="contrib-heading"><?= h($isEs ? 'Ayuda a la próxima persona que visite esta playa' : 'Help the next person plan this beach') ?></h2>
        <p><?= h($isEs ? 'Sube fotos recientes, deja una reseña o reporta las condiciones de hoy.' : "Add recent photos, leave a review, or report today's conditions.") ?></p>
      </div>
      <div class="contrib-actions">
        <?php if (isAuthenticated()): ?>
        <button class="btn coral" type="button" data-action="openPhotoUploadModal" data-action-args='<?= $photoActionArgs ?>'>+ <?= h($isEs ? 'Añadir foto' : 'Add photo') ?></button>
        <button class="btn" type="button" data-action="openReviewForm" data-action-args='<?= $reviewActionArgs ?>'><?= h($isEs ? 'Escribir reseña' : 'Write review') ?></button>
        <?php else: ?>
        <a class="btn coral" href="<?= h($photoLoginUrl) ?>">+ <?= h($isEs ? 'Añadir foto' : 'Add photo') ?></a>
        <a class="btn" href="<?= h($reviewLoginUrl) ?>"><?= h($isEs ? 'Escribir reseña' : 'Write review') ?></a>
        <?php endif; ?>
        <button class="btn sea" type="button" data-action="openCheckinModal" data-action-args='<?= h(json_encode([$beach['id'], $beach['name']])) ?>'>✔ <?= h($isEs ? 'Check in' : 'Check in') ?></button>
      </div>
    </section>

    <section id="photos" class="block">
      <div class="secrow">
        <h2 class="h2"><?= h($isEs ? 'Fotos' : 'Photos') ?><?php if ($totalUserPhotos > 0): ?> <small>(<?= $totalUserPhotos ?>)</small><?php endif; ?></h2>
        <?php if ($hasPhotosContent): ?>
        <?php if (isAuthenticated()): ?>
        <button class="btn" type="button" data-action="openPhotoUploadModal" data-action-args='<?= $photoActionArgs ?>'>+ <?= h($isEs ? 'Añadir foto' : 'Add photo') ?></button>
        <?php else: ?>
        <a class="minor" href="<?= h($photoLoginUrl) ?>"><?= h($isEs ? 'Inicia sesión para añadir fotos' : 'Sign in to add photos') ?></a>
        <?php endif; ?>
        <?php endif; ?>
      </div>
      <?php if ($hasGallery): ?>
      <div class="gal">
        <?php foreach ($beach['gallery'] as $idx => $image): ?>
        <img src="<?= h($image) ?>" alt="<?= h($beach['name']) ?> — <?= h($isEs ? 'foto' : 'photo') ?> <?= $idx + 1 ?>" loading="lazy" data-gallery-index="<?= $idx ?>" data-action="openLightbox" data-action-args='[<?= $idx ?>]'>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($userPhotos)): ?>
      <div class="vphotos">
        <?php foreach ($userPhotos as $photo): ?>
        <button type="button" data-action="openPhotoModal" data-action-args='["/uploads/photos/<?= h($photo['filename']) ?>","<?= h(addslashes($photo['caption'] ?? '')) ?>"]'>
          <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>" alt="<?= h($photo['caption'] ?: ($isEs ? 'Foto de visitante' : 'Visitor photo')) ?>" loading="lazy">
        </button>
        <?php endforeach; ?>
      </div>
      <?php elseif (!$hasGallery): ?>
      <div class="empty-contrib">
        <span class="empty-icon">📷</span>
        <div>
          <h3><?= h($isEs ? 'Sé la primera persona en subir una foto' : 'Be the first to add a photo') ?></h3>
          <p><?= h($isEs ? 'Las fotos recientes ayudan a otros a reconocer el acceso, el agua y el espacio para estacionar.' : 'Recent photos help others recognize the access point, water, and parking setup.') ?></p>
        </div>
        <?php if (isAuthenticated()): ?>
        <button class="btn coral" type="button" data-action="openPhotoUploadModal" data-action-args='<?= $photoActionArgs ?>'><?= h($isEs ? 'Añadir foto' : 'Add photo') ?></button>
        <?php else: ?>
        <a class="btn coral" href="<?= h($photoLoginUrl) ?>"><?= h($isEs ? 'Iniciar sesión' : 'Sign in') ?></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>

    <section id="reviews" class="block">
      <div class="secrow">
        <h2 class="h2"><?= h($isEs ? 'Reseñas de la comunidad' : 'Community reviews') ?><?php if ($avgUserRating): ?> <small>★ <?= number_format($avgUserRating, 1) ?> (<?= $userReviewCount ?>)</small><?php endif; ?></h2>
        <?php if ($hasReviewsContent): ?>
        <?php if (isAuthenticated()): ?>
        <button class="btn" type="button" data-action="openReviewForm" data-action-args='<?= $reviewActionArgs ?>'><?= h($isEs ? 'Escribir reseña' : 'Write a review') ?></button>
        <?php else: ?>
        <a class="minor" href="<?= h($reviewLoginUrl) ?>"><?= h($isEs ? 'Inicia sesión para reseñar' : 'Sign in to review') ?></a>
        <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="revlist">
        <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
        <?php include APP_ROOT . '/components/review-card.php'; ?>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-contrib">
          <span class="empty-icon">✍️</span>
          <div>
            <h3><?= h($isEs ? 'Todavía no hay reseñas de Puerto Rico Beach Finder' : 'No Puerto Rico Beach Finder reviews yet') ?></h3>
            <p><?= h($isEs ? 'Comparte si el acceso fue fácil, cómo estaba el agua y qué debería saber la próxima persona.' : 'Share whether access was easy, how the water felt, and what the next person should know.') ?></p>
          </div>
          <?php if (isAuthenticated()): ?>
          <button class="btn coral" type="button" data-action="openReviewForm" data-action-args='<?= $reviewActionArgs ?>'><?= h($isEs ? 'Escribir reseña' : 'Write review') ?></button>
          <?php else: ?>
          <a class="btn coral" href="<?= h($reviewLoginUrl) ?>"><?= h($isEs ? 'Iniciar sesión' : 'Sign in') ?></a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <?= renderLocalListingsSection($beach, $lang, 'redesign') ?>

    <?php if (!empty($relatedGuides)): ?>
    <section class="block">
      <h2 class="h2"><?= h($isEs ? 'Planifica tu visita' : 'Planning your visit') ?></h2>
      <div class="relguides">
        <?php foreach ($relatedGuides as $guide): ?>
        <a href="<?= h($guide['url']) ?>" class="relguide"><span class="gt"><?= h($guide['title']) ?></span><span class="ar">→</span></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($tags)): ?>
    <section class="block">
      <h2 class="h2"><?= h($isEs ? 'Explorar por actividad' : 'Explore by activity') ?></h2>
      <div class="tagrow">
        <?php foreach ($tags as $tag): ?>
        <a href="<?= h(getLocalizedTagPageUrl($tag, $lang)) ?>"><?= h(__('tags.' . $tag)) ?> →</a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </main>

  <aside class="side">
    <?php $renderScoreCard(); ?>
    <?php $renderConditionsCard(); ?>
    <?php $renderLocationCard(); ?>

    <?php if (($beachReferralBottom ?? '') !== ''): ?>
    <div class="card refslot"><?= $beachReferralBottom ?></div>
    <?php endif; ?>
  </aside>

  <div class="post-amenities-recs">
    <?php if ($nearby): ?>
    <section id="nearby" class="block nearby-block">
      <div class="secrow">
        <h2 class="h2"><?= h($isEs ? 'Playas cercanas' : 'Nearby beaches') ?></h2>
        <a class="minor" href="#getting"><?= h($isEs ? 'Ver ubicación' : 'View location') ?></a>
      </div>
      <div class="ngrid nearby-grid">
        <?php foreach ($nearby as $nb):
          $nbImg = $cardImageUrl($nb, 'medium');
          $nbUrl = routeUrl('beach_detail', $lang, ['slug' => $nb['slug']]); ?>
        <a class="btile" href="<?= h($nbUrl) ?>"><div class="ph" style="background-image:url('<?= h($nbImg) ?>')"></div><div class="gr"></div><span class="di"><?= h($nb['distance_formatted'] ?? '') ?></span><div class="info"><div class="nm"><?= h($nb['name']) ?></div><div class="mu"><?= h($nb['municipality']) ?></div></div></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($similarBeaches)): ?>
    <section id="similar" class="block similar-block">
      <h2 class="h2"><?= h($isEs ? 'Playas similares' : 'Similar beaches') ?></h2>
      <div class="ngrid similar-grid">
        <?php foreach ($similarBeaches as $sb):
          $sbImg = $cardImageUrl($sb, 'thumb'); ?>
        <a class="btile" href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $sb['slug']])) ?>"><div class="ph" style="background-image:url('<?= h($sbImg) ?>')"></div><div class="gr"></div><?php if (!empty($sb['google_rating'])): ?><span class="di">★ <?= number_format($sb['google_rating'], 1) ?></span><?php endif; ?><div class="info"><div class="nm"><?= h($sb['name']) ?></div><div class="mu"><?= h($sb['municipality']) ?></div><div class="why"><?= h($similarReason($sb)) ?></div></div></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($faqs)): ?>
    <section id="faq" class="block faq">
      <h2 class="h2"><?= h($isEs ? 'Preguntas' : 'Questions') ?></h2>
      <?php foreach (array_slice($faqs, 0, 6) as $i => $f): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><?= h($f['question']) ?></summary><p><?= h($f['answer']) ?></p></details>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="block cta">
      <h2 class="h2"><?= h($isEs ? 'Descubre más playas' : 'Discover more beaches') ?></h2>
      <p><?= h($isEs ? 'Explora más de 400 playas en toda la isla con filtros, mapas y guías detalladas.' : 'Explore 400+ beaches across the island with filters, maps, and detailed guides.') ?></p>
      <a class="btn coral" href="<?= h(routeUrl('home', $lang)) ?>#beaches"><?= h($isEs ? 'Explorar todas las playas' : 'Explore all beaches') ?></a>
    </section>
  </div>
</div></div>

</div>

<?php
// Shared beach dialogs + their JS (share, lightbox, report, check-in, review,
// photo upload/lightbox, favorite toggle) — same component classic uses.
include APP_ROOT . '/components/beach/modals.php';
include APP_ROOT . '/components/beach/scripts.php';
?>

<script <?= cspNonceAttr() ?>>
(function(){
  var lat=<?= json_encode($lat) ?>, lng=<?= json_encode($lng) ?>;
  // subnav scroll-spy
  var secs=[].slice.call(document.querySelectorAll('.rd-beach section[id]'));
  var links=[].slice.call(document.querySelectorAll('.rd-beach .subnav a'));
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){var id=e.target.id;links.forEach(function(l){l.classList.toggle('on',l.getAttribute('href')==='#'+id);});}});},{rootMargin:'-45% 0px -50% 0px'});
    secs.forEach(function(s){io.observe(s);});
  }
  // Chart / Satellite (Google Maps embed, lazy)
  [].slice.call(document.querySelectorAll('.loc-card')).forEach(function(card){
    var chart=card.querySelector('.loc-chart'), sat=card.querySelector('.loc-sat'), loaded=false;
    [].slice.call(card.querySelectorAll('.locseg [data-loc]')).forEach(function(b){
      b.addEventListener('click',function(){
        [].slice.call(card.querySelectorAll('.locseg [data-loc]')).forEach(function(x){x.classList.remove('on');});
        b.classList.add('on');
        var isSat=b.dataset.loc==='sat';
        if(isSat&&!loaded){sat.innerHTML='<iframe title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q='+lat+','+lng+'&t=k&z=14&output=embed"></iframe>';loaded=true;}
        chart.style.display=isSat?'none':''; sat.style.display=isSat?'':'none';
      });
    });
  });
})();
</script>
