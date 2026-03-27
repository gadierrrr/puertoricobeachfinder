<?php
/**
 * Beach Detail: Sticky Section Navigation
 * Horizontal scrolling tab bar with anchor links.
 *
 * Expects: $lang
 */
?>
    <!-- Sticky Section Navigation -->
    <div class="section-nav-wrapper"><nav class="beach-section-nav flex overflow-x-auto hide-scrollbar border-b border-white/10 mb-6 -mx-4 px-4 sticky top-14 z-30 bg-[rgba(15,26,31,0.97)] backdrop-blur-md">
        <a href="#section-overview" class="beach-nav-link active" data-section="section-overview"><?= h($lang === 'es' ? 'General' : 'Overview') ?></a>
        <a href="#section-best-time" class="beach-nav-link" data-section="section-best-time"><?= h($lang === 'es' ? 'Mejor Época' : 'Best Time') ?></a>
        <a href="#section-what-to-bring" class="beach-nav-link" data-section="section-what-to-bring"><?= h($lang === 'es' ? 'Qué Llevar' : 'What to Bring') ?></a>
        <a href="#section-history" class="beach-nav-link" data-section="section-history"><?= h($lang === 'es' ? 'Historia' : 'History') ?></a>
        <a href="#section-nearby" class="beach-nav-link" data-section="section-nearby"><?= h($lang === 'es' ? 'Cercano' : 'Nearby') ?></a>
        <a href="#section-tips" class="beach-nav-link" data-section="section-tips"><?= h($lang === 'es' ? 'Consejos' : 'Tips') ?></a>
        <a href="#section-map" class="beach-nav-link" data-section="section-map"><?= h($lang === 'es' ? 'Mapa' : 'Map') ?></a>
    </nav></div>

    <?php
    // Pre-fetch data needed for sidebar (weather loaded client-side for fast TTFB)
    require_once APP_ROOT . '/inc/crowd.php';
    $weather = null;
    $recommendation = null;
    $crowdLevel = getBeachCrowdLevel($beach['id'], 4);
    $sunTimes = getSunTimes($beach['lat'], $beach['lng']);
    ?>

