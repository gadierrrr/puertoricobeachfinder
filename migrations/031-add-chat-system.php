<?php
/**
 * Migration 031: Add chat system tables
 *
 * Creates: chat_channels, chat_messages, chat_reports, chat_participants, chat_blocklist
 * Modifies: users (adds chat_muted_until, chat_banned, chat_message_count)
 * Seeds: general chat channel + default blocklist entries
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDB();

echo "=== Migration 031: Chat System ===\n\n";

// --- chat_channels ---
$db->exec("
    CREATE TABLE IF NOT EXISTS chat_channels (
        id TEXT PRIMARY KEY,
        type TEXT NOT NULL CHECK(type IN ('general', 'beach', 'dm')),
        beach_id TEXT,
        participant_a TEXT,
        participant_b TEXT,
        last_message_at TEXT,
        message_count INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
        FOREIGN KEY (participant_a) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (participant_b) REFERENCES users(id) ON DELETE CASCADE
    )
");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_chat_channels_beach ON chat_channels(type, beach_id) WHERE beach_id IS NOT NULL");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_chat_channels_dm ON chat_channels(participant_a, participant_b) WHERE participant_a IS NOT NULL AND participant_b IS NOT NULL");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_channels_type ON chat_channels(type)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_channels_last_msg ON chat_channels(last_message_at DESC)");
echo "✓ Created chat_channels table\n";

// --- chat_messages ---
$db->exec("
    CREATE TABLE IF NOT EXISTS chat_messages (
        id TEXT PRIMARY KEY,
        channel_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        body TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('published', 'flagged', 'hidden', 'deleted')),
        moderation_result TEXT,
        report_count INTEGER NOT NULL DEFAULT 0,
        ip_hash TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT,
        FOREIGN KEY (channel_id) REFERENCES chat_channels(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_channel ON chat_messages(channel_id, created_at ASC)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_user ON chat_messages(user_id, created_at DESC)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_status ON chat_messages(status)");
echo "✓ Created chat_messages table\n";

// --- chat_reports ---
$db->exec("
    CREATE TABLE IF NOT EXISTS chat_reports (
        id TEXT PRIMARY KEY,
        message_id TEXT NOT NULL,
        reporter_user_id TEXT NOT NULL,
        reason TEXT NOT NULL CHECK(reason IN ('spam', 'harassment', 'inappropriate', 'misinformation', 'other')),
        details TEXT,
        resolved_at TEXT,
        resolved_by TEXT,
        resolution TEXT CHECK(resolution IS NULL OR resolution IN ('dismissed', 'hidden', 'user_warned', 'user_banned')),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
        FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (resolved_by) REFERENCES users(id)
    )
");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_chat_reports_unique ON chat_reports(message_id, reporter_user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_reports_pending ON chat_reports(resolved_at) WHERE resolved_at IS NULL");
echo "✓ Created chat_reports table\n";

// --- chat_participants ---
$db->exec("
    CREATE TABLE IF NOT EXISTS chat_participants (
        channel_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        last_read_at TEXT,
        joined_at TEXT NOT NULL DEFAULT (datetime('now')),
        PRIMARY KEY (channel_id, user_id),
        FOREIGN KEY (channel_id) REFERENCES chat_channels(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
$db->exec("CREATE INDEX IF NOT EXISTS idx_chat_participants_user ON chat_participants(user_id)");
echo "✓ Created chat_participants table\n";

// --- chat_blocklist ---
$db->exec("
    CREATE TABLE IF NOT EXISTS chat_blocklist (
        id TEXT PRIMARY KEY,
        pattern TEXT NOT NULL UNIQUE,
        is_regex INTEGER NOT NULL DEFAULT 0,
        severity TEXT NOT NULL DEFAULT 'block' CHECK(severity IN ('block', 'flag')),
        created_by TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )
");
echo "✓ Created chat_blocklist table\n";

// --- Add columns to users ---
$cols = [];
$result = $db->query("PRAGMA table_info(users)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $cols[] = $row['name'];
}

if (!in_array('chat_muted_until', $cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN chat_muted_until TEXT");
    echo "✓ Added chat_muted_until column to users\n";
}
if (!in_array('chat_banned', $cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN chat_banned INTEGER DEFAULT 0");
    echo "✓ Added chat_banned column to users\n";
}
if (!in_array('chat_message_count', $cols)) {
    $db->exec("ALTER TABLE users ADD COLUMN chat_message_count INTEGER DEFAULT 0");
    echo "✓ Added chat_message_count column to users\n";
}

// --- Seed: General Chat channel ---
$existing = $db->querySingle("SELECT id FROM chat_channels WHERE id = '00000000-0000-4000-8000-000000000001'");
if (!$existing) {
    $db->exec("
        INSERT INTO chat_channels (id, type, created_at)
        VALUES ('00000000-0000-4000-8000-000000000001', 'general', datetime('now'))
    ");
    echo "✓ Seeded general chat channel\n";
}

// --- Seed: Default blocklist ---
$defaultPatterns = [
    // These are severity='block' patterns
    ['pattern' => '\\b(viagra|cialis|crypto|casino|lottery)\\b', 'is_regex' => 1, 'severity' => 'block'],
    ['pattern' => '\\b(buy now|click here|free money|act now)\\b', 'is_regex' => 1, 'severity' => 'block'],
    ['pattern' => '\\b(whatsapp me|telegram me|dm me for)\\b', 'is_regex' => 1, 'severity' => 'flag'],
];

foreach ($defaultPatterns as $p) {
    $existing = $db->querySingle("SELECT id FROM chat_blocklist WHERE pattern = '" . $db->escapeString($p['pattern']) . "'");
    if (!$existing) {
        $id = bin2hex(random_bytes(16));
        $stmt = $db->prepare("INSERT INTO chat_blocklist (id, pattern, is_regex, severity) VALUES (:id, :pattern, :is_regex, :severity)");
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->bindValue(':pattern', $p['pattern'], SQLITE3_TEXT);
        $stmt->bindValue(':is_regex', $p['is_regex'], SQLITE3_INTEGER);
        $stmt->bindValue(':severity', $p['severity'], SQLITE3_TEXT);
        $stmt->execute();
    }
}
echo "✓ Seeded default blocklist patterns\n";

echo "\n=== Migration 031 complete ===\n";
