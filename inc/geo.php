<?php
/**
 * Geolocation utilities
 * Haversine formula for calculating distances between coordinates
 */

// Include guard to prevent duplicate declarations
if (defined('GEO_PHP_INCLUDED')) {
    return;
}
define('GEO_PHP_INCLUDED', true);

// Ensure helpers.php is loaded for formatDistanceDisplay()
require_once __DIR__ . '/helpers.php';

/**
 * Calculate the distance between two points using the Haversine formula
 *
 * @param float $lat1 Latitude of point 1
 * @param float $lng1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lng2 Longitude of point 2
 * @return float Distance in meters
 */
function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371000; // Earth's radius in meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $R * $c;
}

// NOTE: formatDistanceDisplay() is defined in helpers.php to avoid duplicates

/**
 * Sort beaches by distance from a given point
 *
 * @param array $beaches Array of beaches with lat/lng
 * @param float $userLat User's latitude
 * @param float $userLng User's longitude
 * @return array Beaches sorted by distance with distance added
 */
function sortBeachesByDistance(array $beaches, float $userLat, float $userLng): array {
    foreach ($beaches as &$beach) {
        $beach['distance'] = calculateDistance(
            $userLat,
            $userLng,
            (float)$beach['lat'],
            (float)$beach['lng']
        );
        $beach['distance_formatted'] = formatDistanceDisplay($beach['distance']);
    }

    usort($beaches, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    return $beaches;
}

/**
 * Filter beaches within a maximum distance from a point
 *
 * @param array $beaches Array of beaches with lat/lng
 * @param float $userLat User's latitude
 * @param float $userLng User's longitude
 * @param float $maxDistanceKm Maximum distance in kilometers
 * @return array Filtered beaches within the distance
 */
function filterBeachesByDistance(array $beaches, float $userLat, float $userLng, float $maxDistanceKm): array {
    $maxDistanceMeters = $maxDistanceKm * 1000;

    return array_filter($beaches, function($beach) use ($userLat, $userLng, $maxDistanceMeters) {
        $distance = calculateDistance(
            $userLat,
            $userLng,
            (float)$beach['lat'],
            (float)$beach['lng']
        );
        return $distance <= $maxDistanceMeters;
    });
}

/**
 * Get the center point of Puerto Rico (for initial map view)
 *
 * @return array ['lat' => float, 'lng' => float]
 */
function getPRCenter(): array {
    return [
        'lat' => 18.2208,
        'lng' => -66.5901
    ];
}

/**
 * Get the bounding box for Puerto Rico (for map bounds)
 *
 * @return array [sw => [lat, lng], ne => [lat, lng]]
 */
function getPRBounds(): array {
    return [
        'sw' => [17.8, -67.4],  // Southwest corner
        'ne' => [18.6, -65.2]   // Northeast corner
    ];
}
