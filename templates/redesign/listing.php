<?php
/**
 * Redesign v2 — shared listing template.
 * Used by the tag/amenity pages (beaches-by-tag.php), municipality pages
 * (municipality.php), and dynamic proximity pages (beaches-near.php).
 * Rendered inside <main> (header.php already emitted <head> + rd nav).
 *
 * Driven by a $listing array built by the calling page. Every block is
 * optional and renders only when its key is present:
 *   'breadcrumbs'    [[label, url|null], ...] visible crumb trail (no schema)
 *   'eyebrow'        string — small green label above the h1
 *   'h1'             string
 *   'intro'          [string, ...] plain-text hero paragraphs (escaped here)
 *   'stats'          [[value, label], ...] stat pills
 *   'anchors'        [[label, href], ...] jump-nav row (sticky)
 *   'extraHtml'      string — pre-rendered safe HTML slot (send-list capture)
 *   'tagLinks'       [[label, url|null, count|null], ...] chip row; null url
 *                    renders a non-linked chip (classic tag-distribution parity)
 *   'tagLinksLabel'  string — small label before the tagLinks chips
 *   'introHtml'      [string, ...] pre-escaped HTML paragraphs, echoed RAW —
 *                    callers must h() all interpolated values themselves
 *   'beaches'        rows with slug, name, municipality, cover_image,
 *                    google_rating, google_review_count, tags[], and optional
 *                    distance_formatted (shown as a badge). ALL rows render:
 *                    first 30 as large tiles, the rest as a compact list.
 *   'beachesHeading' string, 'beachesSub' string — directory header
 *   'municipalities' [[name, url, count], ...] cross-link cards
 *   'municipalitiesHeading' string
 *   'faqs'           [[question, answer], ...] <details> accordions (verbatim —
 *                    tag pages mirror these in FAQPage JSON-LD)
 *   'faqHeading'     string
 *   'siblings'       [[label, url, extra|null], ...] chip links (other areas)
 *   'siblingsHeading' string
 *   'quizCta'        bool — quiz band via routeUrl('quiz', $lang)
 */

$isEs = ($lang ?? 'en') === 'es';
$listing = $listing ?? [];
?>
<div class="rd rd-listing">

<!-- ===== HERO ===== -->
<header class="lhero">
  <div class="wrap">
    <?php if (!empty($listing['breadcrumbs'])): ?>
    <nav class="crumb" aria-label="Breadcrumb">
      <?php foreach ($listing['breadcrumbs'] as $ci => [$cLabel, $cUrl]): ?><?= $ci > 0 ? ' / ' : '' ?><?php if ($cUrl): ?><a href="<?= h($cUrl) ?>"><?= h($cLabel) ?></a><?php else: ?><?= h($cLabel) ?><?php endif; ?><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!empty($listing['eyebrow'])): ?><p class="eyebrow"><?= h($listing['eyebrow']) ?></p><?php endif; ?>
    <?php if (!empty($listing['h1'])): ?><h1><?= h($listing['h1']) ?><span class="dot">.</span></h1><?php endif; ?>
    <?php foreach ($listing['intro'] ?? [] as $introPara): ?>
    <p class="lede"><?= h($introPara) ?></p>
    <?php endforeach; ?>
    <?php if (!empty($listing['stats'])): ?>
    <div class="pills">
      <?php foreach ($listing['stats'] as [$statValue, $statLabel]): ?>
      <span class="pill"><b><?= h($statValue) ?></b><?= h($statLabel) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</header>

<?php if (!empty($listing['anchors'])): ?>
<nav class="anchors" aria-label="<?= h($isEs ? 'Secciones de la página' : 'Page sections') ?>"><div class="wrap">
  <?php foreach ($listing['anchors'] as [$aLabel, $aHref]): ?>
  <a href="<?= h($aHref) ?>"><?= h($aLabel) ?></a>
  <?php endforeach; ?>
</div></nav>
<?php endif; ?>

<?php if (!empty($listing['extraHtml'])): ?>
<div class="wrap lextra"><?= $listing['extraHtml'] ?></div>
<?php endif; ?>

<?php if (!empty($listing['tagLinks'])): ?>
<div class="wrap ltags">
  <div class="chiprow">
    <?php if (!empty($listing['tagLinksLabel'])): ?><span class="tlab"><?= h($listing['tagLinksLabel']) ?></span><?php endif; ?>
    <?php foreach ($listing['tagLinks'] as $tl): [$tlLabel, $tlUrl] = $tl; $tlCount = $tl[2] ?? null; ?>
    <?php if ($tlUrl): ?><a href="<?= h($tlUrl) ?>"><?= h($tlLabel) ?><?php if ($tlCount !== null): ?> <b><?= (int) $tlCount ?></b><?php endif; ?></a>
    <?php else: ?><span class="chip"><?= h($tlLabel) ?><?php if ($tlCount !== null): ?> <b><?= (int) $tlCount ?></b><?php endif; ?></span><?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($listing['introHtml'])): ?>
<div class="wrap"><div class="lintro">
  <?php foreach ($listing['introHtml'] as $introHtmlPara): ?>
  <p><?= $introHtmlPara /* pre-escaped by caller */ ?></p>
  <?php endforeach; ?>
</div></div>
<?php endif; ?>

<?php if (!empty($listing['beaches'])):
    $lstBeaches = array_values($listing['beaches']);
    $lstTiles = array_slice($lstBeaches, 0, 30);
    $lstRest = array_slice($lstBeaches, 30);
?>
<div class="wrap">
<section id="beaches" class="lsec">
  <?php if (!empty($listing['beachesHeading'])): ?><h2 class="h2"><?= h($listing['beachesHeading']) ?></h2><?php endif; ?>
  <?php if (!empty($listing['beachesSub'])): ?><p class="lsub"><?= h($listing['beachesSub']) ?></p><?php endif; ?>
  <div class="lgrid">
    <?php foreach ($lstTiles as $lstIdx => $b):
        $bUrl = routeUrl('beach_detail', $lang, ['slug' => $b['slug']]);
        $bImg = ($b['cover_image'] ?? '') ?: '/images/beaches/placeholder-beach.webp';
    ?>
    <a class="btile" href="<?= h($bUrl) ?>">
      <img class="btile-photo" src="<?= h($bImg) ?>" alt="<?= h($b['name']) ?>"
           loading="<?= $lstIdx < 6 ? 'eager' : 'lazy' ?>" width="400" height="300">
      <div class="btile-grad"></div>
      <div class="bt-top">
        <span class="bt-rank"><?= $lstIdx + 1 ?></span>
        <?php if (!empty($b['distance_formatted'])): ?><span class="bt-dist"><?= h($b['distance_formatted']) ?></span><?php endif; ?>
      </div>
      <div class="bt-info">
        <div class="bt-name"><?= h($b['name']) ?></div>
        <div class="bt-muni"><?= h($b['municipality']) ?></div>
        <div class="bt-stats">
          <?php if (!empty($b['google_rating'])): ?>
          <span>★ <?= number_format((float) $b['google_rating'], 1) ?><?php if (!empty($b['google_review_count'])): ?> <em>(<?= number_format((int) $b['google_review_count']) ?>)</em><?php endif; ?></span>
          <?php endif; ?>
          <?php foreach (array_slice($b['tags'] ?? [], 0, 2) as $bTag): ?>
          <span class="bt-tag"><?= h(getTagLabel($bTag)) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($lstRest)): ?>
  <div class="clist">
    <?php foreach ($lstRest as $b):
        $bUrl = routeUrl('beach_detail', $lang, ['slug' => $b['slug']]);
        $bImg = ($b['cover_image'] ?? '') ?: '/images/beaches/placeholder-beach.webp';
    ?>
    <a class="crow" href="<?= h($bUrl) ?>">
      <img src="<?= h($bImg) ?>" alt="<?= h($b['name']) ?>" loading="lazy" width="64" height="64">
      <span class="ctx">
        <span class="cn"><?= h($b['name']) ?></span>
        <span class="cm"><?= h($b['municipality']) ?><?php if (!empty($b['distance_formatted'])): ?> · <?= h($b['distance_formatted']) ?><?php endif; ?></span>
      </span>
      <?php if (!empty($b['google_rating'])): ?><span class="cr">★ <?= number_format((float) $b['google_rating'], 1) ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
</div>
<?php endif; ?>

<?php if (!empty($listing['municipalities'])): ?>
<div class="wrap">
<section id="by-municipality" class="lsec">
  <h2 class="h2"><?= h($listing['municipalitiesHeading'] ?? ($isEs ? 'Por Municipio' : 'By Municipality')) ?></h2>
  <div class="mcards">
    <?php foreach ($listing['municipalities'] as [$mName, $mUrl, $mCount]): ?>
    <a class="mcard" href="<?= h($mUrl) ?>">
      <span class="mn"><?= h($mName) ?></span>
      <span class="mc"><?= (int) $mCount ?> <?= h($isEs ? 'playas' : 'beaches') ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
</div>
<?php endif; ?>

<?php if (!empty($listing['faqs'])): ?>
<div class="wrap">
<section id="faq" class="lsec faq">
  <h2 class="h2"><?= h($listing['faqHeading'] ?? ($isEs ? 'Preguntas Frecuentes' : 'Frequently Asked Questions')) ?></h2>
  <?php foreach ($listing['faqs'] as [$faqQ, $faqA]): ?>
  <details><summary><?= h($faqQ) ?></summary><p><?= h($faqA) ?></p></details>
  <?php endforeach; ?>
</section>
</div>
<?php endif; ?>

<?php if (!empty($listing['siblings'])): ?>
<div class="wrap">
<section class="lsec">
  <h2 class="h2"><?= h($listing['siblingsHeading'] ?? ($isEs ? 'Explorar Otras Áreas' : 'Explore Other Areas')) ?></h2>
  <div class="chiprow">
    <?php foreach ($listing['siblings'] as $sib): [$sibLabel, $sibUrl] = $sib; $sibExtra = $sib[2] ?? null; ?>
    <a href="<?= h($sibUrl) ?>"><?= h($sibLabel) ?><?php if ($sibExtra !== null && $sibExtra !== ''): ?> <b><?= h($sibExtra) ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </div>
</section>
</div>
<?php endif; ?>

<?php if (!empty($listing['quizCta'])): ?>
<div class="wrap">
<section class="lsec quizcta">
  <h2><?= h($isEs ? '¿No sabes cuál playa es para ti?' : 'Not sure which beach is right for you?') ?></h2>
  <p><?= h($isEs ? 'Toma nuestro quiz de 60 segundos y te recomendaremos playas perfectas para ti.' : "Take our 60-second quiz and we'll recommend the perfect beaches for you.") ?></p>
  <a class="btn coral" href="<?= h(routeUrl('quiz', $lang)) ?>"><?= h($isEs ? 'Tomar el Quiz' : 'Take the Beach Quiz') ?></a>
</section>
</div>
<?php endif; ?>

</div>
