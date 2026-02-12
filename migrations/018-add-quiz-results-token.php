<?php
/**
 * Migration: Add share token to quiz_results
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: quiz_results token\n";

$db = getDb();

$result = $db->query("PRAGMA table_info(quiz_results)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('token', $columns, true)) {
    $db->exec("ALTER TABLE quiz_results ADD COLUMN token TEXT");
    echo "✓ Added token column\n";
}

if (!in_array('ip_hash', $columns, true)) {
    $db->exec("ALTER TABLE quiz_results ADD COLUMN ip_hash TEXT");
    echo "✓ Added ip_hash column\n";
}

$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_quiz_results_token ON quiz_results(token)");
echo "✓ Ensured token index\n";

echo "✅ Migration completed: quiz_results token\n";

