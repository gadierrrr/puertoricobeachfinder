<?php
/**
 * Beach Coordinate Verification Script
 * Uses Google Places API to verify database coordinates against Google Maps
 *
 * Usage: php scripts/verify-coordinates.php [--fix] [--limit=N]
 *   --fix    Generate migration file for corrections
 *   --limit  Only check N beaches (for testing)
 */

require_once __DIR__ . '/../inc/db.php';

// Google Maps API Key
$apiKey = 'AIzaSyBJzRm5Qpwxmep93ZoPdXAb8w_4zbNomps';

// Parse command line arguments
$args = getopt('', ['fix', 'limit:']);
$generateFix = isset($args['fix']);
$limit = isset($args['limit']) ? (int)$args['limit'] : null;

/**
 * Calculate distance between two coordinates using Haversine formula
 * @return float Distance in meters
 */
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth radius in meters
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dphi = deg2rad($lat2 - $lat1);
    $dlambda = deg2rad($lon2 - $lon1);

    $a = sin($dphi/2) * sin($dphi/2) +
         cos($phi1) * cos($phi2) * sin($dlambda/2) * sin($dlambda/2);

    return 2 * $R * atan2(sqrt($a), sqrt(1-$a));
}

/**
 * Get priority level based on distance
 */
function getPriority($distance) {
    if ($distance > 2000) return 'CRITICAL';
    if ($distance > 500) return 'HIGH';
    if ($distance > 100) return 'MEDIUM';
    return 'OK';
}

/**
 * Search Google Places API for a beach
 */
function searchGooglePlaces($beachName, $apiKey) {
    $query = urlencode($beachName . ' Puerto Rico beach');
    $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
         . "?input={$query}"
         . "&inputtype=textquery"
         . "&fields=name,geometry,formatted_address,place_id"
         . "&locationbias=circle:100000@18.2208,-66.5901" // Center of Puerto Rico
         . "&key={$apiKey}";

    $response = file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['candidates'])) {
        $place = $data['candidates'][0];
        return [
            'found' => true,
            'name' => $place['name'],
            'lat' => $place['geometry']['location']['lat'],
            'lng' => $place['geometry']['location']['lng'],
            'address' => $place['formatted_address'] ?? '',
            'place_id' => $place['place_id']
        ];
    } elseif ($data['status'] === 'ZERO_RESULTS') {
        return ['found' => false, 'reason' => 'No results'];
    } else {
        return ['error' => $data['status'] . ': ' . ($data['error_message'] ?? 'Unknown error')];
    }
}

// Get all beaches from database
$db = getDb();
$sql = "SELECT id, name, lat, lng, municipality FROM beaches ORDER BY name";
$result = $db->query($sql);

$beaches = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $beaches[] = $row;
}

if ($limit) {
    $beaches = array_slice($beaches, 0, $limit);
}

echo "Beach Coordinate Verification\n";
echo "==============================\n";
echo "Checking " . count($beaches) . " beaches against Google Places API\n\n";

$corrections = [];
$stats = ['OK' => 0, 'MEDIUM' => 0, 'HIGH' => 0, 'CRITICAL' => 0, 'NOT_FOUND' => 0, 'ERROR' => 0];

foreach ($beaches as $i => $beach) {
    $progress = sprintf("[%d/%d]", $i + 1, count($beaches));

    // Rate limiting - Google allows 10 QPS, we'll do 5 to be safe
    usleep(200000); // 200ms delay

    $result = searchGooglePlaces($beach['name'], $apiKey);

    if (isset($result['error'])) {
        echo "{$progress} ✗ {$beach['name']} - ERROR: {$result['error']}\n";
        $stats['ERROR']++;
        continue;
    }

    if (!$result['found']) {
        echo "{$progress} ? {$beach['name']} - NOT FOUND on Google Maps\n";
        $stats['NOT_FOUND']++;
        continue;
    }

    $distance = haversine($beach['lat'], $beach['lng'], $result['lat'], $result['lng']);
    $priority = getPriority($distance);
    $stats[$priority]++;

    if ($priority === 'OK') {
        echo "{$progress} ✓ {$beach['name']} - OK (" . round($distance) . "m)\n";
    } else {
        $distStr = $distance > 1000 ? sprintf("%.1fkm", $distance/1000) : sprintf("%.0fm", $distance);
        echo "{$progress} ⚠ {$beach['name']} - {$priority} ({$distStr} off)\n";
        echo "         DB: {$beach['lat']}, {$beach['lng']}\n";
        echo "         Google: {$result['lat']}, {$result['lng']} ({$result['name']})\n";

        $corrections[] = [
            'id' => $beach['id'],
            'name' => $beach['name'],
            'current_lat' => $beach['lat'],
            'current_lng' => $beach['lng'],
            'correct_lat' => $result['lat'],
            'correct_lng' => $result['lng'],
            'google_name' => $result['name'],
            'distance' => $distance,
            'priority' => $priority
        ];
    }
}

// Summary
echo "\n==============================\n";
echo "SUMMARY\n";
echo "==============================\n";
echo "✓ OK (<100m):      {$stats['OK']}\n";
echo "⚠ MEDIUM (100-500m): {$stats['MEDIUM']}\n";
echo "⚠ HIGH (500m-2km):   {$stats['HIGH']}\n";
echo "✗ CRITICAL (>2km):   {$stats['CRITICAL']}\n";
echo "? NOT FOUND:         {$stats['NOT_FOUND']}\n";
echo "✗ ERROR:             {$stats['ERROR']}\n";
echo "\nTotal needing correction: " . count($corrections) . "\n";

// Generate migration file if requested
if ($generateFix && !empty($corrections)) {
    $migrationNum = '008';
    $migrationFile = __DIR__ . "/../migrations/{$migrationNum}-update-beach-coordinates-api-verified.php";

    $php = "<?php\n";
    $php .= "/**\n";
    $php .= " * Migration: Update beach coordinates - API Verified\n";
    $php .= " * Auto-generated from Google Places API verification\n";
    $php .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
    $php .= " *\n";
    $php .= " * Corrections: " . count($corrections) . " beaches\n";
    $php .= " * - CRITICAL (>2km): " . count(array_filter($corrections, fn($c) => $c['priority'] === 'CRITICAL')) . "\n";
    $php .= " * - HIGH (500m-2km): " . count(array_filter($corrections, fn($c) => $c['priority'] === 'HIGH')) . "\n";
    $php .= " * - MEDIUM (100-500m): " . count(array_filter($corrections, fn($c) => $c['priority'] === 'MEDIUM')) . "\n";
    $php .= " */\n\n";
    $php .= "require_once __DIR__ . '/../inc/db.php';\n\n";
    $php .= "\$corrections = [\n";

    foreach ($corrections as $c) {
        $name = addslashes($c['name']);
        $php .= "    // {$c['priority']}: " . round($c['distance']) . "m off - Google: \"{$c['google_name']}\"\n";
        $php .= "    '{$name}' => [{$c['correct_lat']}, {$c['correct_lng']}],\n";
    }

    $php .= "];\n\n";
    $php .= "\$db = getDb();\n";
    $php .= "\$updated = 0;\n\n";
    $php .= "foreach (\$corrections as \$name => \$coords) {\n";
    $php .= "    \$stmt = \$db->prepare(\"UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE name = :name\");\n";
    $php .= "    \$stmt->bindValue(':lat', \$coords[0], SQLITE3_FLOAT);\n";
    $php .= "    \$stmt->bindValue(':lng', \$coords[1], SQLITE3_FLOAT);\n";
    $php .= "    \$stmt->bindValue(':name', \$name, SQLITE3_TEXT);\n";
    $php .= "    \$stmt->execute();\n";
    $php .= "    if (\$db->changes() > 0) {\n";
    $php .= "        echo \"✓ Updated: \$name\\n\";\n";
    $php .= "        \$updated++;\n";
    $php .= "    }\n";
    $php .= "}\n\n";
    $php .= "echo \"\\n✅ Migration completed: \$updated beaches updated\\n\";\n";

    file_put_contents($migrationFile, $php);
    echo "\n✅ Generated migration: {$migrationFile}\n";
}

// Output CSV for review
if (!empty($corrections)) {
    $csvFile = __DIR__ . '/../data/coordinate-corrections.csv';
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, ['Name', 'Current Lat', 'Current Lng', 'Correct Lat', 'Correct Lng', 'Distance (m)', 'Priority', 'Google Name']);
    foreach ($corrections as $c) {
        fputcsv($fp, [
            $c['name'],
            $c['current_lat'],
            $c['current_lng'],
            $c['correct_lat'],
            $c['correct_lng'],
            round($c['distance']),
            $c['priority'],
            $c['google_name']
        ]);
    }
    fclose($fp);
    echo "📄 Saved corrections to: {$csvFile}\n";
}
