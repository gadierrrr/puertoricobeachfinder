<?php
/**
 * Migration: Add beach check-ins table
 *
 * Allows users to report real-time conditions at beaches
 *
 * Run: php migrations/002-add-beach-checkins.php
 */

require_once __DIR__ . '/../inc/db.php';

echo "Running migration: Add beach check-ins table\n";

$db = getDB();

// Create beach_checkins table
$db->exec('CREATE TABLE IF NOT EXISTS beach_checkins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    user_id TEXT,
    crowd_level TEXT,
    parking_status TEXT,
    water_condition TEXT,
    sargassum_level TEXT,
    weather_actual TEXT,
    notes TEXT,
    photo_url TEXT,
    is_verified INTEGER DEFAULT 0,
    created_at TEXT,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)');

echo "- Created beach_checkins table\n";

$db->exec('CREATE INDEX IF NOT EXISTS idx_checkins_beach ON beach_checkins(beach_id)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_checkins_created ON beach_checkins(created_at DESC)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_checkins_user ON beach_checkins(user_id)');

echo "- Created beach_checkins indexes\n";

// Create beach_lists table for custom collections
$db->exec('CREATE TABLE IF NOT EXISTS beach_lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_public INTEGER DEFAULT 0,
    slug TEXT UNIQUE,
    created_at TEXT,
    updated_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)');

echo "- Created beach_lists table\n";

$db->exec('CREATE INDEX IF NOT EXISTS idx_lists_user ON beach_lists(user_id)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_lists_public ON beach_lists(is_public)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_lists_slug ON beach_lists(slug)');

echo "- Created beach_lists indexes\n";

// Create beach_list_items table
$db->exec('CREATE TABLE IF NOT EXISTS beach_list_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id INTEGER NOT NULL,
    beach_id TEXT NOT NULL,
    position INTEGER DEFAULT 0,
    notes TEXT,
    added_at TEXT,
    FOREIGN KEY (list_id) REFERENCES beach_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    UNIQUE(list_id, beach_id)
)');

echo "- Created beach_list_items table\n";

$db->exec('CREATE INDEX IF NOT EXISTS idx_list_items_list ON beach_list_items(list_id)');

echo "- Created beach_list_items indexes\n";

echo "\nMigration complete!\n";
