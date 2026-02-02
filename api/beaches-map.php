<?php
/**
 * API: Get Beach Data for Map View
 *
 * Returns minimal beach data needed for map markers and client-side filtering.
 * This endpoint is designed to be lazy-loaded after page render.
 *
 * GET /api/beaches-map.php
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Cache for 24 hours - beach data rarely changes
header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400, s-maxage=86400');

// Get minimal beach data for map markers
$sql = 'SELECT b.id, b.slug, b.name, b.municipality, b.lat, b.lng,
               b.cover_image, b.google_rating
        FROM beaches b
        WHERE b.publish_status = "published"
        ORDER BY b.name ASC';

$beaches = query($sql, []);

// Attach tags (needed for client-side filtering)
if (!empty($beaches)) {
    $beachIds = array_column($beaches, 'id');
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));

    $tagsSql = "SELECT beach_id, tag FROM beach_tags WHERE beach_id IN ($placeholders)";
    $tagsResult = query($tagsSql, $beachIds);

    // Group tags by beach_id
    $tagsByBeach = [];
    foreach ($tagsResult as $row) {
        $tagsByBeach[$row['beach_id']][] = $row['tag'];
    }

    // Attach tags to beaches
    foreach ($beaches as &$beach) {
        $beach['tags'] = $tagsByBeach[$beach['id']] ?? [];
    }
}

echo json_encode([
    'beaches' => $beaches,
    'total' => count($beaches)
]);
