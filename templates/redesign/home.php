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
    $rd[] = [
        'n' => $b['name'], 'slug' => $b['slug'], 'm' => $b['municipality'],
        'rg' => $region, 'img' => $b['cover_image'] ?: '/images/beaches/placeholder-beach.webp',
        'sc' => $sc['overall'], 'rt' => round((float) $sc['rating'], 1),
        'water' => $surf ?: 'calm', 'surf' => $surfWord[$surf] ?? 'flat', 'crowd' => $crowd,
        'bars' => $sc['bars'],
    ];
}
// default order: Beach Score desc
usort($rd, fn($a, $b) => $b['sc'] <=> $a['sc']);
$total = count($rd);
$municipios = count(array_unique(array_column($rd, 'm')));

// island-representative conditions (San Juan) — cached
$w = null; try { $w = getWeatherForLocation(18.46, -66.11); } catch (\Throwable $e) {}
$wc = $w['current'] ?? [];

$regionMeta = [
    'north' => ['North Coast', '31%', '21%', [200, 170]],
    'metro' => ['Metro · San Juan', '55%', '25%', [340, 172]],
    'west'  => ['Porta del Sol', '8%', '50%', [90, 210]],
    'south' => ['South Coast', '37%', '80%', [290, 250]],
    'east'  => ['East · Fajardo', '71%', '65%', [470, 208]],
    'cays'  => ['The Cays', '92%', '19%', [508, 196]],
];
$popular = array_slice($popularBeaches ?? [], 0, 3);

// ---- homepage design settings (admin-editable: /admin/homepage-design) ----
require_once APP_ROOT . '/inc/settings.php';
require_once APP_ROOT . '/inc/homepage_fonts.php';
$hpDesign = $rdDesign ?? getHomepageDesign();
$hpEditor = $rdEditorMode ?? false;
$heroStyle = '';
if ($hpDesign['bg_mode'] === 'color' && $hpDesign['bg_color'] !== 'default') {
    $heroStyle = 'background:' . h($hpDesign['bg_color']);
}
$heroClasses = 'hero-band' . (homepageHeroIsLight($hpDesign) ? ' dark-hero' : '');
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

<!-- ===== HERO BAND ===== -->
<header class="<?= $heroClasses ?>"<?= $heroStyle ? ' style="' . $heroStyle . '"' : '' ?>>
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
    <div class="topbar">
      <a class="brand" href="<?= h(routeUrl('home', $lang)) ?>"><svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8" stroke-linecap="round"/></svg>Playa Finder</a>
      <nav class="nav"><a href="#beachdir">Coasts</a><a href="#beachdir">Conditions</a><a href="/guides">Guides</a><a href="/quiz">Quiz</a></nav>
      <span class="lang">EN · ES</span>
    </div>
    <div class="hero-grid">
      <div>
        <p class="eyebrow"><?= h($isEs ? 'Las playas de la isla, en un mapa' : "The island's beaches, charted") ?></p>
        <h1 class="headline"><?= h($isEs ? 'Encuentra tu' : 'Find your') ?><br><span class="em"><?= h($isEs ? 'playa' : 'playa') ?></span><span class="dot">.</span></h1>
        <p class="lede"><?= h($isEs ? 'Cada playa pública de Puerto Rico — por costa, agua y gentío. Elige una costa para empezar.' : 'Every public beach in Puerto Rico — by coast, water, and crowd. Pick a coast to start.') ?></p>
        <div class="search">
          <svg class="mag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
          <input id="heroSearch" type="text" placeholder="<?= h($isEs ? 'Busca una playa o pueblo…' : 'Search a beach or town…') ?>" aria-label="Search beaches">
        </div>
        <p class="count"><span class="tri">▸</span> <?= h($isEs ? 'Mostrando' : 'Showing') ?> <b id="rdCount"><?= number_format($total) ?></b>&nbsp;<?= h($isEs ? 'playas' : 'beaches') ?> <span class="muted" id="rdScope">· <?= $municipios ?> municipios</span></p>
        <div class="chips" role="group" aria-label="Filter by condition">
          <?php foreach (['Calm & clear', 'Surf', 'Snorkeling', 'Balneario', 'Secluded', 'Bandera Azul', 'Accessible'] as $c): ?>
          <button class="chip" type="button" aria-pressed="false"><?= h($c) ?></button>
          <?php endforeach; ?>
        </div>
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
          <span class="rname"><?= h($m[0]) ?></span><span class="rcount"><?= $regionCounts[$key] ?> beaches</span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</header>

<!-- ===== DIRECTORY ===== -->
<div class="wrap">
<section id="beachdir" class="beachdir">
  <div class="dir-head">
    <div><span class="sub" id="rdDirSub"><?= h($isEs ? 'Toda la isla' : 'The whole island') ?></span><h2 id="rdDirTitle"><?= h($isEs ? 'Encuentra tu playa' : 'Find your beach') ?></h2></div>
    <span class="dir-count" id="rdDirCount"><?= number_format($total) ?> beaches</span>
  </div>
  <div class="dir-toolbar">
    <button class="tbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18M6 12h12M10 19h4" stroke-linecap="round"/></svg>Filters</button>
    <div class="dir-search"><svg class="mag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg><input id="rdSearch" placeholder="<?= h($isEs ? 'Busca una playa o pueblo…' : 'Search a beach or town…') ?>" aria-label="Search beaches"></div>
    <select class="dir-select" id="rdSort" aria-label="Sort beaches">
      <option value="score"><?= h($isEs ? 'Orden: Puntuación' : 'Sort: Beach Score') ?></option>
      <option value="rating"><?= h($isEs ? 'Mejor calificadas' : 'Top rated') ?></option>
      <option value="calm"><?= h($isEs ? 'Aguas más calmadas' : 'Calmest water') ?></option>
      <option value="crowd"><?= h($isEs ? 'Menos gentío' : 'Least crowded') ?></option>
    </select>
    <div class="seg"><button class="on" data-view="grid">▦ Grid</button><button data-view="map">◵ Map</button></div>
  </div>
  <div class="dir-body">
    <div>
      <div class="dir-grid" id="rdGrid"></div>
      <div class="dir-more"><button id="rdMore"><?= h($isEs ? 'Ver más' : 'Load more') ?> · <span id="rdRemain">0</span> beaches</button></div>
    </div>
    <aside class="dir-side">
      <div class="card-w"><h4><?= h($isEs ? 'Ahora en la costa' : 'On the coast right now') ?></h4>
        <?php if ($wc): ?>
        <div class="cond"><span class="k"><?= h($isEs ? 'Clima' : 'Weather') ?></span><span class="v"><?= round((float)($wc['temperature'] ?? 0)) ?>° <span style="font-size:.7rem;color:var(--ink-60)"><?= h($wc['description'] ?? '') ?></span></span></div>
        <div class="cond"><span class="k"><?= h($isEs ? 'Viento' : 'Wind') ?></span><span class="v"><?= round((float)($wc['wind_speed'] ?? 0)) ?> km/h</span></div>
        <div class="cond"><span class="k"><?= h($isEs ? 'Amanecer' : 'Sunrise') ?></span><span class="v"><?= ($w['sunrise'] ?? null) ? date('g:i a', strtotime($w['sunrise'])) : '—' ?></span></div>
        <div class="cond"><span class="k"><?= h($isEs ? 'Atardecer' : 'Sunset') ?></span><span class="v"><?= ($w['sunset'] ?? null) ? date('g:i a', strtotime($w['sunset'])) : '—' ?></span></div>
        <?php else: ?><div class="cond"><span class="k">San Juan</span><span class="v">—</span></div><?php endif; ?>
      </div>
      <div class="card-w"><h4><?= h($isEs ? 'Populares ahora' : 'Popular right now') ?></h4>
        <?php foreach ($popular as $p): $pImg = ($p['cover_image'] ?? '') ?: '/images/beaches/placeholder-beach.webp'; ?>
        <a class="nearby" href="<?= h(($isEs ? '/es/playa/' : '/beach/') . $p['slug']) ?>"><div class="ph" style="background-image:url('<?= h($pImg) ?>')"></div><div><div class="nm"><?= h($p['name']) ?></div><div class="mi"><?= h($p['municipality']) ?></div></div></a>
        <?php endforeach; ?>
      </div>
      <div class="card-w"><h4><?= h($isEs ? 'Por qué los locales la usan' : 'Why locals use it') ?></h4>
        <div class="cond"><span class="k"><?= h($isEs ? 'Calificación' : 'Avg rating') ?></span><span class="v"><?= number_format((float)($siteStats['avg_rating'] ?? 4.5), 1) ?></span></div>
        <div class="cond"><span class="k"><?= h($isEs ? 'Reseñas' : 'Reviews') ?></span><span class="v"><?= number_format((int)($siteStats['total_reviews'] ?? 0)) ?></span></div>
        <div class="cond"><span class="k"><?= h($isEs ? 'Playas' : 'Charted') ?></span><span class="v"><?= number_format($publishedCount ?? $total) ?></span></div>
      </div>
    </aside>
  </div>
</section>
<footer class="foot"><span>Playa Finder · Puerto Rico</span><span><?= h($isEs ? 'Rediseño en progreso' : 'New design · in progress') ?></span></footer>
</div>
</div>

<script <?= cspNonceAttr() ?>>window.RD_BEACHES = <?= json_encode($rd, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script <?= cspNonceAttr() ?> src="/assets/js/redesign-home.js?v=1"></script>
<?php if ($hpEditor): ?>
<!-- Admin homepage-design editor preview (loaded only inside /admin/homepage-design iframe) -->
<script <?= cspNonceAttr() ?> src="/assets/js/redesign-editor-preview.js?v=1"></script>
<?php endif; ?>
