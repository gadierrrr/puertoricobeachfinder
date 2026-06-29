<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Beach Detail: Horizontal facts row (wireframe)
 * Single wrapping flex row of icon + label facts. Only renders facts with data.
 *
 * Expects: $beach, $lang, $sjuDriveMinutes (optional — computed here if missing)
 */

if (!isset($sjuDriveMinutes)) {
    $sjuDriveMinutes = null;
    if (!empty($beach['lat']) && !empty($beach['lng']) && function_exists('calculateDistance')) {
        $meters = calculateDistance(18.4394, -66.0018, (float)$beach['lat'], (float)$beach['lng']);
        $km = ($meters / 1000) * 1.3;
        $sjuDriveMinutes = max(5, (int) round(($km / 72) * 60 / 5) * 5);
    }
}

$amenities = $beach['amenities'] ?? [];
$hasAmenity = function($keys) use ($amenities) {
    foreach ((array)$keys as $k) {
        if (in_array($k, $amenities, true)) return true;
    }
    return false;
};

$facts = [];

if ($sjuDriveMinutes) {
    $h = intdiv($sjuDriveMinutes, 60);
    $m = $sjuDriveMinutes % 60;
    $driveLabel = $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%d min', $m);
    $facts[] = ['icon' => 'car', 'label' => ($lang === 'es' ? "$driveLabel desde SJU" : "$driveLabel from SJU")];
}

if (!empty($beach['parking_details'])) {
    $_parkRaw = ($lang === 'es' && !empty($beach['parking_details_es'])) ? $beach['parking_details_es'] : $beach['parking_details'];
    $_parkClean = strip_tags($_parkRaw);
    $_parkShort = preg_split('/[.,;\n]/', $_parkClean, 2)[0];
    $_parkShort = trim(mb_substr($_parkShort, 0, 30));
    $facts[] = ['icon' => 'square-parking', 'label' => $_parkShort];
} elseif ($hasAmenity(['parking_free', 'parking_paid', 'parking'])) {
    $facts[] = ['icon' => 'square-parking', 'label' => $lang === 'es' ? 'Estacionamiento' : 'Parking'];
}

if ($hasAmenity(['restrooms', 'bathrooms'])) {
    $facts[] = ['icon' => 'door-open', 'label' => $lang === 'es' ? 'Baños' : 'Restrooms'];
}

if ($hasAmenity(['lifeguard', 'lifeguards'])) {
    $facts[] = ['icon' => 'life-buoy', 'label' => $lang === 'es' ? 'Salvavidas' : 'Lifeguard'];
}

if ($hasAmenity(['food', 'kiosko', 'kioskos', 'restaurant'])) {
    $facts[] = ['icon' => 'utensils', 'label' => $lang === 'es' ? 'Kioskos' : 'Kioskos'];
}

if ($hasAmenity(['shade', 'palm_shade', 'trees'])) {
    $facts[] = ['icon' => 'umbrella', 'label' => $lang === 'es' ? 'Sombra' : 'Shade'];
}

if (!empty($beach['access_label'])) {
    $facts[] = ['icon' => 'footprints', 'label' => h($beach['access_label'])];
}
?>
<?php if (!empty($facts)): ?>
<section class="facts-row" aria-label="<?= h($lang === 'es' ? 'Datos rápidos' : 'Quick facts') ?>">
    <?php foreach ($facts as $f): ?>
    <div class="fact">
        <i data-lucide="<?= h($f['icon']) ?>" class="fact-ico" aria-hidden="true"></i>
        <span class="fact-label"><?= h($f['label']) ?></span>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
