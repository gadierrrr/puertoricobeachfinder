<?php
/**
 * Migration: Add user reviews and photos tables
 */

require_once __DIR__ . '/../inc/db.php';

echo "Running migration: Add reviews and photos tables\n";

$db = getDB();

// Create beach_reviews table
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        beach_id TEXT NOT NULL,
        user_id INTEGER NOT NULL,
        rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title TEXT,
        review_text TEXT NOT NULL,
        visit_date TEXT,
        visited_with TEXT,
        pros TEXT,
        cons TEXT,
        helpful_count INTEGER DEFAULT 0,
        status TEXT DEFAULT 'published' CHECK (status IN ('published', 'pending', 'hidden')),
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
echo "- Created beach_reviews table\n";

// Create indexes for reviews
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_beach ON beach_reviews(beach_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user ON beach_reviews(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_rating ON beach_reviews(rating)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_status ON beach_reviews(status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_created ON beach_reviews(created_at DESC)");
echo "- Created beach_reviews indexes\n";

// Create review_votes table (for "helpful" votes)
$db->exec("
    CREATE TABLE IF NOT EXISTS review_votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        review_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        vote_type TEXT DEFAULT 'helpful' CHECK (vote_type IN ('helpful', 'unhelpful')),
        created_at TEXT NOT NULL,
        FOREIGN KEY (review_id) REFERENCES beach_reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(review_id, user_id)
    )
");
echo "- Created review_votes table\n";

// Create beach_photos table
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        beach_id TEXT NOT NULL,
        user_id INTEGER NOT NULL,
        review_id INTEGER,
        filename TEXT NOT NULL,
        original_filename TEXT,
        file_size INTEGER,
        mime_type TEXT,
        width INTEGER,
        height INTEGER,
        caption TEXT,
        is_featured INTEGER DEFAULT 0,
        status TEXT DEFAULT 'published' CHECK (status IN ('published', 'pending', 'hidden')),
        created_at TEXT NOT NULL,
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (review_id) REFERENCES beach_reviews(id) ON DELETE SET NULL
    )
");
echo "- Created beach_photos table\n";

// Create indexes for photos
$db->exec("CREATE INDEX IF NOT EXISTS idx_photos_beach ON beach_photos(beach_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photos_user ON beach_photos(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photos_review ON beach_photos(review_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photos_status ON beach_photos(status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photos_featured ON beach_photos(is_featured)");
echo "- Created beach_photos indexes\n";

// Create review_responses table (for owner responses)
$db->exec("
    CREATE TABLE IF NOT EXISTS review_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        review_id INTEGER NOT NULL UNIQUE,
        user_id INTEGER NOT NULL,
        response_text TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        FOREIGN KEY (review_id) REFERENCES beach_reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
echo "- Created review_responses table\n";

echo "\nMigration complete!\n";
