<?php
/**
 * API: Get Weather for Beach
 *
 * GET /api/weather.php?beach_id=xxx
 * GET /api/weather.php?lat=18.5&lng=-66.1
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/weather.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=900'); // 15 min cache

$beachId = $_GET['beach_id'] ?? null;
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

// Get coordinates from beach ID if provided
if ($beachId) {
    $beach = queryOne('SELECT lat, lng FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['success' => false, 'error' => 'Beach not found'], 404);
    }
    $lat = (float)$beach['lat'];
    $lng = (float)$beach['lng'];
}

// Validate coordinates
if (!$lat || !$lng) {
    jsonResponse(['success' => false, 'error' => 'Missing coordinates'], 400);
}

// Puerto Rico bounds check
if ($lat < 17.5 || $lat > 18.6 || $lng < -68 || $lng > -65) {
    jsonResponse(['success' => false, 'error' => 'Coordinates outside Puerto Rico'], 400);
}

// Fetch weather
$weather = getWeatherForLocation($lat, $lng);

if (!$weather) {
    jsonResponse(['success' => false, 'error' => 'Weather service unavailable'], 503);
}

// Add recommendation
$weather['recommendation'] = getBeachRecommendation($weather);

jsonResponse([
    'success' => true,
    'data' => $weather
]);
