<?php
/**
 * Migration: Add external_urls column to beaches table
 * Run once to update the database schema
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Add external_urls column\n";

try {
    $db = getDb();

    // Check if column exists before adding
    $result = $db->query("PRAGMA table_info(beaches)");
    $columns = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }

    if (!in_array('external_urls', $columns)) {
        $db->exec("ALTER TABLE beaches ADD COLUMN external_urls TEXT DEFAULT NULL");
        echo "✓ Added external_urls column to beaches\n";
        echo "  This column stores JSON with external links (Wikipedia, TripAdvisor, Google Maps, etc.)\n";
        echo "  Example format: {\"wikipedia\":\"https://...\",\"tripadvisor\":\"https://...\",\"google_maps\":\"https://...\"}\n";
    } else {
        echo "⚠ external_urls column already exists, skipping\n";
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
