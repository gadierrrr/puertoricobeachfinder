<?php
/**
 * Migration: Add user progress, explorer levels, and preferences
 * Quick wins for UX improvements
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: User progress and preferences\n";

$db = getDb();

// ============================================================
// ADD EXPLORER LEVEL COLUMNS TO USERS
// ============================================================

$result = $db->query("PRAGMA table_info(users)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('explorer_level', $columns)) {
    $db->exec("ALTER TABLE users ADD COLUMN explorer_level TEXT DEFAULT 'newcomer'");
    echo "✓ Added explorer_level column to users\n";
}

if (!in_array('total_beaches_visited', $columns)) {
    $db->exec("ALTER TABLE users ADD COLUMN total_beaches_visited INTEGER DEFAULT 0");
    echo "✓ Added total_beaches_visited column to users\n";
}

if (!in_array('onboarding_completed', $columns)) {
    $db->exec("ALTER TABLE users ADD COLUMN onboarding_completed INTEGER DEFAULT 0");
    echo "✓ Added onboarding_completed column to users\n";
}

// ============================================================
// USER PREFERENCES TABLE
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS user_preferences (
        user_id TEXT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
        preferred_activities TEXT,  -- JSON array: ['snorkeling', 'surfing']
        preferred_vibe TEXT,        -- 'relaxing', 'adventurous', 'family'
        notifications_enabled INTEGER DEFAULT 1,
        weekly_digest INTEGER DEFAULT 1,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✓ Created user_preferences table\n";

// ============================================================
// UPDATE EXISTING USERS' BEACHES VISITED COUNT
// ============================================================
echo "Updating beaches visited counts...\n";

// Count unique beaches from check-ins for each user
$db->exec("
    UPDATE users SET total_beaches_visited = (
        SELECT COUNT(DISTINCT beach_id)
        FROM beach_checkins
        WHERE beach_checkins.user_id = users.id
    )
");
echo "✓ Updated beaches visited counts from check-ins\n";

// ============================================================
// UPDATE EXPLORER LEVELS BASED ON BEACHES VISITED
// ============================================================
echo "Updating explorer levels...\n";

// Newcomer: 0-2 beaches
// Explorer: 3-10 beaches
// Guide: 11-25 beaches
// Expert: 26-50 beaches
// Legend: 50+ beaches

$db->exec("
    UPDATE users SET explorer_level = CASE
        WHEN total_beaches_visited >= 50 THEN 'legend'
        WHEN total_beaches_visited >= 26 THEN 'expert'
        WHEN total_beaches_visited >= 11 THEN 'guide'
        WHEN total_beaches_visited >= 3 THEN 'explorer'
        ELSE 'newcomer'
    END
");
echo "✓ Updated explorer levels\n";

echo "\n✅ Migration completed successfully!\n";
