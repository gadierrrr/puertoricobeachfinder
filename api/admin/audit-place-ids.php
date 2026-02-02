<?php
/**
 * Admin Place ID Audit API
 *
 * Endpoints:
 * POST action=start    - Start a new audit (clears previous results)
 * POST action=batch    - Process a batch of beaches
 * POST action=flag     - Flag/unflag a beach for review
 * POST action=resolve  - Mark an issue as resolved
 * POST action=update   - Update a beach's place_id
 * GET                  - Get audit status and results
 */

require_once __DIR__ . '/../../inc/session.php';
session_start();
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/helpers.php';
require_once __DIR__ . '/../../inc/admin.php';
require_once __DIR__ . '/../../inc/constants.php';

header('Content-Type: application/json');

// Require admin authentication
if (!isAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

// Google Maps API Key
$apiKey = 'AIzaSyBJzRm5Qpwxmep93ZoPdXAb8w_4zbNomps';

// Ensure audit table exists
ensureAuditTable();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getAuditResults();
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $csrfToken = $input['csrf_token'] ?? '';

    // Validate CSRF
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }

    switch ($action) {
        case 'start':
            startAudit();
            break;
        case 'batch':
            $batchSize = intval($input['batch_size'] ?? 10);
            processBatch($batchSize, $apiKey);
            break;
        case 'flag':
            $beachId = $input['beach_id'] ?? '';
            $flagged = $input['flagged'] ?? true;
            flagBeach($beachId, $flagged);
            break;
        case 'resolve':
            $beachId = $input['beach_id'] ?? '';
            $resolution = $input['resolution'] ?? '';
            resolveIssue($beachId, $resolution);
            break;
        case 'update':
            $beachId = $input['beach_id'] ?? '';
            $placeId = $input['place_id'] ?? '';
            updatePlaceId($beachId, $placeId, $apiKey);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Ensure the audit table exists
 */
function ensureAuditTable() {
    $db = getDb();
    $db->exec("
        CREATE TABLE IF NOT EXISTS place_id_audit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            beach_id TEXT NOT NULL UNIQUE,
            beach_name TEXT NOT NULL,
            beach_slug TEXT NOT NULL,
            municipality TEXT,
            current_place_id TEXT,
            found_place_id TEXT,
            status TEXT DEFAULT 'pending',
            issue_type TEXT,
            issue_details TEXT,
            coord_distance_meters REAL,
            google_name TEXT,
            google_address TEXT,
            flagged INTEGER DEFAULT 0,
            resolved INTEGER DEFAULT 0,
            resolution_notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (beach_id) REFERENCES beaches(id)
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_status ON place_id_audit(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_flagged ON place_id_audit(flagged)");
}

/**
 * Get audit results and status
 */
function getAuditResults() {
    $filter = $_GET['filter'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // Build WHERE clause based on filter
    $where = '1=1';
    $params = [];

    switch ($filter) {
        case 'issues':
            $where = "issue_type IS NOT NULL AND resolved = 0";
            break;
        case 'flagged':
            $where = "flagged = 1";
            break;
        case 'missing':
            $where = "issue_type = 'missing_place_id'";
            break;
        case 'mismatch':
            $where = "issue_type IN ('name_mismatch', 'coord_mismatch', 'invalid_place_id')";
            break;
        case 'resolved':
            $where = "resolved = 1";
            break;
        case 'pending':
            $where = "status = 'pending'";
            break;
    }

    // Get stats
    $stats = [
        'total' => queryOne("SELECT COUNT(*) as c FROM place_id_audit")['c'] ?? 0,
        'pending' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE status = 'pending'")['c'] ?? 0,
        'processed' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE status = 'processed'")['c'] ?? 0,
        'issues' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE issue_type IS NOT NULL AND resolved = 0")['c'] ?? 0,
        'flagged' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE flagged = 1")['c'] ?? 0,
        'resolved' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE resolved = 1")['c'] ?? 0,
        'missing_place_id' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE issue_type = 'missing_place_id'")['c'] ?? 0,
        'invalid_place_id' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE issue_type = 'invalid_place_id'")['c'] ?? 0,
        'name_mismatch' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE issue_type = 'name_mismatch'")['c'] ?? 0,
        'coord_mismatch' => queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE issue_type = 'coord_mismatch'")['c'] ?? 0,
    ];

    // Get total beaches
    $totalBeaches = queryOne("SELECT COUNT(*) as c FROM beaches")['c'] ?? 0;
    $stats['total_beaches'] = $totalBeaches;
    $stats['audit_complete'] = $stats['total'] >= $totalBeaches && $stats['pending'] === 0;

    // Get filtered results
    $countSql = "SELECT COUNT(*) as c FROM place_id_audit WHERE $where";
    $totalResults = queryOne($countSql, $params)['c'] ?? 0;

    $sql = "
        SELECT a.*, b.lat, b.lng, b.cover_image
        FROM place_id_audit a
        LEFT JOIN beaches b ON a.beach_id = b.id
        WHERE $where
        ORDER BY
            CASE WHEN a.flagged = 1 THEN 0 ELSE 1 END,
            CASE WHEN a.issue_type IS NOT NULL THEN 0 ELSE 1 END,
            a.updated_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;

    $results = query($sql, $params);

    jsonResponse([
        'success' => true,
        'stats' => $stats,
        'filter' => $filter,
        'page' => $page,
        'per_page' => $perPage,
        'total_results' => $totalResults,
        'total_pages' => ceil($totalResults / $perPage),
        'results' => $results
    ]);
}

/**
 * Start a new audit
 */
function startAudit() {
    $db = getDb();

    // Clear existing audit data
    $db->exec("DELETE FROM place_id_audit");

    // Insert all beaches as pending
    $db->exec("
        INSERT INTO place_id_audit (beach_id, beach_name, beach_slug, municipality, current_place_id, status)
        SELECT id, name, slug, municipality, place_id, 'pending'
        FROM beaches
        ORDER BY name
    ");

    $count = queryOne("SELECT COUNT(*) as c FROM place_id_audit")['c'] ?? 0;

    jsonResponse([
        'success' => true,
        'message' => 'Audit started',
        'total_beaches' => $count
    ]);
}

/**
 * Process a batch of beaches
 */
function processBatch($batchSize, $apiKey) {
    // Get pending beaches
    $beaches = query("
        SELECT a.*, b.lat, b.lng
        FROM place_id_audit a
        JOIN beaches b ON a.beach_id = b.id
        WHERE a.status = 'pending'
        ORDER BY a.id
        LIMIT :limit
    ", [':limit' => $batchSize]);

    if (empty($beaches)) {
        jsonResponse([
            'success' => true,
            'message' => 'No more beaches to process',
            'processed' => 0,
            'remaining' => 0
        ]);
        return;
    }

    $processed = 0;
    $issues = 0;
    $results = [];

    foreach ($beaches as $beach) {
        $result = auditBeach($beach, $apiKey);
        $results[] = $result;
        $processed++;
        if ($result['has_issue']) {
            $issues++;
        }
    }

    // Get remaining count
    $remaining = queryOne("SELECT COUNT(*) as c FROM place_id_audit WHERE status = 'pending'")['c'] ?? 0;

    jsonResponse([
        'success' => true,
        'processed' => $processed,
        'issues_found' => $issues,
        'remaining' => $remaining,
        'results' => $results
    ]);
}

/**
 * Audit a single beach
 */
function auditBeach($beach, $apiKey) {
    $beachId = $beach['beach_id'];
    $currentPlaceId = $beach['current_place_id'];
    $lat = $beach['lat'];
    $lng = $beach['lng'];
    $beachName = $beach['beach_name'];

    $issueType = null;
    $issueDetails = null;
    $foundPlaceId = null;
    $googleName = null;
    $googleAddress = null;
    $coordDistance = null;

    // Case 1: No place_id - try to find one
    if (empty($currentPlaceId)) {
        $nearbyResult = findNearbyPlace($lat, $lng, $beachName, $apiKey);

        if ($nearbyResult && !isset($nearbyResult['error'])) {
            $foundPlaceId = $nearbyResult['place_id'];
            $googleName = $nearbyResult['name'];
            $googleAddress = $nearbyResult['vicinity'] ?? '';
            $coordDistance = $nearbyResult['distance'] ?? null;

            $issueType = 'missing_place_id';
            $issueDetails = "Found potential match: \"{$googleName}\" ({$coordDistance}m away)";
        } else {
            $issueType = 'missing_place_id';
            $issueDetails = 'No place_id and no nearby match found';
        }
    }
    // Case 2: Has place_id - verify it
    else {
        $placeDetails = getPlaceDetails($currentPlaceId, $apiKey);

        if (isset($placeDetails['error'])) {
            // Invalid place_id
            $issueType = 'invalid_place_id';
            $issueDetails = "Place ID no longer valid: " . $placeDetails['error'];

            // Try to find a replacement
            $nearbyResult = findNearbyPlace($lat, $lng, $beachName, $apiKey);
            if ($nearbyResult && !isset($nearbyResult['error'])) {
                $foundPlaceId = $nearbyResult['place_id'];
                $googleName = $nearbyResult['name'];
                $googleAddress = $nearbyResult['vicinity'] ?? '';
                $coordDistance = $nearbyResult['distance'] ?? null;
                $issueDetails .= ". Found potential replacement: \"{$googleName}\"";
            }
        } else {
            // Place exists - check for mismatches
            $googleName = $placeDetails['name'] ?? '';
            $googleAddress = $placeDetails['formatted_address'] ?? '';
            $googleLat = $placeDetails['geometry']['location']['lat'] ?? null;
            $googleLng = $placeDetails['geometry']['location']['lng'] ?? null;

            // Check coordinate distance
            if ($googleLat && $googleLng) {
                $coordDistance = haversineDistance($lat, $lng, $googleLat, $googleLng);

                // Flag if more than 500m apart
                if ($coordDistance > 500) {
                    $issueType = 'coord_mismatch';
                    $issueDetails = "Coordinates differ by " . round($coordDistance) . "m. DB: ($lat, $lng), Google: ($googleLat, $googleLng)";
                }
            }

            // Check name similarity
            $nameSimilarity = calculateNameSimilarity($beachName, $googleName);
            if ($nameSimilarity < 0.4 && !$issueType) {
                $issueType = 'name_mismatch';
                $issueDetails = "Name mismatch: DB=\"{$beachName}\" vs Google=\"{$googleName}\" (similarity: " . round($nameSimilarity * 100) . "%)";
            }
        }
    }

    // Update audit record
    execute("
        UPDATE place_id_audit SET
            status = 'processed',
            issue_type = :issue_type,
            issue_details = :issue_details,
            found_place_id = :found_place_id,
            google_name = :google_name,
            google_address = :google_address,
            coord_distance_meters = :coord_distance,
            updated_at = datetime('now')
        WHERE beach_id = :beach_id
    ", [
        ':issue_type' => $issueType,
        ':issue_details' => $issueDetails,
        ':found_place_id' => $foundPlaceId,
        ':google_name' => $googleName,
        ':google_address' => $googleAddress,
        ':coord_distance' => $coordDistance,
        ':beach_id' => $beachId
    ]);

    return [
        'beach_id' => $beachId,
        'beach_name' => $beachName,
        'has_issue' => $issueType !== null,
        'issue_type' => $issueType,
        'issue_details' => $issueDetails
    ];
}

/**
 * Calculate Haversine distance between two points in meters
 */
function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // meters

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLng / 2) * sin($deltaLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Calculate name similarity (0-1)
 */
function calculateNameSimilarity($name1, $name2) {
    // Normalize names
    $n1 = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $name1));
    $n2 = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $name2));

    // Check if one contains the other
    if (strpos($n1, $n2) !== false || strpos($n2, $n1) !== false) {
        return 0.8;
    }

    // Use similar_text for percentage match
    similar_text($n1, $n2, $percent);
    return $percent / 100;
}

/**
 * Find nearby place via Google API
 */
function findNearbyPlace($lat, $lng, $name, $apiKey) {
    // Try text search first with location bias
    $searchQuery = $name . ' Puerto Rico beach';
    $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
         . "?input=" . urlencode($searchQuery)
         . "&inputtype=textquery"
         . "&fields=place_id,name,geometry,vicinity"
         . "&locationbias=circle:1000@{$lat},{$lng}"
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['candidates'])) {
        $place = $data['candidates'][0];
        $placeLat = $place['geometry']['location']['lat'] ?? $lat;
        $placeLng = $place['geometry']['location']['lng'] ?? $lng;
        $distance = haversineDistance($lat, $lng, $placeLat, $placeLng);

        return [
            'place_id' => $place['place_id'],
            'name' => $place['name'] ?? '',
            'vicinity' => $place['vicinity'] ?? '',
            'distance' => round($distance)
        ];
    }

    // Fallback to nearby search
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json"
         . "?location={$lat},{$lng}"
         . "&radius=200"
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['results'])) {
        $place = $data['results'][0];
        $placeLat = $place['geometry']['location']['lat'] ?? $lat;
        $placeLng = $place['geometry']['location']['lng'] ?? $lng;
        $distance = haversineDistance($lat, $lng, $placeLat, $placeLng);

        return [
            'place_id' => $place['place_id'],
            'name' => $place['name'] ?? '',
            'vicinity' => $place['vicinity'] ?? '',
            'distance' => round($distance)
        ];
    }

    return ['error' => 'No nearby places found'];
}

/**
 * Get place details from Google API
 */
function getPlaceDetails($placeId, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json"
         . "?place_id=" . urlencode($placeId)
         . "&fields=name,geometry,formatted_address"
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] !== 'OK') {
        return ['error' => $data['status'] . ': ' . ($data['error_message'] ?? 'Unknown error')];
    }

    return $data['result'] ?? ['error' => 'No result'];
}

/**
 * Flag/unflag a beach for review
 */
function flagBeach($beachId, $flagged) {
    if (empty($beachId)) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    execute("
        UPDATE place_id_audit SET
            flagged = :flagged,
            updated_at = datetime('now')
        WHERE beach_id = :beach_id
    ", [
        ':flagged' => $flagged ? 1 : 0,
        ':beach_id' => $beachId
    ]);

    jsonResponse(['success' => true, 'flagged' => $flagged]);
}

/**
 * Mark an issue as resolved
 */
function resolveIssue($beachId, $resolution) {
    if (empty($beachId)) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    execute("
        UPDATE place_id_audit SET
            resolved = 1,
            resolution_notes = :resolution,
            updated_at = datetime('now')
        WHERE beach_id = :beach_id
    ", [
        ':resolution' => $resolution,
        ':beach_id' => $beachId
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Update a beach's place_id
 */
function updatePlaceId($beachId, $newPlaceId, $apiKey) {
    if (empty($beachId)) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    // Verify the new place_id is valid
    if (!empty($newPlaceId)) {
        $placeDetails = getPlaceDetails($newPlaceId, $apiKey);
        if (isset($placeDetails['error'])) {
            jsonResponse(['error' => 'Invalid place_id: ' . $placeDetails['error']], 400);
        }
    }

    // Update the beach
    execute("
        UPDATE beaches SET
            place_id = :place_id,
            updated_at = datetime('now')
        WHERE id = :beach_id
    ", [
        ':place_id' => $newPlaceId ?: null,
        ':beach_id' => $beachId
    ]);

    // Update audit record
    execute("
        UPDATE place_id_audit SET
            current_place_id = :place_id,
            resolved = 1,
            resolution_notes = 'Place ID updated',
            updated_at = datetime('now')
        WHERE beach_id = :beach_id
    ", [
        ':place_id' => $newPlaceId ?: null,
        ':beach_id' => $beachId
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Place ID updated'
    ]);
}
