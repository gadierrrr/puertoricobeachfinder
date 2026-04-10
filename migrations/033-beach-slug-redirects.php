<?php
/**
 * Migration 033: Beach slug redirects
 *
 * Creates the beach_slug_redirects table that powers permanent 301s
 * from old beach URLs to current ones. The table stores
 * (old_slug → beach_id), so chained renames automatically resolve to
 * the current slug at lookup time.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDB();

echo "=== Migration 033: Beach Slug Redirects ===\n\n";

$db->exec("
    CREATE TABLE IF NOT EXISTS beach_slug_redirects (
        old_slug TEXT PRIMARY KEY,
        beach_id TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
    )
");
echo "✓ Created beach_slug_redirects table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_beach_slug_redirects_beach_id ON beach_slug_redirects(beach_id)");
echo "✓ Created idx_beach_slug_redirects_beach_id\n";

echo "\nMigration 033 complete.\n";
