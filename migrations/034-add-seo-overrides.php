<?php
/**
 * Migration 034: Add per-beach SEO override columns to beaches
 *
 * Adds hand-written title-tag and meta-description overrides (English +
 * Spanish) that take precedence over the auto-generated values in
 * public/beach.php. Leaving a column NULL keeps the existing auto behavior.
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Add SEO override columns to beaches\n";

$db = getDb();

$existing = [];
$columns = $db->query('PRAGMA table_info(beaches)');
while ($columns && ($col = $columns->fetchArray(SQLITE3_ASSOC))) {
    $existing[$col['name'] ?? ''] = true;
}

$toAdd = ['seo_title', 'seo_title_es', 'seo_description', 'seo_description_es'];
$added = 0;
foreach ($toAdd as $colName) {
    if (isset($existing[$colName])) {
        echo "Column {$colName} already exists — skipping.\n";
        continue;
    }
    $db->exec("ALTER TABLE beaches ADD COLUMN {$colName} TEXT DEFAULT NULL");
    echo "Added column {$colName}.\n";
    $added++;
}

echo "Done. Added {$added} new column(s).\n";
