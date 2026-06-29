<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Discovery: Compact Beach Card (wireframe 1:1)
 *
 * Layout:
 *   [ thumb 120px ] │ Name · Municipality
 *                   │ rating · tag · tag
 *                   │ amenity and condition pills
 *                   │ Tide / Sarg / UV pills
 *                   │ Save ♡   + Add to day
 *
 * Expects: $beach, $lang
 */

require_once __DIR__ . '/../../inc/helpers.php';

$lang  = $lang ?? (function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en');
$slug  = $beach['slug']        ?? '';
$name  = $beach['name']        ?? '—';
$muni  = $beach['municipality'] ?? '';
$cover = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$rating = isset($beach['google_rating']) ? (float)$beach['google_rating'] : null;
$tags  = $beach['tags']        ?? [];
$amenities = $beach['amenities'] ?? [];
$sargassum = $beach['sargassum'] ?? null;
$surf  = $beach['surf']        ?? null;
$wind  = $beach['wind']        ?? null;

$href = function_exists('routeUrl') ? routeUrl('beach_detail', $lang, ['slug' => $slug]) : "/beach/{$slug}";
$thumb = function_exists('getThumbnailUrl') ? getThumbnailUrl($cover) : $cover;

// Top 2 tags for the title row
$titleTags = array_slice($tags, 0, 2);

// Amenity icon + compact label map (matches production amenity vocabulary)
$amenityIcons = [
    'parking'           => ['icon' => 'car', 'label' => 'Parking'],
    'restrooms'         => ['icon' => 'door-open', 'label' => 'Restrooms'],
    'shade-structures'  => ['icon' => 'umbrella', 'label' => 'Shade'],
    'shade'             => ['icon' => 'umbrella', 'label' => 'Shade'],
    'lifeguard'         => ['icon' => 'life-buoy', 'label' => 'Lifeguard'],
    'food'              => ['icon' => 'utensils', 'label' => 'Kiosko'],
    'food-vendors'      => ['icon' => 'utensils', 'label' => 'Kiosko'],
    'showers'           => ['icon' => 'shower-head', 'label' => 'Showers'],
    'picnic-areas'      => ['icon' => 'trees', 'label' => 'Picnic'],
    'water-sports'      => ['icon' => 'waves', 'label' => 'Water'],
    'equipment-rental'  => ['icon' => 'sailboat', 'label' => 'Rental'],
    'accessibility'     => ['icon' => 'accessibility', 'label' => 'Access'],
    'camping'           => ['icon' => 'tent', 'label' => 'Camping'],
];
$renderableAmenities = array_values(array_intersect(array_keys($amenityIcons), $amenities));
?>
<article class="discovery-card" data-beach-id="<?= h($beach['id'] ?? '') ?>"
         data-lat="<?= h($beach['lat'] ?? '') ?>" data-lng="<?= h($beach['lng'] ?? '') ?>">
    <a href="<?= h($href) ?>" class="discovery-card__thumb">
        <img src="<?= h($thumb) ?>" alt="<?= h($name) ?>" loading="lazy">
        <?php if ($muni): ?>
        <span class="discovery-card__badge"><?= h($muni) ?></span>
        <?php endif; ?>
    </a>
    <div class="discovery-card__body">
        <h3 class="discovery-card__title">
            <a href="<?= h($href) ?>"><?= h($name) ?></a>
        </h3>
        <div class="discovery-card__meta">
            <?php if ($rating): ?>
            <span class="discovery-card__rating">
                <i data-lucide="star" class="w-3.5 h-3.5" aria-hidden="true"></i>
                <?= number_format($rating, 1) ?>
            </span>
            <?php endif; ?>
            <?php foreach ($titleTags as $t): ?>
            <span class="discovery-card__tag"><?= h(function_exists('getTagLabel') ? getTagLabel($t) : $t) ?></span>
            <?php endforeach; ?>
        </div>

        <?php
        // Unified "kv" pill row: amenities + live conditions rendered as
        // small rounded pills (wireframe 1:1 — see .kv span style in
        // prbf-wireframe.html). Ok/warn variants for condition quality.
        $kvPills = [];
        foreach (array_slice($renderableAmenities, 0, 4) as $a) {
            $icon  = $amenityIcons[$a]['icon'];
            $label = $amenityIcons[$a]['label'];
            $kvPills[] = ['class' => '', 'icon' => $icon, 'text' => $label];
        }
        if (isset($beach['has_signal']) && (int)$beach['has_signal'] === 0) {
            $kvPills[] = ['class' => 'warn', 'icon' => 'wifi-off', 'text' => 'No signal'];
        }
        if ($sargassum) {
            $cls = getConditionDotClass($sargassum);
            $kvClass = $cls === 'green' ? 'ok' : ($cls === 'red' ? 'warn' : ($cls === 'yellow' ? 'sun' : ''));
            $kvPills[] = ['class' => $kvClass, 'icon' => null, 'text' => 'Sarg ' . strtolower(getConditionLabel('sargassum', $sargassum))];
        }
        if ($surf) {
            $cls = getConditionDotClass($surf);
            $kvClass = $cls === 'green' ? 'ok' : ($cls === 'red' ? 'warn' : ($cls === 'yellow' ? 'sun' : ''));
            $kvPills[] = ['class' => $kvClass, 'icon' => null, 'text' => 'Surf ' . strtolower(getConditionLabel('surf', $surf))];
        }
        if ($wind) {
            $cls = getConditionDotClass($wind);
            $kvClass = $cls === 'green' ? 'ok' : ($cls === 'red' ? 'warn' : ($cls === 'yellow' ? 'sun' : ''));
            $kvPills[] = ['class' => $kvClass, 'icon' => null, 'text' => 'Wind ' . strtolower(getConditionLabel('wind', $wind))];
        }
        ?>
        <?php if (!empty($kvPills)): ?>
        <div class="discovery-card__kv" aria-label="<?= h(__('beach.amenities')) ?>">
            <?php foreach ($kvPills as $p): ?>
            <span class="discovery-card__kv-pill<?= $p['class'] ? ' discovery-card__kv-pill--' . h($p['class']) : '' ?>">
                <?php if (!empty($p['icon'])): ?><i data-lucide="<?= h($p['icon']) ?>" class="w-3.5 h-3.5" aria-hidden="true"></i><?php endif; ?>
                <?= h($p['text']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="discovery-card__actions">
            <button type="button" class="discovery-action discovery-action--save"
                    data-action="toggleFavorite" data-action-args='["<?= h($beach['id'] ?? '') ?>"]'
                    aria-label="<?= h(__('beach.plan_save')) ?>">
                <i data-lucide="heart" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('beach.plan_save')) ?></span>
            </button>
            <button type="button" class="discovery-action"
                    data-action="addBeachToItinerary"
                    data-action-args='["<?= h($beach['id'] ?? '') ?>","<?= h(addslashes($name)) ?>"]'
                    aria-label="<?= h(__('beach.plan_add_to_day')) ?>">
                <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('beach.plan_add_to_day')) ?></span>
            </button>
        </div>
    </div>
</article>
