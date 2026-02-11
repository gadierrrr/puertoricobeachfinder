#!/usr/bin/env php
<?php
/**
 * Import enrichment JSON files into the database
 * Usage: php import-enrichment.php /tmp/enrichment_batch_*.json
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/constants.php';

$files = array_slice($argv, 1);
if (empty($files)) {
    // Glob for enrichment files
    $files = glob('/tmp/enrichment_batch_*.json');
}

if (empty($files)) {
    die("No enrichment files found.\n");
}

$db = getDB();
$totalBeaches = 0;
$totalSuccess = 0;
$totalErrors = 0;
$errors = [];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "SKIP: {$file} not found\n";
        continue;
    }

    $json = file_get_contents($file);
    $beaches = json_decode($json, true);

    if (!is_array($beaches)) {
        echo "ERROR: Invalid JSON in {$file}\n";
        $totalErrors++;
        continue;
    }

    echo "Processing " . basename($file) . " (" . count($beaches) . " beaches)...\n";

    foreach ($beaches as $entry) {
        $totalBeaches++;
        $beachId = $entry['beach_id'] ?? null;

        if (!$beachId) {
            echo "  SKIP: missing beach_id\n";
            $totalErrors++;
            continue;
        }

        // Verify beach exists
        $beach = queryOne("SELECT id, name FROM beaches WHERE id = ?", [$beachId]);
        if (!$beach) {
            echo "  SKIP: beach not found: {$beachId}\n";
            $totalErrors++;
            continue;
        }

        $db->exec('BEGIN TRANSACTION');

        try {
            // Tags
            $tags = $entry['tags'] ?? [];
            foreach ($tags as $tag) {
                if (in_array($tag, TAGS)) {
                    execute("INSERT OR IGNORE INTO beach_tags (beach_id, tag) VALUES (?, ?)", [$beachId, $tag]);
                }
            }

            // Amenities
            $amenities = $entry['amenities'] ?? [];
            foreach ($amenities as $amenity) {
                if (in_array($amenity, AMENITIES)) {
                    execute("INSERT OR IGNORE INTO beach_amenities (beach_id, amenity) VALUES (?, ?)", [$beachId, $amenity]);
                }
            }

            // Features
            $features = $entry['features'] ?? [];
            $pos = 1;
            foreach ($features as $feature) {
                if (!empty($feature['title']) && !empty($feature['description'])) {
                    execute(
                        "INSERT INTO beach_features (beach_id, title, description, position) VALUES (?, ?, ?, ?)",
                        [$beachId, $feature['title'], $feature['description'], $pos]
                    );
                    $pos++;
                }
            }

            // Tips
            $tips = $entry['tips'] ?? [];
            $pos = 1;
            foreach ($tips as $tip) {
                if (!empty($tip['category']) && !empty($tip['tip'])) {
                    execute(
                        "INSERT INTO beach_tips (beach_id, category, tip, position) VALUES (?, ?, ?, ?)",
                        [$beachId, $tip['category'], $tip['tip'], $pos]
                    );
                    $pos++;
                }
            }

            // Field data
            $field = $entry['field_data'] ?? [];
            if (!empty($field)) {
                $validAccessLabels = ['short path', '10-min walk', 'moderate hike', 'difficult hike'];
                $accessLabel = in_array($field['access_label'] ?? '', $validAccessLabels)
                    ? $field['access_label'] : 'short path';

                execute(
                    "UPDATE beaches SET best_time = ?, parking_details = ?, safety_info = ?, access_label = ? WHERE id = ?",
                    [
                        $field['best_time'] ?? '',
                        $field['parking_details'] ?? '',
                        $field['safety_info'] ?? '',
                        $accessLabel,
                        $beachId
                    ]
                );
            }

            $db->exec('COMMIT');
            $totalSuccess++;

        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $totalErrors++;
            $errors[] = "{$beach['name']}: {$e->getMessage()}";
            echo "  ERROR: {$beach['name']}: {$e->getMessage()}\n";
        }
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "IMPORT COMPLETE\n";
echo str_repeat('=', 50) . "\n";
echo "Total entries:  {$totalBeaches}\n";
echo "Succeeded:      {$totalSuccess}\n";
echo "Errors:         {$totalErrors}\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach (array_slice($errors, 0, 10) as $e) {
        echo "  - {$e}\n";
    }
}

// Show updated stats
$withTags = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_tags")['c'];
$withAmenities = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_amenities")['c'];
$withFeatures = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_features")['c'];
$withTips = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_tips")['c'];
$withBestTime = queryOne("SELECT COUNT(*) as c FROM beaches WHERE best_time IS NOT NULL AND best_time <> ''")['c'];

echo "\nUpdated Database State:\n";
echo str_repeat('-', 40) . "\n";
echo "With tags:       {$withTags}\n";
echo "With amenities:  {$withAmenities}\n";
echo "With features:   {$withFeatures}\n";
echo "With tips:       {$withTips}\n";
echo "With best_time:  {$withBestTime}\n";
