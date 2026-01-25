<?php
/**
 * Migration: Add beach_images table for admin-managed beach photos
 *
 * Run with: php migrations/009-admin-beach-images.php
 */

require_once __DIR__ . '/../inc/db.php';

echo "Running migration 009: Admin Beach Images\n";

$db = getDb();

// Create beach_images table
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        beach_id TEXT NOT NULL,
        filename TEXT NOT NULL,
        original_filename TEXT,
        original_format TEXT,
        file_size INTEGER,
        original_size INTEGER,
        mime_type TEXT DEFAULT 'image/webp',
        width INTEGER,
        height INTEGER,
        position INTEGER DEFAULT 0,
        is_cover INTEGER DEFAULT 0,
        alt_text TEXT,
        optimization_savings INTEGER DEFAULT 0,
        created_at TEXT NOT NULL,
        uploaded_by TEXT,
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    )
");

echo "  - Created beach_images table\n";

// Create indexes for efficient queries
$db->exec("CREATE INDEX IF NOT EXISTS idx_beach_images_beach_id ON beach_images(beach_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_beach_images_is_cover ON beach_images(beach_id, is_cover)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_beach_images_position ON beach_images(beach_id, position)");

echo "  - Created indexes\n";

// Create upload directories
$uploadDir = __DIR__ . '/../uploads/admin/beaches';
$thumbsDir = $uploadDir . '/thumbs';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "  - Created upload directory: $uploadDir\n";
}

if (!is_dir($thumbsDir)) {
    mkdir($thumbsDir, 0755, true);
    echo "  - Created thumbnails directory: $thumbsDir\n";
}

echo "\nMigration 009 completed successfully!\n";
