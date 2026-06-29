<?php
/**
 * Migration 033: Add location_type column to beaches
 *
 * The codebase queries `beaches.location_type` in several places
 * (public/index.php, beaches-by-tag.php, beaches-near.php, helpers.php,
 * collection_query.php) but no migration added the column. This backfills
 * that drift: idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Add location_type column to beaches\n";

$db = getDb();

$columns = $db->query('PRAGMA table_info(beaches)');
$hasColumn = false;
while ($columns && ($col = $columns->fetchArray(SQLITE3_ASSOC))) {
    if (($col['name'] ?? '') === 'location_type') {
        $hasColumn = true;
        break;
    }
}

if ($hasColumn) {
    echo "Column location_type already exists — nothing to do.\n";
    return;
}

$db->exec("ALTER TABLE beaches ADD COLUMN location_type TEXT DEFAULT 'beach'");

// Backfill: existing rows get 'beach' via the default, but set explicitly
// so downstream queries matching `location_type = 'beach'` (without the
// OR IS NULL branch) stay safe too.
$db->exec("UPDATE beaches SET location_type = 'beach' WHERE location_type IS NULL");

echo "Added location_type column and backfilled " .
    (int)$db->querySingle('SELECT COUNT(*) FROM beaches WHERE location_type = \'beach\'') .
    " rows.\n";
