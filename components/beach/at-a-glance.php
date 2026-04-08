<?php
/**
 * At a Glance — AI-optimized summary block for beach detail pages.
 * Auto-generated from existing beach data. Renders a concise summary
 * paragraph (for AI Overview extraction) + structured activity grid.
 *
 * Required: $beach (array with tags, amenities, etc.), $lang (string)
 */

// Build cells array — only include cells with data
$glanceCells = [];

// 1. Swimming
$swimDiff = (int) ($beach['swim_difficulty'] ?? 3);
$hasCalmTag = in_array('calm-waters', $beach['tags'] ?? [], true);
if ($swimDiff <= 1) {
    $swimLabel = __('glance.excellent');
    $swimDetail = $hasCalmTag ? __('glance.calm_waters') : __('glance.easy_conditions');
} elseif ($swimDiff === 2) {
    $swimLabel = __('glance.good');
    $swimDetail = $hasCalmTag ? __('glance.calm_waters') : __('glance.gentle_waves');
} elseif ($swimDiff === 3) {
    $swimLabel = __('glance.moderate');
    $swimDetail = __('glance.check_conditions');
} else {
    $swimLabel = __('glance.challenging');
    $swimDetail = __('glance.experienced_swimmers');
}
$glanceCells[] = [
    'icon' => '🏊',
    'label' => __('glance.swimming'),
    'value' => $swimLabel . ' — ' . $swimDetail,
];

// 2. Snorkeling (only if tag exists)
$hasSnorkelTag = in_array('snorkeling', $beach['tags'] ?? [], true);
if ($hasSnorkelTag) {
    $desc = strtolower($beach['description'] ?? '');
    $hasReef = (str_contains($desc, 'reef') || str_contains($desc, 'coral'));
    $snorkelValue = $hasReef
        ? __('glance.great') . ' — ' . __('glance.reef_access')
        : __('glance.good') . ' — ' . __('glance.clear_water');
    $glanceCells[] = [
        'icon' => '🤿',
        'label' => __('glance.snorkeling'),
        'value' => $snorkelValue,
    ];
}

// 3. Surfing (only if tag exists)
$hasSurfTag = in_array('surfing', $beach['tags'] ?? [], true);
if ($hasSurfTag) {
    $glanceCells[] = [
        'icon' => '🏄',
        'label' => __('glance.surfing'),
        'value' => __('glance.good') . ' — ' . __('glance.check_conditions'),
    ];
}

// 4. Family-Friendly
$isFamilyFriendly = !empty($beach['safe_for_children']) || in_array('family-friendly', $beach['tags'] ?? [], true);
if ($isFamilyFriendly) {
    $hasLifeguard = !empty($beach['has_lifeguard']);
    $familyDetail = $hasLifeguard ? __('glance.lifeguard_on_duty') : __('glance.safe_for_kids');
    // Check features for roped area
    foreach ($beach['features'] ?? [] as $f) {
        $fTitle = strtolower($f['title'] ?? '');
        if (str_contains($fTitle, 'roped') || str_contains($fTitle, 'rope')) {
            $familyDetail = __('glance.roped_swim_area');
            break;
        }
    }
    $glanceCells[] = [
        'icon' => '👨‍👩‍👧‍👦',
        'label' => __('glance.family_friendly'),
        'value' => __('glance.yes') . ' — ' . $familyDetail,
    ];
}

// 5. Parking
$parkingDetails = trim($lang === 'es'
    ? ($beach['parking_details_es'] ?? $beach['parking_details'] ?? '')
    : ($beach['parking_details'] ?? ''));
$hasParkingAmenity = in_array('parking', $beach['amenities'] ?? [], true)
    || in_array('free-parking', $beach['amenities'] ?? [], true);
if ($parkingDetails !== '' || $hasParkingAmenity) {
    if ($parkingDetails !== '') {
        // Extract first phrase (up to first period or 40 chars)
        $dotPos = strpos($parkingDetails, '.');
        $parkingShort = ($dotPos !== false && $dotPos < 60)
            ? substr($parkingDetails, 0, $dotPos)
            : mb_substr($parkingDetails, 0, 40) . '…';
    } else {
        $parkingShort = __('glance.available');
    }
    $glanceCells[] = [
        'icon' => '🅿️',
        'label' => __('glance.parking'),
        'value' => $parkingShort,
    ];
}

// 6. Food
$hasFood = in_array('food', $beach['amenities'] ?? [], true);
if ($hasFood) {
    // Try to extract restaurant name from description/features
    $foodValue = __('glance.available_nearby');
    $desc = $beach['description'] ?? '';
    if (preg_match('/restaurant\s+(?:called\s+|named\s+)?([A-Z][a-záéíóúñ\s]+)/i', $desc, $m)) {
        $foodValue = trim($m[1]);
    } elseif (str_contains(strtolower($desc), 'beachfront restaurant')) {
        $foodValue = __('glance.beachfront_restaurant');
    } elseif (str_contains(strtolower($desc), 'restaurant')) {
        $foodValue = __('glance.restaurant_on_site');
    } elseif (str_contains(strtolower($desc), 'kiosk') || str_contains(strtolower($desc), 'food')) {
        $foodValue = __('glance.food_kiosks');
    }
    $glanceCells[] = [
        'icon' => '🍽️',
        'label' => __('glance.food'),
        'value' => $foodValue,
    ];
}

// 7. Best Time
$bestTime = trim($lang === 'es'
    ? ($beach['best_time_es'] ?? $beach['best_time'] ?? '')
    : ($beach['best_time'] ?? ''));
if ($bestTime !== '') {
    // Extract first sentence or date range
    $dotPos = strpos($bestTime, '.');
    $bestTimeShort = ($dotPos !== false && $dotPos < 50)
        ? substr($bestTime, 0, $dotPos)
        : mb_substr($bestTime, 0, 45) . '…';
    // Try to extract month range pattern
    if (preg_match('/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s*(?:–|-|to)\s*(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*)/i', $bestTime, $m)) {
        $bestTimeShort = $m[1];
    }
    $glanceCells[] = [
        'icon' => '📅',
        'label' => __('glance.best_time'),
        'value' => $bestTimeShort,
    ];
}

// Only render if we have enough data
if (count($glanceCells) < 2) {
    return;
}

// Generate summary paragraph
$summaryText = generateAtAGlanceSummary($beach, $lang);
?>

<!-- At a Glance — AI-extractable summary -->
<div class="at-a-glance relative rounded-xl border border-warm-200 overflow-hidden bg-white shadow-card">
    <!-- Gold accent bar -->
    <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-ocean-500 to-ocean-600 rounded-l-xl"></div>

    <div class="pl-5 pr-5 py-5 sm:pl-6 sm:pr-6">
        <!-- Header -->
        <h2 class="flex items-center gap-2 text-sunset-400 font-bold text-base mb-3">
            <svg style="width:18px;height:18px;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01"/>
            </svg>
            <?= h(__('glance.title')) ?>
        </h2>

        <!-- Summary paragraph (AI-extractable) -->
        <p class="at-a-glance-summary text-[15px] leading-relaxed text-warm-700 mb-4">
            <?= h($summaryText) ?>
        </p>

        <!-- Activity/amenity grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <?php foreach ($glanceCells as $cell): ?>
            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg" style="background: rgba(245,240,235,0.6);">
                <span class="text-lg flex-shrink-0"><?= $cell['icon'] ?></span>
                <div class="min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-warm-500"><?= h($cell['label']) ?></div>
                    <div class="text-[13px] font-semibold text-warm-700 truncate"><?= h($cell['value']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
