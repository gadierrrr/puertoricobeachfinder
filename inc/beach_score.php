<?php
/**
 * Derived "Beach Score" for the redesign. The DB has no numeric score fields,
 * so we synthesise an overall score + sub‑metrics from existing signals
 * (rating, surf, swim difficulty, sargassum, access, lifeguard, kid‑safe, tags,
 * amenities). Values are 0–100. This is a heuristic — tune during polish.
 */
if (defined('BEACH_SCORE_INCLUDED')) { return; }
define('BEACH_SCORE_INCLUDED', true);

function bsClamp($v): int { return (int) max(0, min(100, round($v))); }

function bsColor(int $v): string { return $v >= 67 ? 'g' : ($v >= 40 ? 'a' : 'r'); }

/** True if any needle appears in the (lowercased) list. */
function bsHas(array $list, array $needles): bool {
    foreach ($list as $item) {
        foreach ($needles as $n) {
            if (str_contains((string) $item, $n)) { return true; }
        }
    }
    return false;
}

/**
 * @return array{overall:int, rating:float, access:int, bars:array<array{0:string,1:int,2:string}>}
 */
function computeBeachScore(array $beach, array $tags = [], array $amenities = []): array {
    $tags = array_map('strtolower', $tags);
    $am   = array_map('strtolower', $amenities);
    $surf = strtolower((string) ($beach['surf'] ?? ''));
    $swim = strtolower((string) ($beach['swim_difficulty'] ?? ''));
    $sarg = strtolower((string) ($beach['sargassum'] ?? ''));
    $access = strtolower((string) ($beach['access_label'] ?? ''));

    // rating (prefer user rating, else Google), 0–100
    $rating = (float) ($beach['avg_user_rating'] ?? 0);
    if ($rating <= 0) { $rating = (float) ($beach['google_rating'] ?? 0); }
    $ratingScore = $rating > 0 ? bsClamp($rating / 5 * 100) : 72;

    // calm water (surf: calm|small|medium|large; sargassum: none|light|moderate|high)
    $calm = 60;
    if ($surf === 'calm' || str_contains($surf, 'flat')) { $calm = 92; }
    elseif ($surf === 'small' || str_contains($surf, 'low')) { $calm = 80; }
    elseif ($surf === 'medium' || str_contains($surf, 'mod')) { $calm = 55; }
    elseif ($surf === 'large' || bsHas([$surf], ['big', 'high', 'strong'])) { $calm = 32; }
    if (str_contains($sarg, 'high')) { $calm -= 22; }
    elseif (str_contains($sarg, 'mod')) { $calm -= 12; }
    elseif (str_contains($sarg, 'light')) { $calm -= 5; }
    if (bsHas($tags, ['surf', 'surfing'])) { $calm = min($calm, 40); }
    $calm = bsClamp($calm);

    // snorkeling
    $snorkel = bsHas($tags, ['snorkel', 'reef', 'clear', 'dive', 'coral']) ? 88 : ($calm >= 75 ? 60 : 44);

    // seclusion / uncrowded
    $seclusion = bsHas($tags, ['secluded', 'hidden', 'wild', 'remote', 'quiet']) ? 88 : 55;
    if (bsHas([$access], ['boat', 'kayak', 'hike', 'trail', '4x4'])) { $seclusion = max($seclusion, 85); }
    if (bsHas($tags, ['balneario', 'popular', 'lively']) || bsHas($am, ['lifeguard'])) { $seclusion = min($seclusion, 48); }
    $seclusion = bsClamp($seclusion);

    // family
    $family = 58;
    if (!empty($beach['safe_for_children'])) { $family += 18; }
    if (!empty($beach['has_lifeguard'])) { $family += 14; }
    $family = bsClamp($family + ($calm - 60) / 4);

    // facilities
    $facCount = count(array_intersect($am, ['parking', 'restrooms', 'bathrooms', 'showers', 'food', 'lifeguard', 'gazebos', 'picnic', 'camping', 'kiosks']));
    $facilities = 18 + $facCount * 15 + (!empty($beach['has_lifeguard']) ? 12 : 0) + (str_contains($access, 'balneario') ? 22 : 0);
    if (bsHas([$access], ['boat', 'kayak'])) { $facilities = min($facilities, 24); }
    $facilities = bsClamp($facilities);

    // access ease
    $accessScore = 78;
    if (bsHas([$access], ['boat', 'kayak'])) { $accessScore = 26; }
    elseif (bsHas([$access], ['hike', 'trail', '4x4', 'walk'])) { $accessScore = 44; }
    elseif (bsHas([$access], ['parking', 'road', 'balneario', 'drive', 'easy', 'path', 'roadside'])) { $accessScore = 90; }
    $accessScore = bsClamp($accessScore);

    $overall = bsClamp(
        0.40 * $ratingScore + 0.15 * $calm + 0.12 * $snorkel +
        0.11 * $seclusion + 0.12 * $family + 0.10 * $facilities
    );

    return [
        'overall' => $overall,
        'rating'  => $rating,
        'access'  => $accessScore,
        'bars'    => [
            ['Calm water', $calm, bsColor($calm)],
            ['Snorkeling', $snorkel, bsColor($snorkel)],
            ['Seclusion', $seclusion, bsColor($seclusion)],
            ['Family', $family, bsColor($family)],
            ['Facilities', $facilities, bsColor($facilities)],
        ],
    ];
}
