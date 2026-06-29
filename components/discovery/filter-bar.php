<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Discovery: Filter Bar (wireframe 1:1)
 *
 * Two rows:
 *   1. Pill-dropdowns: Region · Vibe · Amenities · Conditions · Drive from SJU
 *   2. Active chips (Family / Snorkel / Free parking / Shade / Kiosko) + Clear
 *
 * Expects: $selectedTags, $selectedMunicipality, $amenities, $searchQuery,
 *          $hasLifeguard, $maxDistance, $lang
 */

$selectedTags = $selectedTags ?? [];
$selectedMunicipality = $selectedMunicipality ?? '';
$amenities = $amenities ?? [];
$hasLifeguard = $hasLifeguard ?? false;
$maxDistance = $maxDistance ?? 30;
$lang = $lang ?? 'en';

// PR regions with matching municipalities — cosmetic grouping for the dropdown.
$regions = [
    'norte'    => ['Arecibo','Barceloneta','Camuy','Dorado','Hatillo','Isabela','Manatí','Quebradillas','Toa Baja','Vega Alta','Vega Baja','San Juan','Carolina','Loíza'],
    'este'     => ['Fajardo','Ceiba','Naguabo','Humacao','Yabucoa','Río Grande','Luquillo','Maunabo'],
    'sur'      => ['Arroyo','Cabo Rojo','Guánica','Guayama','Guayanilla','Juana Díaz','Lajas','Patillas','Peñuelas','Ponce','Salinas','Santa Isabel','Yauco'],
    'oeste'    => ['Aguada','Aguadilla','Añasco','Cabo Rojo','Hormigueros','Mayagüez','Moca','Rincón','San Germán'],
    'culebra'  => ['Culebra'],
    'vieques'  => ['Vieques'],
];

$vibeTags = ['family-friendly','surfing','snorkeling','swimming','secluded','scenic','calm-waters','popular','fishing','diving','camping'];

$amenityOptions = [
    'parking'          => 'car',
    'restrooms'        => 'door-open',
    'lifeguard'        => 'life-buoy',
    'food'             => 'utensils',
    'shade-structures' => 'umbrella',
    'showers'          => 'shower-head',
    'picnic-areas'     => 'trees',
    'water-sports'     => 'waves',
    'equipment-rental' => 'sailboat',
    'accessibility'    => 'accessibility',
];

$activeChips = [];
foreach ($selectedTags as $tag) {
    $activeChips[] = ['key' => 'tag', 'value' => $tag, 'label' => function_exists('getTagLabel') ? getTagLabel($tag) : $tag];
}
foreach ($amenities as $a) {
    if (!$a) continue;
    $activeChips[] = ['key' => 'amenity', 'value' => $a, 'label' => function_exists('getAmenityLabel') ? getAmenityLabel($a) : $a];
}
if ($hasLifeguard && !in_array('lifeguard', $amenities, true)) {
    $activeChips[] = ['key' => 'lifeguard', 'value' => '1', 'label' => __('filters.lifeguard') !== 'filters.lifeguard' ? __('filters.lifeguard') : 'Lifeguard'];
}
if ($selectedMunicipality) {
    $activeChips[] = ['key' => 'municipality', 'value' => $selectedMunicipality, 'label' => $selectedMunicipality];
}
?>
<section class="discovery-filter-bar" aria-label="<?= h(__('filters.refine')) ?>">
    <div class="discovery-filter-bar__pills">

        <details class="discovery-dropdown">
            <summary class="discovery-dropdown__trigger">
                <span>Region</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </summary>
            <div class="discovery-dropdown__menu">
                <?php foreach ($regions as $slug => $munis): ?>
                <div class="discovery-dropdown__group">
                    <div class="discovery-dropdown__group-title"><?= h(__('beach.region_' . $slug)) ?></div>
                    <?php foreach ($munis as $m): ?>
                    <a href="?municipality=<?= h($m) ?>" class="discovery-dropdown__item<?= $selectedMunicipality === $m ? ' is-active' : '' ?>">
                        <?= h($m) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="discovery-dropdown">
            <summary class="discovery-dropdown__trigger">
                <span><?= h(__('beach.vibe')) ?></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </summary>
            <div class="discovery-dropdown__menu discovery-dropdown__menu--single">
                <?php foreach ($vibeTags as $t): $isOn = in_array($t, $selectedTags, true); ?>
                <button type="button" data-action="toggleTag" data-action-args='["<?= h($t) ?>"]' class="discovery-dropdown__item<?= $isOn ? ' is-active' : '' ?>">
                    <?= h(function_exists('getTagLabel') ? getTagLabel($t) : $t) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="discovery-dropdown">
            <summary class="discovery-dropdown__trigger">
                <span><?= h(__('beach.amenities')) ?></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </summary>
            <div class="discovery-dropdown__menu discovery-dropdown__menu--single">
                <?php foreach ($amenityOptions as $a => $icon): $isOn = in_array($a, $amenities, true); ?>
                <label class="discovery-dropdown__item<?= $isOn ? ' is-active' : '' ?>">
                    <input type="checkbox" name="amenities[]" value="<?= h($a) ?>" <?= $isOn ? 'checked' : '' ?> class="discovery-dropdown__checkbox">
                    <i data-lucide="<?= h($icon) ?>" class="discovery-dropdown__item-icon" aria-hidden="true"></i>
                    <span><?= h(function_exists('getAmenityLabel') ? getAmenityLabel($a) : $a) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="discovery-dropdown">
            <summary class="discovery-dropdown__trigger">
                <span><?= h(__('beach.conditions')) ?></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </summary>
            <div class="discovery-dropdown__menu discovery-dropdown__menu--single">
                <?php foreach (['sargassum_clear' => 'Sargassum clear', 'low_surf' => 'Calm surf', 'low_wind' => 'Light wind'] as $v => $label): ?>
                <a href="?condition=<?= h($v) ?>" class="discovery-dropdown__item">
                    <?= h($label) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </details>

        <details class="discovery-dropdown discovery-dropdown--drive">
            <summary class="discovery-dropdown__trigger">
                <span><?= h(__('beach.drive_from_filter', ['minutes' => $maxDistance])) ?></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </summary>
            <div class="discovery-dropdown__menu discovery-dropdown__menu--single">
                <?php foreach ([15, 30, 45, 60, 90] as $m): ?>
                <button type="button" data-action="setMaxDistance" data-action-args='[<?= $m ?>]' class="discovery-dropdown__item<?= (int)$maxDistance === $m ? ' is-active' : '' ?>">
                    <?= $m ?> min
                </button>
                <?php endforeach; ?>
            </div>
        </details>
    </div>

    <?php if (!empty($activeChips)): ?>
    <div class="discovery-filter-bar__chips" id="applied-filters">
        <?php foreach ($activeChips as $chip): ?>
        <span class="discovery-chip discovery-chip--active">
            <?= h($chip['label']) ?>
            <button type="button" aria-label="<?= h(__('filters.remove_filter')) ?>"
                    data-action="removeFilter" data-action-args='["<?= h($chip['key']) ?>","<?= h($chip['value']) ?>"]'
                    class="discovery-chip__remove">✕</button>
        </span>
        <?php endforeach; ?>
        <button type="button" data-action="clearFilters" class="discovery-chip discovery-chip--clear discovery-filter-bar__clear">
            <?= h(__('filters.clear_filters')) ?> ✕
        </button>
    </div>
    <?php endif; ?>
</section>
