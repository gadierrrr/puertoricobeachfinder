<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Beach Detail: Nearby pills (wireframe)
 * Inline pills linking to similar beaches within range.
 *
 * Expects: $beach, $lang, $similarBeaches (optional — fetched if absent)
 */
if (!isset($similarBeaches)) {
    $similarBeaches = function_exists('getSimilarBeaches')
        ? getSimilarBeaches($beach['id'], $beach['tags'] ?? [], 6)
        : [];
}
if (empty($similarBeaches)) return;
?>
<section class="nearby-block" aria-label="<?= h(__('beach.nearby_within_min')) ?>">
    <h3 class="nearby-label"><?= h($lang === 'es' ? 'Cerca (≤ 20 min)' : 'Nearby (≤ 20 min)') ?></h3>
    <div class="nearby-pills">
        <?php foreach (array_slice($similarBeaches, 0, 6) as $similar): ?>
        <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $similar['slug']])) ?>" class="nearby-pill">
            <i data-lucide="map-pin" class="w-3.5 h-3.5" aria-hidden="true"></i>
            <span><?= h($similar['name']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
