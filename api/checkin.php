<?php
/**
 * Beach Check-In API
 *
 * POST: Submit a new check-in
 * GET: Get recent check-ins for a beach
 */

require_once __DIR__ . '/../inc/session.php';
session_start();
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Submit a check-in
    submitCheckin();
} elseif ($method === 'GET') {
    // Get check-ins for a beach
    getCheckins();
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function submitCheckin() {
    // Require authentication
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in to check in'], 401);
    }

    // Validate CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $beachId = $_POST['beach_id'] ?? '';
    $crowdLevel = $_POST['crowd_level'] ?? null;
    $parkingStatus = $_POST['parking_status'] ?? null;
    $waterCondition = $_POST['water_condition'] ?? null;
    $sargassumLevel = $_POST['sargassum_level'] ?? null;
    $weatherActual = $_POST['weather_actual'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $userId = $_SESSION['user_id'];

    // Validate beach exists
    $beach = queryOne('SELECT id, name FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // Validate at least one condition is provided
    if (!$crowdLevel && !$parkingStatus && !$waterCondition && !$sargassumLevel && !$weatherActual && !$notes) {
        jsonResponse(['error' => 'Please provide at least one condition update'], 400);
    }

    // Validate enum values
    $validCrowdLevels = ['empty', 'light', 'moderate', 'busy', 'packed'];
    $validParkingStatus = ['plenty', 'available', 'limited', 'full'];
    $validWaterConditions = ['calm', 'small-waves', 'choppy', 'rough'];
    $validSargassumLevels = ['none', 'light', 'moderate', 'heavy'];

    if ($crowdLevel && !in_array($crowdLevel, $validCrowdLevels)) {
        $crowdLevel = null;
    }
    if ($parkingStatus && !in_array($parkingStatus, $validParkingStatus)) {
        $parkingStatus = null;
    }
    if ($waterCondition && !in_array($waterCondition, $validWaterConditions)) {
        $waterCondition = null;
    }
    if ($sargassumLevel && !in_array($sargassumLevel, $validSargassumLevels)) {
        $sargassumLevel = null;
    }

    // Rate limit: max 1 check-in per beach per hour per user
    $recentCheckin = queryOne("
        SELECT id FROM beach_checkins
        WHERE beach_id = :beach_id AND user_id = :user_id
        AND created_at > datetime('now', '-1 hour')
    ", [':beach_id' => $beachId, ':user_id' => $userId]);

    if ($recentCheckin) {
        jsonResponse(['error' => 'You can only check in once per hour at each beach'], 429);
    }

    // Insert check-in
    $result = execute("
        INSERT INTO beach_checkins (beach_id, user_id, crowd_level, parking_status, water_condition, sargassum_level, weather_actual, notes, created_at)
        VALUES (:beach_id, :user_id, :crowd_level, :parking_status, :water_condition, :sargassum_level, :weather_actual, :notes, datetime('now'))
    ", [
        ':beach_id' => $beachId,
        ':user_id' => $userId,
        ':crowd_level' => $crowdLevel,
        ':parking_status' => $parkingStatus,
        ':water_condition' => $waterCondition,
        ':sargassum_level' => $sargassumLevel,
        ':weather_actual' => $weatherActual,
        ':notes' => $notes ?: null
    ]);

    if ($result) {
        jsonResponse([
            'success' => true,
            'message' => 'Thanks for checking in! Your update helps other beachgoers.'
        ]);
    } else {
        jsonResponse(['error' => 'Failed to save check-in'], 500);
    }
}

function getCheckins() {
    $beachId = $_GET['beach_id'] ?? '';
    $limit = min(20, max(1, intval($_GET['limit'] ?? 5)));

    if (!$beachId) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    $checkins = query("
        SELECT
            c.id, c.crowd_level, c.parking_status, c.water_condition,
            c.sargassum_level, c.weather_actual, c.notes, c.created_at,
            u.name as user_name, u.avatar_url
        FROM beach_checkins c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.beach_id = :beach_id
        ORDER BY c.created_at DESC
        LIMIT :limit
    ", [':beach_id' => $beachId, ':limit' => $limit]);

    // Format for display
    foreach ($checkins as &$checkin) {
        $checkin['time_ago'] = timeAgo($checkin['created_at']);
        $checkin['crowd_label'] = getCheckinLabel('crowd', $checkin['crowd_level']);
        $checkin['parking_label'] = getCheckinLabel('parking', $checkin['parking_status']);
        $checkin['water_label'] = getCheckinLabel('water', $checkin['water_condition']);
        $checkin['sargassum_label'] = getCheckinLabel('sargassum', $checkin['sargassum_level']);
    }

    if (isHtmx()) {
        // Return HTML for HTMX
        header('Content-Type: text/html');
        if (empty($checkins)) {
            echo '<p class="text-sm text-gray-500 text-center py-4">No recent check-ins. Be the first to report!</p>';
            return;
        }

        foreach ($checkins as $checkin) {
            renderCheckinCard($checkin);
        }
        return;
    }

    jsonResponse($checkins);
}

function getCheckinLabel($type, $value) {
    if (!$value) return null;

    $labels = [
        'crowd' => [
            'empty' => ['label' => 'Empty', 'emoji' => '🏝️', 'color' => 'green'],
            'light' => ['label' => 'Light Crowd', 'emoji' => '👥', 'color' => 'green'],
            'moderate' => ['label' => 'Moderate', 'emoji' => '👥👥', 'color' => 'yellow'],
            'busy' => ['label' => 'Busy', 'emoji' => '👥👥👥', 'color' => 'orange'],
            'packed' => ['label' => 'Packed', 'emoji' => '🔥', 'color' => 'red']
        ],
        'parking' => [
            'plenty' => ['label' => 'Plenty Available', 'emoji' => '🅿️', 'color' => 'green'],
            'available' => ['label' => 'Spots Available', 'emoji' => '🅿️', 'color' => 'green'],
            'limited' => ['label' => 'Limited Parking', 'emoji' => '⚠️', 'color' => 'yellow'],
            'full' => ['label' => 'Parking Full', 'emoji' => '🚫', 'color' => 'red']
        ],
        'water' => [
            'calm' => ['label' => 'Calm Water', 'emoji' => '🌊', 'color' => 'green'],
            'small-waves' => ['label' => 'Small Waves', 'emoji' => '🌊', 'color' => 'blue'],
            'choppy' => ['label' => 'Choppy', 'emoji' => '🌊', 'color' => 'yellow'],
            'rough' => ['label' => 'Rough Seas', 'emoji' => '⚠️', 'color' => 'red']
        ],
        'sargassum' => [
            'none' => ['label' => 'No Sargassum', 'emoji' => '✓', 'color' => 'green'],
            'light' => ['label' => 'Light Sargassum', 'emoji' => '🌿', 'color' => 'yellow'],
            'moderate' => ['label' => 'Moderate', 'emoji' => '🌿🌿', 'color' => 'orange'],
            'heavy' => ['label' => 'Heavy Sargassum', 'emoji' => '🌿🌿🌿', 'color' => 'red']
        ]
    ];

    return $labels[$type][$value] ?? null;
}

function renderCheckinCard($checkin) {
    $avatar = $checkin['avatar_url'] ?: null;
    $name = $checkin['user_name'] ?: 'Beach Visitor';
    $initial = strtoupper(substr($name, 0, 1));
    ?>
    <div class="flex gap-3 py-3 border-b border-gray-100 last:border-0">
        <?php if ($avatar): ?>
        <img src="<?= h($avatar) ?>" alt="" class="w-8 h-8 rounded-full">
        <?php else: ?>
        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm flex-shrink-0">
            <?= $initial ?>
        </div>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-gray-900 text-sm"><?= h($name) ?></span>
                <span class="text-xs text-gray-400"><?= h($checkin['time_ago']) ?></span>
            </div>

            <div class="flex flex-wrap gap-2 mt-1.5">
                <?php if ($checkin['crowd_label']): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-<?= $checkin['crowd_label']['color'] ?>-100 text-<?= $checkin['crowd_label']['color'] ?>-700 px-2 py-0.5 rounded-full">
                    <span><?= $checkin['crowd_label']['emoji'] ?></span>
                    <span><?= h($checkin['crowd_label']['label']) ?></span>
                </span>
                <?php endif; ?>

                <?php if ($checkin['parking_label']): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-<?= $checkin['parking_label']['color'] ?>-100 text-<?= $checkin['parking_label']['color'] ?>-700 px-2 py-0.5 rounded-full">
                    <span><?= $checkin['parking_label']['emoji'] ?></span>
                    <span><?= h($checkin['parking_label']['label']) ?></span>
                </span>
                <?php endif; ?>

                <?php if ($checkin['water_label']): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-<?= $checkin['water_label']['color'] ?>-100 text-<?= $checkin['water_label']['color'] ?>-700 px-2 py-0.5 rounded-full">
                    <span><?= $checkin['water_label']['emoji'] ?></span>
                    <span><?= h($checkin['water_label']['label']) ?></span>
                </span>
                <?php endif; ?>

                <?php if ($checkin['sargassum_label']): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-<?= $checkin['sargassum_label']['color'] ?>-100 text-<?= $checkin['sargassum_label']['color'] ?>-700 px-2 py-0.5 rounded-full">
                    <span><?= $checkin['sargassum_label']['emoji'] ?></span>
                    <span><?= h($checkin['sargassum_label']['label']) ?></span>
                </span>
                <?php endif; ?>
            </div>

            <?php if ($checkin['notes']): ?>
            <p class="text-sm text-gray-600 mt-1.5"><?= h($checkin['notes']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
