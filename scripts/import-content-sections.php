#!/usr/bin/env php
<?php
/**
 * Import content section JSON files into the database
 * Usage: php import-content-sections.php /tmp/content_batch_*.json
 *
 * Each JSON file should contain an array of objects:
 * [
 *   {
 *     "beach_id": "uuid",
 *     "sections": [
 *       {"section_type": "history", "heading": "...", "content": "..."},
 *       ...
 *     ]
 *   }
 * ]
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../inc/db.php';

$files = array_slice($argv, 1);
if (empty($files)) {
    $files = glob('/tmp/content_batch_*.json');
}

if (empty($files)) {
    die("No content files found.\n");
}

$db = getDB();
$totalBeaches = 0;
$totalSections = 0;
$totalSuccess = 0;
$totalErrors = 0;

$validTypes = ['history', 'best_time', 'getting_there', 'what_to_bring', 'nearby', 'local_tips'];

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

        $beach = queryOne("SELECT id, name FROM beaches WHERE id = ?", [$beachId]);
        if (!$beach) {
            echo "  SKIP: beach not found: {$beachId}\n";
            $totalErrors++;
            continue;
        }

        $sections = $entry['sections'] ?? [];
        if (empty($sections)) {
            echo "  SKIP: no sections for {$beach['name']}\n";
            $totalErrors++;
            continue;
        }

        $db->exec('BEGIN TRANSACTION');

        try {
            // Delete existing draft sections for this beach
            execute("DELETE FROM beach_content_sections WHERE beach_id = ? AND status = 'draft'", [$beachId]);

            $displayOrder = 1;
            foreach ($sections as $section) {
                $sectionType = $section['section_type'] ?? '';
                if (!in_array($sectionType, $validTypes)) {
                    continue;
                }

                $content = $section['content'] ?? '';
                $heading = $section['heading'] ?? '';
                $wordCount = str_word_count($content);

                // Generate UUID
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                execute(
                    "INSERT INTO beach_content_sections (id, beach_id, section_type, heading, content, word_count, display_order, status, generated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', datetime('now'))",
                    [$id, $beachId, $sectionType, $heading, $content, $wordCount, $displayOrder]
                );

                $displayOrder++;
                $totalSections++;
            }

            $db->exec('COMMIT');
            $totalSuccess++;

        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $totalErrors++;
            echo "  ERROR: {$beach['name']}: {$e->getMessage()}\n";
        }
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "CONTENT IMPORT COMPLETE\n";
echo str_repeat('=', 50) . "\n";
echo "Total beaches:   {$totalBeaches}\n";
echo "Succeeded:       {$totalSuccess}\n";
echo "Errors:          {$totalErrors}\n";
echo "Total sections:  {$totalSections}\n";

// Show stats
$sectionsByStatus = query("SELECT status, COUNT(*) as c FROM beach_content_sections GROUP BY status");
echo "\nSections by status:\n";
foreach ($sectionsByStatus as $row) {
    echo "  {$row['status']}: {$row['c']}\n";
}

$beachesWithSections = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_content_sections")['c'];
echo "Beaches with sections: {$beachesWithSections}\n";
