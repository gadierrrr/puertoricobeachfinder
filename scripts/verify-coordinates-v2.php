#!/usr/bin/env php
<?php
/**
 * Beach Coordinate Verification v2
 * Uses Place Details API (for beaches with place_id) and improved text search
 * (for beaches without place_id) to verify database coordinates against Google Maps.
 *
 * Usage:
 *   php scripts/verify-coordinates-v2.php [options]
 *
 * Options:
 *   --phase=1|2|3|all  Which phase to run (default: all)
 *   --limit=N          Process only N beaches (for testing)
 *   --fix              Generate migration file with corrections
 *   --resume           Skip beaches already in output CSV
 *   --help             Show this help message
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/geo.php';

// ── Color output helpers ────────────────────────────────────────────────────

function colorize($text, $color) {
    $colors = [
        'red' => "\033[31m", 'green' => "\033[32m",
        'yellow' => "\033[33m", 'blue' => "\033[34m",
        'cyan' => "\033[36m", 'reset' => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}
function success($msg) { echo colorize("  OK ", 'green') . $msg . "\n"; }
function warn($msg)    { echo colorize("  WARN ", 'yellow') . $msg . "\n"; }
function err($msg)     { echo colorize("  FAIL ", 'red') . $msg . "\n"; }
function info($msg)    { echo colorize("  INFO ", 'blue') . $msg . "\n"; }

// ── CLI argument parsing ────────────────────────────────────────────────────

$args = getopt('', ['phase:', 'limit:', 'fix', 'resume', 'help']);

if (isset($args['help'])) {
    echo "Beach Coordinate Verification v2\n";
    echo "=================================\n\n";
    echo "Usage: php scripts/verify-coordinates-v2.php [options]\n\n";
    echo "Options:\n";
    echo "  --phase=1|2|3|all  Phase to run (default: all)\n";
    echo "  --limit=N          Process only N beaches\n";
    echo "  --fix              Generate migration file\n";
    echo "  --resume           Skip already-processed beaches\n";
    echo "  --help             Show this help\n";
    exit(0);
}

$phase = $args['phase'] ?? 'all';
$limit = isset($args['limit']) ? (int)$args['limit'] : null;
$generateFix = isset($args['fix']);
$resume = isset($args['resume']);
$apiKey = envRequire('GOOGLE_MAPS_API_KEY');
$outputDir = __DIR__ . '/../data';

// ── Google API wrappers ─────────────────────────────────────────────────────

function callPlaceDetails(string $placeId, string $apiKey): array {
    $url = "https://maps.googleapis.com/maps/api/place/details/json"
         . "?place_id=" . urlencode($placeId)
         . "&fields=geometry,name,types,formatted_address,business_status"
         . "&key={$apiKey}";

    $ctx = stream_context_create(['http' => ['timeout' => 15]]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return ['ok' => false, 'error' => 'HTTP request failed'];
    }

    $data = json_decode($response, true);
    if (!$data) {
        return ['ok' => false, 'error' => 'Invalid JSON'];
    }
    if ($data['status'] !== 'OK') {
        return ['ok' => false, 'error' => $data['status'] . ': ' . ($data['error_message'] ?? '')];
    }

    $r = $data['result'];
    return [
        'ok'      => true,
        'name'    => $r['name'] ?? '',
        'lat'     => $r['geometry']['location']['lat'] ?? null,
        'lng'     => $r['geometry']['location']['lng'] ?? null,
        'types'   => $r['types'] ?? [],
        'address' => $r['formatted_address'] ?? '',
        'status'  => $r['business_status'] ?? '',
    ];
}

function callFindPlace(string $query, string $apiKey, string $locationBias): array {
    $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
         . "?input=" . urlencode($query)
         . "&inputtype=textquery"
         . "&fields=name,geometry,formatted_address,place_id,types,business_status"
         . "&locationbias=" . urlencode($locationBias)
         . "&key={$apiKey}";

    $ctx = stream_context_create(['http' => ['timeout' => 15]]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return ['ok' => false, 'error' => 'HTTP request failed'];
    }

    $data = json_decode($response, true);
    if (!$data) {
        return ['ok' => false, 'error' => 'Invalid JSON'];
    }
    if ($data['status'] === 'ZERO_RESULTS' || empty($data['candidates'])) {
        return ['ok' => true, 'found' => false];
    }
    if ($data['status'] !== 'OK') {
        return ['ok' => false, 'error' => $data['status'] . ': ' . ($data['error_message'] ?? '')];
    }

    $c = $data['candidates'][0];
    return [
        'ok'       => true,
        'found'    => true,
        'name'     => $c['name'] ?? '',
        'lat'      => $c['geometry']['location']['lat'] ?? null,
        'lng'      => $c['geometry']['location']['lng'] ?? null,
        'types'    => $c['types'] ?? [],
        'place_id' => $c['place_id'] ?? '',
        'address'  => $c['formatted_address'] ?? '',
    ];
}

// ── Type classification ─────────────────────────────────────────────────────

function classifyTypes(array $types): array {
    $beachLike = ['natural_feature', 'park', 'point_of_interest', 'establishment',
                  'campground', 'tourist_attraction', 'locality'];
    $suspect   = ['airport', 'restaurant', 'store', 'lodging', 'car_rental',
                  'gas_station', 'museum', 'church', 'school', 'hospital',
                  'shopping_mall', 'bank', 'pharmacy'];

    $isBeach   = !empty(array_intersect($types, $beachLike));
    $isSuspect = !empty(array_intersect($types, $suspect));

    return ['is_beach' => $isBeach, 'is_suspect' => $isSuspect];
}

function getPriority(float $distance): string {
    if ($distance > 2000) return 'CRITICAL';
    if ($distance > 500)  return 'HIGH';
    if ($distance > 100)  return 'MEDIUM';
    return 'OK';
}

// ── Load existing results for --resume ───────────────────────────────────────

function loadProcessedIds(string $csvPath): array {
    $ids = [];
    if (!file_exists($csvPath)) return $ids;
    $fp = fopen($csvPath, 'r');
    $header = fgetcsv($fp); // skip header
    while ($row = fgetcsv($fp)) {
        if (!empty($row[0])) $ids[$row[0]] = true;
    }
    fclose($fp);
    return $ids;
}

// ── PHASE 1: Place Details for beaches with place_id ────────────────────────

function runPhase1($db, $apiKey, $limit, $outputDir, $resume) {
    echo "\n" . colorize("PHASE 1: Place Details API (beaches with place_id)", 'cyan') . "\n";
    echo str_repeat('=', 60) . "\n";

    // Get all beaches with place_id
    $sql = "SELECT id, name, municipality, lat, lng, place_id FROM beaches
            WHERE place_id IS NOT NULL AND place_id != ''
            ORDER BY name";
    $beaches = query($sql);

    if ($limit) $beaches = array_slice($beaches, 0, $limit);
    echo "Checking " . count($beaches) . " beaches with place_id\n\n";

    // Identify duplicate place_ids
    $placeIdCounts = [];
    foreach ($beaches as $b) {
        $placeIdCounts[$b['place_id']] = ($placeIdCounts[$b['place_id']] ?? 0) + 1;
    }
    $duplicatePlaceIds = array_filter($placeIdCounts, fn($c) => $c > 1);
    echo "Found " . count($duplicatePlaceIds) . " duplicate place_ids (shared by " .
         array_sum($duplicatePlaceIds) . " beaches)\n\n";

    // Deduplicate API calls
    $placeDetailsCache = [];
    $processedIds = $resume ? loadProcessedIds("{$outputDir}/phase1-place-details.csv") : [];

    // Open CSV
    $csvPath = "{$outputDir}/phase1-place-details.csv";
    $isNewFile = !$resume || !file_exists($csvPath);
    $fp = fopen($csvPath, $resume ? 'a' : 'w');
    if ($isNewFile) {
        fputcsv($fp, ['id', 'name', 'municipality', 'db_lat', 'db_lng',
                       'google_lat', 'google_lng', 'distance_m', 'priority',
                       'google_name', 'google_types', 'type_match', 'is_suspect',
                       'is_duplicate_placeid', 'place_id', 'recommended_action', 'notes']);
    }

    $stats = ['OK' => 0, 'MEDIUM' => 0, 'HIGH' => 0, 'CRITICAL' => 0, 'ERROR' => 0, 'DUPLICATE' => 0];
    $results = [];

    foreach ($beaches as $i => $beach) {
        $progress = sprintf("[%d/%d]", $i + 1, count($beaches));

        if (isset($processedIds[$beach['id']])) {
            continue;
        }

        $isDuplicate = ($placeIdCounts[$beach['place_id']] ?? 1) > 1;

        // Check cache or call API
        if (!isset($placeDetailsCache[$beach['place_id']])) {
            usleep(200000); // 200ms rate limit
            $placeDetailsCache[$beach['place_id']] = callPlaceDetails($beach['place_id'], $apiKey);
        }

        $result = $placeDetailsCache[$beach['place_id']];

        if (!$result['ok']) {
            err("{$progress} {$beach['name']} - {$result['error']}");
            $stats['ERROR']++;
            fputcsv($fp, [$beach['id'], $beach['name'], $beach['municipality'],
                          $beach['lat'], $beach['lng'], '', '', '', 'ERROR',
                          '', '', '', '', $isDuplicate ? 'Y' : 'N',
                          $beach['place_id'], 'ERROR', $result['error']]);
            continue;
        }

        $distance = calculateDistance(
            (float)$beach['lat'], (float)$beach['lng'],
            (float)$result['lat'], (float)$result['lng']
        );
        $priority = getPriority($distance);
        $typeInfo = classifyTypes($result['types']);

        // Determine recommended action
        $action = 'KEEP_CURRENT';
        $notes = '';

        if ($isDuplicate) {
            $action = 'REVIEW_DUPLICATE';
            $notes = "Shared place_id with " . ($placeIdCounts[$beach['place_id']] - 1) . " other beach(es)";
            $stats['DUPLICATE']++;
        } elseif ($typeInfo['is_suspect']) {
            $action = 'REVIEW_TYPE';
            $notes = "Suspect type: " . implode(',', $result['types']);
        } elseif ($priority === 'CRITICAL') {
            $action = 'REVIEW_DISTANCE';
            $notes = sprintf("%.0fm off - may be wrong place", $distance);
        } elseif ($priority === 'HIGH') {
            $action = 'ACCEPT';
            $notes = sprintf("%.0fm off - likely correct place, imprecise pin", $distance);
        } elseif ($priority === 'MEDIUM') {
            $action = 'ACCEPT';
            $notes = sprintf("%.0fm refinement", $distance);
        }

        $distStr = $distance > 1000
            ? sprintf("%.1fkm", $distance / 1000)
            : sprintf("%.0fm", $distance);

        if ($priority === 'OK') {
            success("{$progress} {$beach['name']} ({$distStr})");
        } elseif ($isDuplicate) {
            warn("{$progress} {$beach['name']} - DUPLICATE place_id ({$distStr}) [{$result['name']}]");
        } elseif ($priority === 'CRITICAL' || $typeInfo['is_suspect']) {
            err("{$progress} {$beach['name']} - {$priority} ({$distStr}) [{$result['name']}]");
        } else {
            warn("{$progress} {$beach['name']} - {$priority} ({$distStr})");
        }

        $stats[$priority]++;

        fputcsv($fp, [
            $beach['id'], $beach['name'], $beach['municipality'],
            $beach['lat'], $beach['lng'], $result['lat'], $result['lng'],
            round($distance), $priority, $result['name'],
            implode('|', $result['types']),
            $typeInfo['is_beach'] ? 'Y' : 'N',
            $typeInfo['is_suspect'] ? 'Y' : 'N',
            $isDuplicate ? 'Y' : 'N',
            $beach['place_id'], $action, $notes
        ]);

        $results[] = [
            'id' => $beach['id'],
            'name' => $beach['name'],
            'municipality' => $beach['municipality'],
            'db_lat' => $beach['lat'],
            'db_lng' => $beach['lng'],
            'google_lat' => $result['lat'],
            'google_lng' => $result['lng'],
            'distance' => $distance,
            'priority' => $priority,
            'google_name' => $result['name'],
            'google_types' => $result['types'],
            'type_match' => $typeInfo['is_beach'],
            'is_suspect' => $typeInfo['is_suspect'],
            'is_duplicate' => $isDuplicate,
            'place_id' => $beach['place_id'],
            'action' => $action,
            'notes' => $notes,
        ];
    }

    fclose($fp);

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "PHASE 1 SUMMARY\n";
    echo str_repeat('=', 60) . "\n";
    echo colorize("  OK (<100m):        ", 'green') . $stats['OK'] . "\n";
    echo colorize("  MEDIUM (100-500m): ", 'yellow') . $stats['MEDIUM'] . "\n";
    echo colorize("  HIGH (500m-2km):   ", 'yellow') . $stats['HIGH'] . "\n";
    echo colorize("  CRITICAL (>2km):   ", 'red') . $stats['CRITICAL'] . "\n";
    echo colorize("  DUPLICATE place_id:", 'yellow') . $stats['DUPLICATE'] . "\n";
    echo colorize("  ERROR:             ", 'red') . $stats['ERROR'] . "\n";
    echo "\nCSV saved to: {$csvPath}\n";

    return $results;
}

// ── PHASE 2: Text search for beaches without place_id ───────────────────────

function runPhase2($db, $apiKey, $limit, $outputDir, $resume) {
    echo "\n" . colorize("PHASE 2: Text Search (beaches without place_id)", 'cyan') . "\n";
    echo str_repeat('=', 60) . "\n";

    // Get beaches without place_id
    $sql = "SELECT id, name, municipality, lat, lng FROM beaches
            WHERE place_id IS NULL OR place_id = ''
            ORDER BY name";
    $beaches = query($sql);

    if ($limit) $beaches = array_slice($beaches, 0, $limit);
    echo "Checking " . count($beaches) . " beaches without place_id\n\n";

    // Compute municipality centroids for location bias
    $centroids = query("SELECT municipality, AVG(lat) as clat, AVG(lng) as clng
                        FROM beaches WHERE lat IS NOT NULL GROUP BY municipality");
    $munCenters = [];
    foreach ($centroids as $c) {
        $munCenters[$c['municipality']] = ['lat' => $c['clat'], 'lng' => $c['clng']];
    }

    $processedIds = $resume ? loadProcessedIds("{$outputDir}/phase2-text-search.csv") : [];

    $csvPath = "{$outputDir}/phase2-text-search.csv";
    $isNewFile = !$resume || !file_exists($csvPath);
    $fp = fopen($csvPath, $resume ? 'a' : 'w');
    if ($isNewFile) {
        fputcsv($fp, ['id', 'name', 'municipality', 'db_lat', 'db_lng',
                       'google_lat', 'google_lng', 'distance_m', 'priority',
                       'google_name', 'google_types', 'place_id_found',
                       'query_used', 'confidence', 'recommended_action', 'notes']);
    }

    $stats = ['OK' => 0, 'MEDIUM' => 0, 'HIGH' => 0, 'CRITICAL' => 0,
              'NOT_FOUND' => 0, 'ERROR' => 0];
    $results = [];

    foreach ($beaches as $i => $beach) {
        $progress = sprintf("[%d/%d]", $i + 1, count($beaches));

        if (isset($processedIds[$beach['id']])) {
            continue;
        }

        $center = $munCenters[$beach['municipality']] ?? ['lat' => 18.2208, 'lng' => -66.5901];
        $locationBias = "circle:30000@{$center['lat']},{$center['lng']}";

        // Try multiple query variants
        $queries = [
            "{$beach['name']}, {$beach['municipality']}, Puerto Rico",
            "Playa {$beach['name']}, {$beach['municipality']}, Puerto Rico",
            "{$beach['name']} beach, {$beach['municipality']}, Puerto Rico",
            "{$beach['name']} Puerto Rico beach",
        ];

        $bestResult = null;
        $bestQuery = '';
        $bestScore = -999;

        foreach ($queries as $q) {
            usleep(200000); // 200ms rate limit

            $result = callFindPlace($q, $apiKey, $locationBias);

            if (!$result['ok']) {
                err("{$progress} {$beach['name']} - {$result['error']}");
                $stats['ERROR']++;
                break;
            }

            if (!$result['found']) continue;

            // Score this result
            $typeInfo = classifyTypes($result['types']);
            $distFromCenter = calculateDistance(
                $center['lat'], $center['lng'],
                $result['lat'], $result['lng']
            );
            $distFromDb = calculateDistance(
                (float)$beach['lat'], (float)$beach['lng'],
                $result['lat'], $result['lng']
            );

            $score = 0;
            if ($typeInfo['is_beach']) $score += 10;
            if ($typeInfo['is_suspect']) $score -= 20;
            if (stripos($result['name'], 'playa') !== false ||
                stripos($result['name'], 'beach') !== false) $score += 5;
            if ($distFromCenter < 5000) $score += 3;
            if ($distFromDb < 2000) $score += 5;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestResult = $result;
                $bestResult['type_info'] = $typeInfo;
                $bestResult['dist_from_db'] = $distFromDb;
                $bestQuery = $q;
            }

            // If we got a great match, stop trying
            if ($score >= 15) break;
        }

        if (!$bestResult || (isset($result) && !$result['ok'])) {
            if (!isset($result) || $result['ok']) {
                info("{$progress} {$beach['name']} - NOT FOUND on Google Maps");
                $stats['NOT_FOUND']++;
                fputcsv($fp, [$beach['id'], $beach['name'], $beach['municipality'],
                              $beach['lat'], $beach['lng'], '', '', '', '',
                              '', '', '', '', 'NONE', 'KEEP_CURRENT', 'Not found on Google Maps']);
            }
            continue;
        }

        $distance = $bestResult['dist_from_db'];
        $priority = getPriority($distance);
        $typeInfo = $bestResult['type_info'];

        // Determine confidence
        $confidence = 'LOW';
        if ($bestScore >= 15 && $distance < 2000) $confidence = 'HIGH';
        elseif ($bestScore >= 8 && $distance < 5000) $confidence = 'MEDIUM';

        // Determine action
        $action = 'KEEP_CURRENT';
        $notes = '';
        if ($typeInfo['is_suspect']) {
            $action = 'REVIEW_TYPE';
            $notes = "Suspect: " . implode(',', $bestResult['types']);
        } elseif ($confidence === 'HIGH' && $priority !== 'OK') {
            $action = 'ACCEPT';
            $notes = sprintf("%.0fm off, high confidence", $distance);
        } elseif ($confidence === 'MEDIUM' && $priority !== 'OK') {
            $action = 'REVIEW';
            $notes = sprintf("%.0fm off, medium confidence", $distance);
        } elseif ($priority === 'CRITICAL') {
            $action = 'REVIEW_DISTANCE';
            $notes = sprintf("%.0fm off - may be wrong place", $distance);
        }

        $distStr = $distance > 1000
            ? sprintf("%.1fkm", $distance / 1000)
            : sprintf("%.0fm", $distance);

        if ($priority === 'OK') {
            success("{$progress} {$beach['name']} ({$distStr})");
        } elseif ($priority === 'CRITICAL' || $typeInfo['is_suspect']) {
            err("{$progress} {$beach['name']} - {$priority} ({$distStr}) [{$bestResult['name']}]");
        } else {
            warn("{$progress} {$beach['name']} - {$priority} ({$distStr})");
        }

        $stats[$priority]++;

        fputcsv($fp, [
            $beach['id'], $beach['name'], $beach['municipality'],
            $beach['lat'], $beach['lng'], $bestResult['lat'], $bestResult['lng'],
            round($distance), $priority, $bestResult['name'],
            implode('|', $bestResult['types']),
            $bestResult['place_id'] ?? '',
            $bestQuery, $confidence, $action, $notes
        ]);

        $results[] = [
            'id' => $beach['id'],
            'name' => $beach['name'],
            'municipality' => $beach['municipality'],
            'db_lat' => $beach['lat'],
            'db_lng' => $beach['lng'],
            'google_lat' => $bestResult['lat'],
            'google_lng' => $bestResult['lng'],
            'distance' => $distance,
            'priority' => $priority,
            'google_name' => $bestResult['name'],
            'place_id_found' => $bestResult['place_id'] ?? '',
            'confidence' => $confidence,
            'action' => $action,
            'notes' => $notes,
        ];
    }

    fclose($fp);

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "PHASE 2 SUMMARY\n";
    echo str_repeat('=', 60) . "\n";
    echo colorize("  OK (<100m):        ", 'green') . $stats['OK'] . "\n";
    echo colorize("  MEDIUM (100-500m): ", 'yellow') . $stats['MEDIUM'] . "\n";
    echo colorize("  HIGH (500m-2km):   ", 'yellow') . $stats['HIGH'] . "\n";
    echo colorize("  CRITICAL (>2km):   ", 'red') . $stats['CRITICAL'] . "\n";
    echo colorize("  NOT FOUND:         ", 'blue') . $stats['NOT_FOUND'] . "\n";
    echo colorize("  ERROR:             ", 'red') . $stats['ERROR'] . "\n";
    echo "\nCSV saved to: {$csvPath}\n";

    return $results;
}

// ── PHASE 3: Consolidated report ────────────────────────────────────────────

function runPhase3($outputDir) {
    echo "\n" . colorize("PHASE 3: Consolidated Report", 'cyan') . "\n";
    echo str_repeat('=', 60) . "\n";

    $report = [
        'generated_at' => date('Y-m-d H:i:s'),
        'summary' => ['total' => 0, 'ok' => 0, 'auto_corrections' => 0,
                       'needs_review' => 0, 'duplicates' => 0, 'no_match' => 0, 'errors' => 0],
        'auto_corrections' => [],
        'needs_review' => [],
        'duplicate_groups' => [],
        'no_match' => [],
        'errors' => [],
    ];

    // Parse Phase 1 CSV
    $p1Path = "{$outputDir}/phase1-place-details.csv";
    if (file_exists($p1Path)) {
        $fp = fopen($p1Path, 'r');
        $header = fgetcsv($fp);
        while ($row = fgetcsv($fp)) {
            $entry = array_combine($header, $row);
            $report['summary']['total']++;

            if ($entry['priority'] === 'ERROR') {
                $report['errors'][] = $entry;
                $report['summary']['errors']++;
            } elseif ($entry['priority'] === 'OK') {
                $report['summary']['ok']++;
            } elseif ($entry['recommended_action'] === 'ACCEPT') {
                $report['auto_corrections'][] = $entry;
                $report['summary']['auto_corrections']++;
            } elseif (str_starts_with($entry['recommended_action'], 'REVIEW')) {
                $report['needs_review'][] = $entry;
                $report['summary']['needs_review']++;
                if ($entry['is_duplicate_placeid'] === 'Y') {
                    $report['summary']['duplicates']++;
                }
            }
        }
        fclose($fp);
    }

    // Parse Phase 2 CSV
    $p2Path = "{$outputDir}/phase2-text-search.csv";
    if (file_exists($p2Path)) {
        $fp = fopen($p2Path, 'r');
        $header = fgetcsv($fp);
        while ($row = fgetcsv($fp)) {
            $entry = array_combine($header, $row);
            $report['summary']['total']++;

            if ($entry['confidence'] === 'NONE') {
                $report['no_match'][] = $entry;
                $report['summary']['no_match']++;
            } elseif ($entry['priority'] === 'OK') {
                $report['summary']['ok']++;
            } elseif ($entry['recommended_action'] === 'ACCEPT') {
                $report['auto_corrections'][] = $entry;
                $report['summary']['auto_corrections']++;
            } else {
                $report['needs_review'][] = $entry;
                $report['summary']['needs_review']++;
            }
        }
        fclose($fp);
    }

    // Group duplicates by place_id for easier review
    $dupGroups = [];
    foreach ($report['needs_review'] as $entry) {
        if (($entry['is_duplicate_placeid'] ?? '') === 'Y') {
            $pid = $entry['place_id'];
            if (!isset($dupGroups[$pid])) {
                $dupGroups[$pid] = [
                    'place_id' => $pid,
                    'google_name' => $entry['google_name'],
                    'google_lat' => $entry['google_lat'],
                    'google_lng' => $entry['google_lng'],
                    'beaches' => [],
                ];
            }
            $dupGroups[$pid]['beaches'][] = [
                'id' => $entry['id'],
                'name' => $entry['name'],
                'municipality' => $entry['municipality'],
                'db_lat' => $entry['db_lat'],
                'db_lng' => $entry['db_lng'],
                'distance_m' => $entry['distance_m'],
            ];
        }
    }
    $report['duplicate_groups'] = array_values($dupGroups);

    // Write JSON
    $jsonPath = "{$outputDir}/phase3-consolidated-review.json";
    file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Summary:\n";
    echo "  Total checked:      {$report['summary']['total']}\n";
    echo colorize("  OK (<100m):         ", 'green') . $report['summary']['ok'] . "\n";
    echo colorize("  Auto-correctable:   ", 'yellow') . $report['summary']['auto_corrections'] . "\n";
    echo colorize("  Needs review:       ", 'yellow') . $report['summary']['needs_review'] . "\n";
    echo colorize("  Duplicate groups:   ", 'yellow') . count($report['duplicate_groups']) . "\n";
    echo colorize("  No match:           ", 'blue') . $report['summary']['no_match'] . "\n";
    echo colorize("  Errors:             ", 'red') . $report['summary']['errors'] . "\n";
    echo "\nReport saved to: {$jsonPath}\n";

    return $report;
}

// ── Migration generator ─────────────────────────────────────────────────────

function generateMigration($outputDir) {
    $jsonPath = "{$outputDir}/phase3-consolidated-review.json";
    if (!file_exists($jsonPath)) {
        err("Run phases 1-3 first to generate the consolidated report.");
        return;
    }

    $report = json_decode(file_get_contents($jsonPath), true);
    $corrections = $report['auto_corrections'] ?? [];

    if (empty($corrections)) {
        info("No auto-correctable entries found.");
        return;
    }

    echo "\n" . colorize("Generating migration for " . count($corrections) . " corrections", 'cyan') . "\n";

    // Find next migration number
    $migrations = glob(__DIR__ . '/../migrations/*.php');
    $maxNum = 0;
    foreach ($migrations as $m) {
        if (preg_match('/(\d+)-/', basename($m), $match)) {
            $maxNum = max($maxNum, (int)$match[1]);
        }
    }
    $nextNum = str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);

    $migrationFile = __DIR__ . "/../migrations/{$nextNum}-update-beach-coordinates-v2.php";

    $php = "<?php\n";
    $php .= "/**\n";
    $php .= " * Migration: Update beach coordinates - v2 API Verified\n";
    $php .= " * Auto-generated from verify-coordinates-v2.php\n";
    $php .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
    $php .= " *\n";
    $php .= " * Corrections: " . count($corrections) . " beaches\n";
    $php .= " * IMPORTANT: Back up database before running!\n";
    $php .= " *   php scripts/backup-db.php\n";
    $php .= " */\n\n";
    $php .= "require_once __DIR__ . '/../inc/db.php';\n\n";
    $php .= "// Corrections: id => [lat, lng, google_name, distance_m]\n";
    $php .= "\$corrections = [\n";

    foreach ($corrections as $c) {
        $name = addslashes($c['name']);
        $gname = addslashes($c['google_name']);
        $php .= "    // {$name} ({$c['priority']}: {$c['distance_m']}m) -> \"{$gname}\"\n";
        $php .= "    '{$c['id']}' => [{$c['google_lat']}, {$c['google_lng']}],\n";
    }

    $php .= "];\n\n";
    $php .= "\$db = getDb();\n";
    $php .= "\$updated = 0;\n\n";
    $php .= "foreach (\$corrections as \$id => \$coords) {\n";
    $php .= "    \$stmt = \$db->prepare(\"UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE id = :id\");\n";
    $php .= "    \$stmt->bindValue(':lat', \$coords[0], SQLITE3_FLOAT);\n";
    $php .= "    \$stmt->bindValue(':lng', \$coords[1], SQLITE3_FLOAT);\n";
    $php .= "    \$stmt->bindValue(':id', \$id, SQLITE3_TEXT);\n";
    $php .= "    \$stmt->execute();\n";
    $php .= "    if (\$db->changes() > 0) {\n";
    $php .= "        echo \"Updated: \$id\\n\";\n";
    $php .= "        \$updated++;\n";
    $php .= "    }\n";
    $php .= "}\n\n";
    $php .= "echo \"\\nMigration completed: \$updated beaches updated\\n\";\n";

    file_put_contents($migrationFile, $php);
    echo colorize("  Generated: {$migrationFile}\n", 'green');
}

// ── Main execution ──────────────────────────────────────────────────────────

echo "\n" . colorize("Beach Coordinate Verification v2", 'cyan') . "\n";
echo str_repeat('=', 60) . "\n";

$db = getDb();

$p1Results = [];
$p2Results = [];

if ($phase === 'all' || $phase === '1') {
    $p1Results = runPhase1($db, $apiKey, $limit, $outputDir, $resume);
}

if ($phase === 'all' || $phase === '2') {
    $p2Results = runPhase2($db, $apiKey, $limit, $outputDir, $resume);
}

if ($phase === 'all' || $phase === '3') {
    $report = runPhase3($outputDir);
}

if ($generateFix) {
    generateMigration($outputDir);
}

echo "\nDone.\n";
