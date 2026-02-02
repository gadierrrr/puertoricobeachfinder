<?php
/**
 * Crowd Level Functions
 * Aggregates check-in data to determine current crowd levels
 */

require_once __DIR__ . '/db.php';

/**
 * Get current crowd level for a beach based on recent check-ins
 *
 * @param string $beachId Beach ID
 * @param int $hoursBack How many hours back to consider (default 4)
 * @return array|null Crowd data or null if no recent data
 */
function getBeachCrowdLevel(string $beachId, int $hoursBack = 4): ?array {
    $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));

    $checkins = query(
        'SELECT crowd_level, created_at FROM beach_checkins
         WHERE beach_id = :beach_id AND created_at >= :cutoff AND crowd_level IS NOT NULL
         ORDER BY created_at DESC',
        [':beach_id' => $beachId, ':cutoff' => $cutoffTime]
    );

    if (empty($checkins)) {
        return null;
    }

    // Weight more recent check-ins higher
    $weights = ['empty' => 1, 'light' => 2, 'moderate' => 3, 'busy' => 4, 'packed' => 5];
    $totalWeight = 0;
    $weightedSum = 0;
    $count = count($checkins);

    foreach ($checkins as $index => $checkin) {
        $level = strtolower($checkin['crowd_level']);
        if (!isset($weights[$level])) continue;

        // More recent = higher weight (1.0 to 0.5)
        $recencyWeight = 1 - ($index / ($count * 2));
        $weightedSum += $weights[$level] * $recencyWeight;
        $totalWeight += $recencyWeight;
    }

    if ($totalWeight === 0) return null;

    $avgScore = $weightedSum / $totalWeight;

    // Map score back to level
    $level = 'unknown';
    $label = 'Unknown';
    $color = 'gray';

    if ($avgScore <= 1.5) {
        $level = 'empty';
        $label = 'Empty';
        $color = 'green';
    } elseif ($avgScore <= 2.5) {
        $level = 'light';
        $label = 'Not Busy';
        $color = 'green';
    } elseif ($avgScore <= 3.5) {
        $level = 'moderate';
        $label = 'Moderate';
        $color = 'yellow';
    } elseif ($avgScore <= 4.5) {
        $level = 'busy';
        $label = 'Busy';
        $color = 'orange';
    } else {
        $level = 'packed';
        $label = 'Very Busy';
        $color = 'red';
    }

    $latestCheckin = $checkins[0];
    $minutesAgo = round((time() - strtotime($latestCheckin['created_at'])) / 60);

    return [
        'level' => $level,
        'label' => $label,
        'color' => $color,
        'score' => round($avgScore, 1),
        'checkin_count' => $count,
        'latest_checkin' => $latestCheckin['created_at'],
        'minutes_ago' => $minutesAgo,
        'time_label' => formatTimeAgo($minutesAgo)
    ];
}

/**
 * Get crowd levels for multiple beaches efficiently
 *
 * @param array $beachIds Array of beach IDs
 * @param int $hoursBack How many hours back to consider
 * @return array Map of beach_id => crowd data
 */
function getBatchCrowdLevels(array $beachIds, int $hoursBack = 4): array {
    if (empty($beachIds)) return [];

    $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));

    $params = $beachIds;
    $params[] = $cutoffTime;

    $checkins = query(
        "SELECT beach_id, crowd_level, created_at FROM beach_checkins
         WHERE beach_id IN ({$placeholders}) AND created_at >= ? AND crowd_level IS NOT NULL
         ORDER BY beach_id, created_at DESC",
        $params
    );

    // Group by beach
    $grouped = [];
    foreach ($checkins as $checkin) {
        $grouped[$checkin['beach_id']][] = $checkin;
    }

    // Calculate crowd level for each beach
    $results = [];
    foreach ($beachIds as $beachId) {
        if (!isset($grouped[$beachId])) {
            $results[$beachId] = null;
            continue;
        }

        $beachCheckins = $grouped[$beachId];
        $weights = ['empty' => 1, 'light' => 2, 'moderate' => 3, 'busy' => 4, 'packed' => 5];
        $totalWeight = 0;
        $weightedSum = 0;
        $count = count($beachCheckins);

        foreach ($beachCheckins as $index => $checkin) {
            $level = strtolower($checkin['crowd_level']);
            if (!isset($weights[$level])) continue;

            $recencyWeight = 1 - ($index / ($count * 2));
            $weightedSum += $weights[$level] * $recencyWeight;
            $totalWeight += $recencyWeight;
        }

        if ($totalWeight === 0) {
            $results[$beachId] = null;
            continue;
        }

        $avgScore = $weightedSum / $totalWeight;

        $level = 'unknown';
        $label = 'Unknown';
        $color = 'gray';

        if ($avgScore <= 1.5) {
            $level = 'empty';
            $label = 'Empty';
            $color = 'green';
        } elseif ($avgScore <= 2.5) {
            $level = 'light';
            $label = 'Not Busy';
            $color = 'green';
        } elseif ($avgScore <= 3.5) {
            $level = 'moderate';
            $label = 'Moderate';
            $color = 'yellow';
        } elseif ($avgScore <= 4.5) {
            $level = 'busy';
            $label = 'Busy';
            $color = 'orange';
        } else {
            $level = 'packed';
            $label = 'Very Busy';
            $color = 'red';
        }

        $latestCheckin = $beachCheckins[0];
        $minutesAgo = round((time() - strtotime($latestCheckin['created_at'])) / 60);

        $results[$beachId] = [
            'level' => $level,
            'label' => $label,
            'color' => $color,
            'score' => round($avgScore, 1),
            'checkin_count' => $count,
            'latest_checkin' => $latestCheckin['created_at'],
            'minutes_ago' => $minutesAgo,
            'time_label' => formatTimeAgo($minutesAgo)
        ];
    }

    return $results;
}

/**
 * Get crowd patterns for a beach (busy times by day/hour)
 *
 * @param string $beachId Beach ID
 * @param int $daysBack How many days of data to analyze
 * @return array Pattern data
 */
function getCrowdPatterns(string $beachId, int $daysBack = 30): array {
    $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$daysBack} days"));

    $checkins = query(
        "SELECT crowd_level, created_at FROM beach_checkins
         WHERE beach_id = :beach_id AND created_at >= :cutoff AND crowd_level IS NOT NULL",
        [':beach_id' => $beachId, ':cutoff' => $cutoffTime]
    );

    if (count($checkins) < 5) {
        return ['has_data' => false];
    }

    $weights = ['empty' => 1, 'light' => 2, 'moderate' => 3, 'busy' => 4, 'packed' => 5];

    // Group by day of week and hour
    $byDay = [];
    $byHour = [];

    foreach ($checkins as $checkin) {
        $level = strtolower($checkin['crowd_level']);
        if (!isset($weights[$level])) continue;

        $timestamp = strtotime($checkin['created_at']);
        $dayOfWeek = date('N', $timestamp); // 1=Monday, 7=Sunday
        $hour = (int)date('G', $timestamp);

        $byDay[$dayOfWeek][] = $weights[$level];
        $byHour[$hour][] = $weights[$level];
    }

    // Calculate averages
    $dayAverages = [];
    $dayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    for ($d = 1; $d <= 7; $d++) {
        if (isset($byDay[$d]) && count($byDay[$d]) > 0) {
            $dayAverages[$dayNames[$d]] = round(array_sum($byDay[$d]) / count($byDay[$d]), 1);
        }
    }

    $hourAverages = [];
    for ($h = 6; $h <= 20; $h++) { // 6 AM to 8 PM
        if (isset($byHour[$h]) && count($byHour[$h]) > 0) {
            $hourAverages[$h] = round(array_sum($byHour[$h]) / count($byHour[$h]), 1);
        }
    }

    // Find busiest/quietest times
    $busiestDay = !empty($dayAverages) ? array_keys($dayAverages, max($dayAverages))[0] : null;
    $quietestDay = !empty($dayAverages) ? array_keys($dayAverages, min($dayAverages))[0] : null;
    $busiestHour = !empty($hourAverages) ? array_keys($hourAverages, max($hourAverages))[0] : null;
    $quietestHour = !empty($hourAverages) ? array_keys($hourAverages, min($hourAverages))[0] : null;

    return [
        'has_data' => true,
        'total_checkins' => count($checkins),
        'days' => $dayAverages,
        'hours' => $hourAverages,
        'busiest_day' => $busiestDay,
        'quietest_day' => $quietestDay,
        'busiest_hour' => $busiestHour ? formatHour($busiestHour) : null,
        'quietest_hour' => $quietestHour ? formatHour($quietestHour) : null
    ];
}

/**
 * Format minutes ago to human-readable string
 */
function formatTimeAgo(int $minutes): string {
    if ($minutes < 1) return 'Just now';
    if ($minutes < 60) return $minutes . 'm ago';

    $hours = floor($minutes / 60);
    if ($hours < 24) return $hours . 'h ago';

    $days = floor($hours / 24);
    return $days . 'd ago';
}

/**
 * Format hour to readable time
 */
function formatHour(int $hour): string {
    if ($hour === 0) return '12 AM';
    if ($hour === 12) return '12 PM';
    if ($hour < 12) return $hour . ' AM';
    return ($hour - 12) . ' PM';
}

/**
 * Get crowd level badge HTML
 */
function getCrowdBadgeHtml(?array $crowdData, bool $compact = false): string {
    if (!$crowdData) return '';

    $colors = [
        'green' => 'bg-green-100 text-green-700 border-green-200',
        'yellow' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
        'orange' => 'bg-orange-100 text-orange-700 border-orange-200',
        'red' => 'bg-red-100 text-red-700 border-red-200',
        'gray' => 'bg-gray-100 text-gray-600 border-gray-200'
    ];

    $icons = [
        'empty' => '👤',
        'light' => '👥',
        'moderate' => '👥',
        'busy' => '👥👥',
        'packed' => '🔥'
    ];

    $colorClass = $colors[$crowdData['color']] ?? $colors['gray'];
    $icon = $icons[$crowdData['level']] ?? '👥';

    if ($compact) {
        return sprintf(
            '<span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border %s" title="%s • %s">
                <span>%s</span>
                <span class="font-medium">%s</span>
            </span>',
            $colorClass,
            $crowdData['label'],
            $crowdData['time_label'],
            $icon,
            $crowdData['label']
        );
    }

    return sprintf(
        '<div class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-lg border %s">
            <span>%s</span>
            <span class="font-medium">%s</span>
            <span class="text-xs opacity-75">• %s</span>
        </div>',
        $colorClass,
        $icon,
        $crowdData['label'],
        $crowdData['time_label']
    );
}
