<?php
/**
 * API: Get rendered Weather Widget HTML
 *
 * GET /api/weather-widget.php?lat=18.5&lng=-66.1&size=sidebar
 * Returns pre-rendered HTML for the weather widget component.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/weather.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=900'); // 15 min cache

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$size = $_GET['size'] ?? 'sidebar';

// Validate coordinates
if (!$lat || !$lng) {
    http_response_code(400);
    echo '<div class="text-sm text-gray-400">Weather unavailable</div>';
    exit;
}

// Puerto Rico bounds check
if ($lat < 17.5 || $lat > 18.6 || $lng < -68 || $lng > -65) {
    http_response_code(400);
    echo '<div class="text-sm text-gray-400">Weather unavailable</div>';
    exit;
}

// Whitelist allowed sizes
$allowedSizes = ['compact', 'sidebar', 'medium', 'full'];
if (!in_array($size, $allowedSizes, true)) {
    $size = 'sidebar';
}

// Fetch weather
$weather = getWeatherForLocation($lat, $lng);

if (!$weather) {
    echo '<div class="text-sm text-gray-400">Weather unavailable</div>';
    exit;
}

// Render the widget component
include APP_ROOT . '/components/weather-widget.php';
