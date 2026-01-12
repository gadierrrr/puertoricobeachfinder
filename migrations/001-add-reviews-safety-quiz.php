<?php
/**
 * Migration: Add reviews, safety info, and quiz tables
 * Run once to update the database schema
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Reviews, Safety, and Quiz tables\n";

$db = getDb();

// ============================================================
// BEACH REVIEWS TABLE
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_reviews (
        id TEXT PRIMARY KEY,
        beach_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title TEXT,
        review_text TEXT,
        visit_date TEXT,
        visit_type TEXT, -- solo, couple, family, friends, group
        would_recommend INTEGER DEFAULT 1,
        helpful_count INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'published', -- published, pending, hidden
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
echo "✓ Created beach_reviews table\n";

// Review photos
$db->exec("
    CREATE TABLE IF NOT EXISTS review_photos (
        id TEXT PRIMARY KEY,
        review_id TEXT NOT NULL,
        photo_url TEXT NOT NULL,
        caption TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES beach_reviews(id) ON DELETE CASCADE
    )
");
echo "✓ Created review_photos table\n";

// Review helpful votes
$db->exec("
    CREATE TABLE IF NOT EXISTS review_helpful_votes (
        id TEXT PRIMARY KEY,
        review_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(review_id, user_id),
        FOREIGN KEY (review_id) REFERENCES beach_reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
echo "✓ Created review_helpful_votes table\n";

// Indexes for reviews
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_beach ON beach_reviews(beach_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user ON beach_reviews(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_reviews_rating ON beach_reviews(rating)");
echo "✓ Created review indexes\n";

// ============================================================
// BEACH SAFETY INFORMATION
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_safety (
        beach_id TEXT PRIMARY KEY,
        swim_difficulty INTEGER DEFAULT 3 CHECK (swim_difficulty >= 1 AND swim_difficulty <= 5),
        -- 1=Very Easy (pool-like), 2=Easy, 3=Moderate, 4=Challenging, 5=Experts Only

        has_lifeguard INTEGER DEFAULT 0,
        lifeguard_hours TEXT, -- e.g., '9am-5pm weekends'
        lifeguard_seasonal INTEGER DEFAULT 0, -- 1 if only seasonal

        rip_current_risk TEXT DEFAULT 'low', -- low, moderate, high
        typical_wave_height TEXT, -- e.g., '1-3 ft'

        -- Hazards (JSON array or comma-separated)
        hazards TEXT, -- rocks, jellyfish, sea_urchins, strong_currents, boats, etc.

        -- Emergency info
        nearest_hospital TEXT,
        hospital_distance_km REAL,
        emergency_phone TEXT,
        beach_patrol_phone TEXT,

        -- Flags and warnings
        current_flag_color TEXT, -- green, yellow, red, purple (marine life)
        flag_updated_at TEXT,

        -- Child safety
        safe_for_children INTEGER DEFAULT 1,
        children_notes TEXT,

        -- Water quality
        water_quality_rating TEXT, -- excellent, good, fair, poor
        water_quality_updated TEXT,

        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
    )
");
echo "✓ Created beach_safety table\n";

// ============================================================
// BEACH ACCESSIBILITY INFORMATION
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_accessibility (
        beach_id TEXT PRIMARY KEY,

        wheelchair_access TEXT DEFAULT 'none', -- none, partial, full
        wheelchair_path_to_water INTEGER DEFAULT 0,
        beach_wheelchair_available INTEGER DEFAULT 0,
        beach_mat_available INTEGER DEFAULT 0,

        accessible_parking INTEGER DEFAULT 0,
        accessible_parking_spots INTEGER DEFAULT 0,
        accessible_restrooms INTEGER DEFAULT 0,

        parking_to_beach_distance_m INTEGER, -- meters
        terrain_description TEXT, -- e.g., 'Paved path to sand, then soft sand'

        service_animals_allowed INTEGER DEFAULT 1,

        accessibility_notes TEXT,
        verified_date TEXT,

        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
    )
");
echo "✓ Created beach_accessibility table\n";

// ============================================================
// BEACH PARKING INFORMATION
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS beach_parking (
        beach_id TEXT PRIMARY KEY,

        has_parking INTEGER DEFAULT 1,
        parking_type TEXT, -- free, paid, street, private
        parking_cost TEXT, -- e.g., '$5/day' or 'Free'
        parking_spots_estimate INTEGER,

        fills_up_by TEXT, -- e.g., '10am on weekends'
        alternative_parking TEXT, -- description of nearby options

        public_transit_access INTEGER DEFAULT 0,
        transit_details TEXT,

        rideshare_dropoff TEXT, -- description of best dropoff point

        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
    )
");
echo "✓ Created beach_parking table\n";

// ============================================================
// WEATHER CACHE TABLE
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS weather_cache (
        location_key TEXT PRIMARY KEY, -- lat_lng rounded to 2 decimals
        weather_data TEXT NOT NULL, -- JSON
        fetched_at TEXT NOT NULL,
        expires_at TEXT NOT NULL
    )
");
echo "✓ Created weather_cache table\n";

// ============================================================
// QUIZ RESULTS TABLE
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS quiz_results (
        id TEXT PRIMARY KEY,
        user_id TEXT, -- nullable for anonymous
        session_id TEXT, -- for anonymous users
        answers TEXT NOT NULL, -- JSON of quiz answers
        matched_beaches TEXT NOT NULL, -- JSON array of beach IDs with scores
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");
echo "✓ Created quiz_results table\n";

// ============================================================
// ADD COMPUTED RATING COLUMNS TO BEACHES
// ============================================================

// Check if columns exist before adding
$result = $db->query("PRAGMA table_info(beaches)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('avg_user_rating', $columns)) {
    $db->exec("ALTER TABLE beaches ADD COLUMN avg_user_rating REAL DEFAULT NULL");
    echo "✓ Added avg_user_rating column to beaches\n";
}

if (!in_array('user_review_count', $columns)) {
    $db->exec("ALTER TABLE beaches ADD COLUMN user_review_count INTEGER DEFAULT 0");
    echo "✓ Added user_review_count column to beaches\n";
}

if (!in_array('swim_difficulty', $columns)) {
    $db->exec("ALTER TABLE beaches ADD COLUMN swim_difficulty INTEGER DEFAULT 3");
    echo "✓ Added swim_difficulty column to beaches\n";
}

if (!in_array('has_lifeguard', $columns)) {
    $db->exec("ALTER TABLE beaches ADD COLUMN has_lifeguard INTEGER DEFAULT 0");
    echo "✓ Added has_lifeguard column to beaches\n";
}

if (!in_array('safe_for_children', $columns)) {
    $db->exec("ALTER TABLE beaches ADD COLUMN safe_for_children INTEGER DEFAULT 1");
    echo "✓ Added safe_for_children column to beaches\n";
}

echo "\n✅ Migration completed successfully!\n";
