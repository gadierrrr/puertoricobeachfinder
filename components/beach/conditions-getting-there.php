<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Beach Detail: Live Conditions + Getting There (wireframe)
 * Two-panel block. Left = live conditions table, right = mini-map + map buttons + bullets.
 *
 * Reuses IDs #lc-water, #lc-uv, #lc-tide populated by scripts.php weather fetch.
 * Reuses #beach-map initialization from modals.php.
 *
 * Expects: $beach, $lang, $sunTimes
 */
$appleUrl = 'https://maps.apple.com/?daddr=' . urlencode($beach['lat'] . ',' . $beach['lng']) . '&q=' . urlencode($beach['name']);
$googleUrl = getDirectionsUrl($beach);
?>
<section id="section-conditions" class="cond-block scroll-mt-[120px]">
    <div class="cond-grid">
        <!-- Panel: Live Conditions -->
        <div class="cond-panel">
            <h3 class="panel-label"><?= h(__('beach.live_conditions')) ?></h3>
            <div class="cond-rows">
                <div class="cond-row"><span><?= h(__('beach.tide')) ?></span><b id="lc-tide">—</b></div>
                <div class="cond-row"><span><?= h(__('beach.condition_surf')) ?></span><b><?= $beach['surf'] ? h(getConditionLabel('surf', $beach['surf'])) : '—' ?></b></div>
                <div class="cond-row"><span><?= h(__('beach.condition_wind')) ?></span><b><?= $beach['wind'] ? h(getConditionLabel('wind', $beach['wind'])) : '—' ?></b></div>
                <div class="cond-row"><span><?= h(__('beach.water_temp')) ?></span><b id="lc-water">—</b></div>
                <div class="cond-row"><span>UV</span><b id="lc-uv">—</b></div>
                <div class="cond-row"><span><?= h(__('beach.condition_sargassum')) ?></span><b><?= $beach['sargassum'] ? h(getConditionLabel('sargassum', $beach['sargassum'])) : h(__('beach.condition_clear')) ?></b></div>
                <?php if ($sunTimes): ?>
                <div class="cond-row"><span><?= h(__('beach.sunrise')) ?></span><b><?= h($sunTimes['sunrise']) ?></b></div>
                <div class="cond-row"><span><?= h(__('beach.sunset')) ?></span><b><?= h($sunTimes['sunset']) ?></b></div>
                <?php endif; ?>
            </div>
            <!-- Hidden weather-widget-container keeps scripts.php fetch chain firing. -->
            <div id="weather-widget-container" class="hidden"
                 data-lat="<?= h($beach['lat']) ?>" data-lng="<?= h($beach['lng']) ?>"></div>
        </div>

        <!-- Panel: Getting There -->
        <div class="cond-panel" id="section-map">
            <h3 class="panel-label"><?= h($lang === 'es' ? 'Cómo llegar' : 'Getting there') ?></h3>
            <div id="beach-map" class="cond-mini-map"></div>
            <div class="cond-map-buttons">
                <a href="<?= h($appleUrl) ?>" target="_blank" rel="noopener" class="btn-maps"
                   data-bf-track="directions"
                   data-bf-beach-id="<?= h($beach['id']) ?>"
                   data-bf-source="getting_there_apple">
                    <?= h($lang === 'es' ? 'Apple Maps' : 'Apple Maps') ?> ↗
                </a>
                <a href="<?= h($googleUrl) ?>" target="_blank" rel="noopener" class="btn-maps"
                   data-bf-track="directions"
                   data-bf-beach-id="<?= h($beach['id']) ?>"
                   data-bf-source="getting_there_google">
                    <?= h($lang === 'es' ? 'Google Maps' : 'Google Maps') ?> ↗
                </a>
                <?php if ($beach['lat'] && $beach['lng']): ?>
                <a href="https://www.google.com/maps/search/parking+near+<?= urlencode($beach['lat'] . ',' . $beach['lng']) ?>"
                   target="_blank" rel="noopener" class="btn-maps btn-maps--ghost">
                    <?= h($lang === 'es' ? 'Punto de parking' : 'Parking pin') ?>
                </a>
                <?php endif; ?>
            </div>
            <ul class="cond-bullets">
                <?php if (!empty($beach['access_label'])): ?>
                <li><?= h($lang === 'es' ? 'Acceso' : 'Access') ?>: <b><?= h($beach['access_label']) ?></b></li>
                <?php endif; ?>
                <?php if (!empty($beach['parking_details'])): ?>
                <li><?= h($lang === 'es' ? 'Estacionamiento' : 'Parking') ?>: <?= h(trim(strip_tags(preg_split('/[.\n]/', (string)(($lang === 'es' && !empty($beach['parking_details_es'])) ? $beach['parking_details_es'] : $beach['parking_details']), 2)[0]))) ?></li>
                <?php endif; ?>
                <?php if (!empty($beach['safety_info'])): ?>
                <li class="warn">
                    <i data-lucide="alert-triangle" class="w-4 h-4" aria-hidden="true"></i>
                    <?= h(trim(strip_tags(preg_split('/[.\n]/', (string)(($lang === 'es' && !empty($beach['safety_info_es'])) ? $beach['safety_info_es'] : $beach['safety_info']), 2)[0]))) ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</section>
