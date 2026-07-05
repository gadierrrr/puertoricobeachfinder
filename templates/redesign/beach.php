<?php
/**
 * Redesign v2 — beach profile template.
 * Rendered inside <main> (header.php already output <head> + <body>, nav skipped).
 * Expects $beach (with ->tags/amenities/tips/gallery), $faqs, $lang from beach.php.
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
    'metro' => 'Metro · San Juan', 'north' => 'North Coast', 'west' => 'Porta del Sol',
    'south' => 'South Coast', 'east' => 'East · Fajardo', 'cays' => 'The Cays',
][$region] ?? '';
$access = strtolower((string) ($beach['access_label'] ?? ''));
$isBoat = str_contains($access, 'boat') || str_contains($access, 'kayak');
$surf = strtolower((string) ($beach['surf'] ?? ''));
$cover = $beach['cover_image'] ?: '/images/beaches/placeholder-beach.webp';
$rating = $score['rating'];
$reviewCount = (int) ($beach['google_review_count'] ?? 0);
$dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;

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
$uvLabel = function ($uv) {
    $uv = (float) $uv;
    if ($uv < 3) return ['Low', 'g']; if ($uv < 6) return ['Moderate', 'a'];
    if ($uv < 8) return ['High', 'r']; return ['Very high', 'r'];
};

// at-a-glance tiles derived from fields
$swimTile = in_array($surf, ['calm', 'small'], true)
    ? ['Easy — calm water', 'g']
    : ($surf === 'large' ? ['Advanced — strong surf', 'r'] : ['Moderate — check conditions', 'a']);
$snorkelGood = (bool) array_filter($tags, fn($t) => str_contains(strtolower($t), 'snorkel') || str_contains(strtolower($t), 'reef'));
$glance = [
    ['🏊', $isEs ? 'Nadar' : 'Swimming', $swimTile[0], $swimTile[1]],
    ['🤿', $isEs ? 'Snorkel' : 'Snorkeling', $snorkelGood ? 'Great — reef access' : 'Limited', $snorkelGood ? 'g' : 'a'],
    ['👨‍👩‍👧‍👦', $isEs ? 'Familia' : 'Family', !empty($beach['safe_for_children']) ? 'Yes — safe for kids' : 'Check conditions', !empty($beach['safe_for_children']) ? 'g' : 'a'],
    ['🧭', $isEs ? 'Acceso' : 'Access', ucfirst((string) ($beach['access_label'] ?? '—')), $isBoat ? 'r' : (str_contains($access, 'hike') || str_contains($access, 'walk') ? 'a' : 'g')],
    ['📅', $isEs ? 'Mejor época' : 'Best time', $beach['best_time'] ?: 'Dec – April', ''],
    ['🌾', $isEs ? 'Ambiente' : 'Vibe', !empty($chipTags) ? getTagLabel($chipTags[0]) : 'Scenic', ''],
];

// about text
$about = $isEs && !empty($beach['description_es']) ? $beach['description_es'] : ($beach['description'] ?? '');
$aboutParas = array_values(array_filter(array_map('trim', preg_split('/\n{2,}|\r\n\r\n/', $about) ?: [$about]), fn($p) => $p !== ''));
if (!$aboutParas && !empty($beach['notes'])) { $aboutParas = [trim((string) $beach['notes'])]; }
if (!$aboutParas) {
    $tagStr = $chipTags ? strtolower(implode(', ', array_map('getTagLabel', $chipTags))) : 'scenic';
    $aboutParas = [$beach['name'] . ' is a ' . $tagStr . ' beach in ' . $beach['municipality'] . ', on Puerto Rico’s ' . ($regionLabel ?: 'coast') . '. ' . (!empty($beach['access_label']) ? ucfirst((string) $beach['access_label']) . '.' : '')];
}

// tips
$tipList = [];
foreach (($beach['tips'] ?? []) as $t) {
    $txt = $isEs && !empty($t['tip_es']) ? $t['tip_es'] : ($t['tip'] ?? '');
    if ($txt) { $tipList[] = $txt; }
}
if (!$tipList && !empty($beach['local_tips'])) {
    $tipList = array_values(array_filter(array_map('trim', preg_split('/\n|•|\r/', (string) $beach['local_tips']))));
}
if (!$tipList) {
    $tipList = [
        'Arrive before 10am for the calmest water and best light',
        'Bring your own snorkel gear — clarity here is worth it',
        'Pack water, snacks and shade; vendors may be limited',
        'Reef-safe sunscreen only, and carry out everything you bring',
    ];
}
$tipList = array_slice($tipList, 0, 6);

// nearby
$nearby = array_slice(getNearbyBeaches((string) $beach['id'], $lat, $lng, 4) ?: [], 0, 4);
?>
<div class="rd rd-beach">

<header class="hero">
  <div class="hero-photo" style="background-image:url('<?= h($cover) ?>')"></div>
  <div class="hero-scrim"></div>
  <svg class="h-star" viewBox="0 0 100 100"><path d="M50 0 L59 41 L100 50 L59 59 L50 100 L41 59 L0 50 L41 41 Z"/></svg>
  <?php if ($isBoat): ?><div class="h-sticker">solo en bote<small>boat access only</small></div><?php endif; ?>

  <div class="wrap" style="width:100%">
    <div class="crumb"><a href="<?= h(routeUrl('home', $lang)) ?>">Home</a> / <a href="/#beaches">Beaches</a> / <?= h($beach['municipality']) ?> / <?= h($beach['name']) ?></div>
  </div>

  <div class="wrap hero-body" style="width:100%">
    <div class="h-tags">
      <?php foreach ($chipTags as $t): ?><span class="h-tag"><?= h(getTagLabel($t)) ?></span><?php endforeach; ?>
      <?php if ($isBoat): ?><span class="h-tag boat">🛥️ Boat-only</span><?php endif; ?>
    </div>
    <h1 class="h-title"><?= h($beach['name']) ?></h1>
    <div class="h-sub"><?= h($beach['municipality']) ?><?= $regionLabel ? ' · ' . h($regionLabel) : '' ?> <span class="coord">· <?= number_format($lat, 2) ?>°N <?= number_format(abs($lng), 2) ?>°W</span></div>
    <div class="h-meta">
      <div class="h-score"><span class="num"><?= $score['overall'] ?></span><span class="lab">Beach<br><b>Score</b></span></div>
      <?php if ($rating > 0): ?>
      <div><div class="stars"><?= str_repeat('★', (int) round($rating)) . str_repeat('☆', 5 - (int) round($rating)) ?></div><div style="font-family:var(--data);font-size:.8rem;letter-spacing:.04em;opacity:.85"><?= number_format($rating, 1) ?><?= $reviewCount ? ' · ' . number_format($reviewCount) . ' reviews' : '' ?></div></div>
      <?php endif; ?>
      <div class="h-actions">
        <a class="btn coral" href="<?= h($dirUrl) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 21l9-4 9 4z"/></svg><?= h($isEs ? 'Cómo llegar' : 'Directions') ?></a>
        <a class="btn" href="#">♡ <?= h($isEs ? 'Guardar' : 'Save') ?></a>
        <a class="btn" href="#">↗ <?= h($isEs ? 'Compartir' : 'Share') ?></a>
      </div>
    </div>
  </div>
</header>

<nav class="subnav"><div class="wrap">
  <a href="#overview" class="on">Overview</a><a href="#scores">Scores</a><a href="#about">About</a>
  <a href="#tips">Tips</a><a href="#getting">Getting there</a><a href="#tours">Tours</a><a href="#nearby">Nearby</a><a href="#faq">FAQ</a>
</div></nav>

<div class="wrap"><div class="body">
  <main>
    <section id="overview" class="block">
      <span class="eyebrow"><?= h($isEs ? 'Vistazo' : 'At a glance') ?></span>
      <?php if (!empty($about)): ?><p class="lead" style="margin:8px 0 18px"><?= h(mb_strlen($about) > 220 ? mb_substr($about, 0, 220) . '…' : $about) ?></p><?php endif; ?>
      <div class="glance">
        <?php foreach ($glance as $g): ?>
        <div class="gtile"><span class="ic"><?= $g[0] ?></span><div><div class="k"><?= h($g[1]) ?></div><div class="v <?= $g[3] ?>"><?= h($g[2]) ?></div></div></div>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="scores" class="block">
      <h2 class="h2"><?= h($isEs ? 'Puntuación' : 'Beach Score') ?></h2>
      <div class="scorecard">
        <div class="top"><div class="big"><?= $score['overall'] ?><small>/100</small></div><div style="font-family:var(--data);text-transform:uppercase;letter-spacing:.1em;font-size:.72rem;color:var(--ink-60);text-align:right">How this beach<br>rates for a day out</div></div>
        <div class="scores">
          <?php
            $bars = array_merge([['Overall', $score['overall'], bsColor($score['overall'])]], $score['bars']);
            $icons = ['Overall' => '⭐', 'Calm water' => '🌊', 'Snorkeling' => '🤿', 'Seclusion' => '🌾', 'Family' => '👨‍👩‍👧', 'Facilities' => '🚻'];
            foreach ($bars as $b): ?>
          <div class="score"><span><?= ($icons[$b[0]] ?? '•') . ' ' . h($b[0]) ?></span><div class="bar"><i class="<?= $b[2] ?>" style="width:<?= $b[1] ?>%"></i></div><span class="pct"><?= $b[1] ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="about" class="block prose">
      <h2 class="h2"><?= h($isEs ? 'Sobre ' : 'About ') . h($beach['name']) ?></h2>
      <?php foreach ($aboutParas as $p): if ($p === '') continue; ?><p><?= h($p) ?></p><?php endforeach; ?>
    </section>

    <section id="tips" class="block">
      <h2 class="h2"><?= h($isEs ? 'Consejos' : 'Local tips') ?></h2>
      <div class="tips">
        <?php foreach ($tipList as $t): ?><div class="tip"><span class="ck">✔</span><span><?= h($t) ?></span></div><?php endforeach; ?>
      </div>
    </section>

    <section id="getting" class="block">
      <h2 class="h2"><?= h($isEs ? 'Cómo llegar y seguridad' : 'Getting there & safety') ?></h2>
      <div style="display:grid;gap:12px">
        <div class="callout"><span class="ic"><?= $isBoat ? '🛥️' : '🧭' ?></span><div><h4><?= h($isEs ? 'Acceso' : 'Access') ?></h4><p><?= h(ucfirst((string) ($beach['access_label'] ?? ''))) ?><?= !empty($beach['parking_details']) ? '. ' . h($beach['parking_details']) : '' ?></p></div></div>
        <?php if (!empty($beach['safety_info'])): ?>
        <div class="callout warn"><span class="ic">⚠️</span><div><h4><?= h($isEs ? 'Seguridad' : 'Swim smart') ?></h4><p><?= h($beach['safety_info']) ?></p></div></div>
        <?php else: ?>
        <div class="callout warn"><span class="ic">⚠️</span><div><h4>Swim smart</h4><p>No lifeguards. Never swim alone, reapply waterproof sunscreen, and check the marine forecast before you go — especially in hurricane season (Jun–Nov).</p></div></div>
        <?php endif; ?>
      </div>
    </section>

    <?= renderToursSection($beach, $lang, 'redesign') ?>

    <?= renderLocalListingsSection($beach, $lang, 'redesign') ?>

    <?php if ($nearby): ?>
    <section id="nearby" class="block">
      <h2 class="h2"><?= h($isEs ? 'Playas cercanas' : 'Nearby beaches') ?></h2>
      <div class="ngrid">
        <?php foreach ($nearby as $nb):
          $nbImg = $nb['cover_image'] ?: '/images/beaches/placeholder-beach.webp';
          $nbUrl = ($isEs ? '/es/playa/' : '/beach/') . $nb['slug']; ?>
        <a class="btile" href="<?= h($nbUrl) ?>"><div class="ph" style="background-image:url('<?= h($nbImg) ?>')"></div><div class="gr"></div><span class="di"><?= h($nb['distance_formatted'] ?? '') ?></span><div class="info"><div class="nm"><?= h($nb['name']) ?></div><div class="mu"><?= h($nb['municipality']) ?></div></div></a>
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
  </main>

  <aside class="side">
    <div class="card">
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

    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:13px">
        <h4 style="margin:0"><?= h($isEs ? 'Dónde queda' : 'Where it is') ?></h4>
        <div class="locseg"><button class="on" data-loc="chart" type="button">Chart</button><button data-loc="sat" type="button">Satellite</button></div>
      </div>
      <div id="locChart"><?= renderIslandLocator($lat, $lng) ?></div>
      <div id="locSat" class="loc-sat" style="display:none"></div>
      <div class="loc-note"><span><?= h($beach['municipality']) ?><?= $regionLabel ? ' · ' . h($regionLabel) : '' ?></span><b><?= number_format($lat, 2) ?>°N <?= number_format(abs($lng), 2) ?>°W</b></div>
    </div>

    <div class="card">
      <h4><?= h($isEs ? '¿Estuviste aquí?' : 'Been here?') ?></h4>
      <p style="font-size:.9rem;color:var(--ink-60);margin-bottom:12px"><?= h($isEs ? 'Reporta las condiciones de hoy para ayudar a otros.' : "Report today's conditions to help other beachgoers.") ?></p>
      <button class="checkin">✔ <?= h($isEs ? 'Check in' : 'Check in') ?></button>
    </div>
  </aside>
</div></div>

<div class="dockbar">
  <a class="btn coral" href="<?= h($dirUrl) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 21l9-4 9 4z"/></svg><?= h($isEs ? 'Cómo llegar' : 'Directions') ?></a>
  <a class="btn" href="#">♡ <?= h($isEs ? 'Guardar' : 'Save') ?></a>
  <a class="btn" href="#">↗ <?= h($isEs ? 'Compartir' : 'Share') ?></a>
</div>
</div>

<script <?= cspNonceAttr() ?>>
(function(){
  var lat=<?= json_encode($lat) ?>, lng=<?= json_encode($lng) ?>;
  // Fixed dockbar needs body padding + chat-FAB clearance (see redesign.css)
  document.body.classList.add('rd-has-dock');
  // subnav scroll-spy
  var secs=[].slice.call(document.querySelectorAll('.rd-beach section[id]'));
  var links=[].slice.call(document.querySelectorAll('.rd-beach .subnav a'));
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){var id=e.target.id;links.forEach(function(l){l.classList.toggle('on',l.getAttribute('href')==='#'+id);});}});},{rootMargin:'-45% 0px -50% 0px'});
    secs.forEach(function(s){io.observe(s);});
  }
  // Chart / Satellite (Google Maps embed, lazy)
  var chart=document.getElementById('locChart'), sat=document.getElementById('locSat'), loaded=false;
  [].slice.call(document.querySelectorAll('.locseg [data-loc]')).forEach(function(b){
    b.addEventListener('click',function(){
      [].slice.call(document.querySelectorAll('.locseg [data-loc]')).forEach(function(x){x.classList.remove('on');});
      b.classList.add('on');
      var isSat=b.dataset.loc==='sat';
      if(isSat&&!loaded){sat.innerHTML='<iframe title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q='+lat+','+lng+'&t=k&z=14&output=embed"></iframe>';loaded=true;}
      chart.style.display=isSat?'none':''; sat.style.display=isSat?'':'none';
    });
  });
})();
</script>
