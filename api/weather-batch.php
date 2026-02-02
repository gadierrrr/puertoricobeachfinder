<?php
/**
 * Batch Weather API
 * Fetches weather for multiple beach locations asynchronously
 *
 * GET /api/weather-batch.php?beaches=id1,id2,id3
 * Returns JSON with weather data keyed by beach ID
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/weather.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Get beach IDs from request
$beachIdsParam = $_GET['beaches'] ?? '';
if (empty($beachIdsParam)) {
    echo json_encode(['error' => 'No beach IDs provided']);
    exit;
}

$beachIds = array_filter(array_map('trim', explode(',', $beachIdsParam)));
if (empty($beachIds)) {
    echo json_encode(['error' => 'Invalid beach IDs']);
    exit;
}

// Limit to prevent abuse
$beachIds = array_slice($beachIds, 0, 30);

// Fetch beach coordinates
$placeholders = implode(',', array_fill(0, count($beachIds), '?'));
$beaches = query("
    SELECT id, lat, lng FROM beaches
    WHERE id IN ($placeholders)
", $beachIds);

if (empty($beaches)) {
    echo json_encode([]);
    exit;
}

// Batch fetch weather (uses caching internally)
$weatherMap = getBatchWeatherForBeaches($beaches, 15);

// Simplify the response for the frontend (only send what's needed for cards)
$response = [];
foreach ($weatherMap as $beachId => $weather) {
    if ($weather && isset($weather['current'])) {
        $response[$beachId] = [
            'temp' => round($weather['current']['temperature'] ?? 0),
            'icon' => $weather['current']['icon'] ?? '🌤️',
            'description' => $weather['current']['description'] ?? 'Weather'
        ];
    }
}

echo json_encode($response);
