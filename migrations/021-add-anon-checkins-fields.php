<?php
/**
 * Migration: Add anonymous check-in support fields.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: anonymous check-in fields\n";

$db = getDb();

$result = $db->query("PRAGMA table_info(beach_checkins)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('anon_id', $columns, true)) {
    $db->exec("ALTER TABLE beach_checkins ADD COLUMN anon_id TEXT");
    echo "✓ Added anon_id\n";
}

if (!in_array('ip_hash', $columns, true)) {
    $db->exec("ALTER TABLE beach_checkins ADD COLUMN ip_hash TEXT");
    echo "✓ Added ip_hash\n";
}

if (!in_array('user_agent_hash', $columns, true)) {
    $db->exec("ALTER TABLE beach_checkins ADD COLUMN user_agent_hash TEXT");
    echo "✓ Added user_agent_hash\n";
}

$db->exec("CREATE INDEX IF NOT EXISTS idx_checkins_ip_hash ON beach_checkins(ip_hash)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_checkins_beach_ip_recent ON beach_checkins(beach_id, ip_hash, created_at DESC)");

echo "✅ Migration completed: anonymous check-in fields\n";

