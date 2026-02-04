<?php
/**
 * Migration: Ensure Google OAuth columns exist on users table
 * Adds users.google_id and users.avatar_url when missing.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Ensure users OAuth columns\n";

try {
    $db = getDb();

    $result = $db->query("PRAGMA table_info(users)");
    $columns = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }

    if (!in_array('google_id', $columns, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
        echo "✓ Added google_id column to users\n";
    } else {
        echo "⚠ google_id column already exists, skipping\n";
    }

    if (!in_array('avatar_url', $columns, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN avatar_url TEXT");
        echo "✓ Added avatar_url column to users\n";
    } else {
        echo "⚠ avatar_url column already exists, skipping\n";
    }

    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_google_id ON users(google_id)");
    echo "✓ Ensured index on users.google_id\n";

    echo "\n✅ Migration completed successfully!\n";
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
