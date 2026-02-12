<?php
/**
 * Migration: SEO fixes
 * 1. Publish 2 draft beaches
 * 2. Replace em dashes in content sections and descriptions
 * 3. Fix 204 generic beach descriptions
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: SEO fixes\n";

try {
    $db = getDb();
    $db->exec("BEGIN TRANSACTION");

    // 1. Publish draft beaches
    $stmt = $db->prepare("UPDATE beaches SET publish_status = 'published' WHERE publish_status = 'draft'");
    $stmt->execute();
    $published = $db->changes();
    echo "Published $published draft beaches\n";

    // 2. Replace em dashes in beach_content_sections
    $db->exec("UPDATE beach_content_sections SET content = REPLACE(content, ' — ', ', ')");
    $pass1 = $db->changes();
    // Second pass: catch em dashes without surrounding spaces
    $db->exec("UPDATE beach_content_sections SET content = REPLACE(content, '—', '--')");
    $pass2 = $db->changes();
    echo "Replaced em dashes in content sections: $pass1 (spaced) + $pass2 (remaining)\n";

    // Replace em dashes in beach descriptions
    $db->exec("UPDATE beaches SET description = REPLACE(description, ' — ', ', ')");
    $d1 = $db->changes();
    $db->exec("UPDATE beaches SET description = REPLACE(description, '—', '--')");
    $d2 = $db->changes();
    echo "Replaced em dashes in descriptions: $d1 (spaced) + $d2 (remaining)\n";

    // 3. Fix generic descriptions
    $generic = $db->query("SELECT id, name, municipality, description FROM beaches WHERE description LIKE '%most visually stunning coastal destinations%'");
    $fixedCount = 0;
    $updateStmt = $db->prepare("UPDATE beaches SET description = :desc WHERE id = :id");

    while ($row = $generic->fetchArray(SQLITE3_ASSOC)) {
        $desc = $row['description'];
        // Extract the notes after the generic opener
        $marker = 'coastal destinations. ';
        $pos = strpos($desc, $marker);
        if ($pos === false) {
            continue;
        }
        $notes = substr($desc, $pos + strlen($marker));

        // Clean source citations like (OSM; Google; DRNA) or (Mapcarta; Google)
        $notes = preg_replace('/\s*\([^)]*(?:OSM|Google|Mapcarta|DRNA|NOAA|Surf maps|iNaturalist|eBird|AllTrails|Waze)[^)]*\)\s*/', ' ', $notes);
        $notes = trim($notes, " \t\n\r\0\x0B.");

        if (empty($notes)) {
            // If no meaningful notes remain, create a simple description
            $newDesc = $row['name'] . ' in ' . $row['municipality'] . ', Puerto Rico.';
        } else {
            // Capitalize first letter of notes if needed
            $notes = ucfirst($notes);
            // Ensure notes end with a period
            if (!str_ends_with($notes, '.')) {
                $notes .= '.';
            }
            $newDesc = $row['name'] . ' in ' . $row['municipality'] . ', Puerto Rico. ' . $notes;
        }

        $updateStmt->bindValue(':desc', $newDesc, SQLITE3_TEXT);
        $updateStmt->bindValue(':id', $row['id'], SQLITE3_TEXT);
        $updateStmt->execute();
        $updateStmt->reset();
        $fixedCount++;
    }

    echo "Fixed $fixedCount generic descriptions\n";

    $db->exec("COMMIT");
    echo "Migration complete!\n";
} catch (Exception $e) {
    if (isset($db)) {
        $db->exec("ROLLBACK");
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
