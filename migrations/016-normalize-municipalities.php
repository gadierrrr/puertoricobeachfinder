<?php
/**
 * Migration: Normalize accented municipality values
 * Converts accented characters to ASCII equivalents so municipality slugs
 * match the Nginx URL pattern [a-z-]+ and the MUNICIPALITIES constant.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Normalize accented municipality values\n";

try {
    $db = getDb();
    $db->exec("BEGIN TRANSACTION");

    $mappings = [
        'Cataño'    => 'Catano',
        'Guánica'   => 'Guanica',
        'Loíza'     => 'Loiza',
        'Manatí'    => 'Manati',
        'Mayagüez'  => 'Mayaguez',
        'Peñuelas'  => 'Penuelas',
        'Rincón'    => 'Rincon',
        'Río Grande' => 'Rio Grande',
    ];

    $stmt = $db->prepare("UPDATE beaches SET municipality = :new WHERE municipality = :old");
    $totalUpdated = 0;

    foreach ($mappings as $accented => $normalized) {
        $stmt->bindValue(':new', $normalized, SQLITE3_TEXT);
        $stmt->bindValue(':old', $accented, SQLITE3_TEXT);
        $stmt->execute();
        $changed = $db->changes();
        if ($changed > 0) {
            echo "  Updated $changed beaches: '$accented' -> '$normalized'\n";
            $totalUpdated += $changed;
        }
        $stmt->reset();
    }

    $db->exec("COMMIT");
    echo "Migration complete: $totalUpdated beaches updated\n";
} catch (Exception $e) {
    $db->exec("ROLLBACK");
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
