<?php
/**
 * Migration 038: Add user_badges table + backfill from existing activity.
 *
 * Achievement badges (Sprint 3 item 12). Award-only (never revoked). The catalog +
 * award logic live in inc/helpers.php (getAchievementsCatalog/awardAchievements); this migration
 * creates the table and retroactively awards badges to users who already have
 * favorites/reviews/photos/check-ins, so the feature isn't empty on launch.
 * Thresholds here mirror getAchievementsCatalog(). Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Add badges\n";

$db = getDb();

$db->exec("CREATE TABLE IF NOT EXISTS user_badges (
    user_id TEXT NOT NULL,
    badge_key TEXT NOT NULL,
    earned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_key)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_user ON user_badges(user_id)");

// badge_key => [signal, threshold] — must match inc/helpers.php getAchievementsCatalog().
$badges = [
    'first_checkin'    => ['checkins', 1],
    'explorer_5'       => ['unique_beaches', 5],
    'explorer_25'      => ['unique_beaches', 25],
    'island_hopper'    => ['municipalities', 5],
    'first_favorite'   => ['favorites', 1],
    'collector'        => ['favorites', 10],
    'first_review'     => ['reviews', 1],
    'trusted_reviewer' => ['reviews', 5],
    'shutterbug'       => ['photos', 1],
];

$signalSql = [
    'favorites'      => 'SELECT COUNT(*) c FROM user_favorites WHERE user_id = :id',
    'reviews'        => "SELECT COUNT(*) c FROM beach_reviews WHERE user_id = :id AND status = 'published'",
    'photos'         => "SELECT COUNT(*) c FROM beach_photos WHERE user_id = :id AND status = 'published'",
    'checkins'       => 'SELECT COUNT(*) c FROM beach_checkins WHERE user_id = :id',
    'unique_beaches' => 'SELECT COUNT(DISTINCT beach_id) c FROM beach_checkins WHERE user_id = :id',
    'municipalities' => 'SELECT COUNT(DISTINCT b.municipality) c FROM beach_checkins c2 JOIN beaches b ON b.id = c2.beach_id WHERE c2.user_id = :id AND b.municipality IS NOT NULL AND b.municipality != ""',
];

// Collect every user id that has any activity.
$userIds = [];
foreach ([
    'SELECT DISTINCT user_id FROM user_favorites WHERE user_id IS NOT NULL AND user_id != ""',
    "SELECT DISTINCT user_id FROM beach_reviews WHERE user_id IS NOT NULL AND status = 'published'",
    "SELECT DISTINCT user_id FROM beach_photos WHERE user_id IS NOT NULL AND status = 'published'",
    'SELECT DISTINCT user_id FROM beach_checkins WHERE user_id IS NOT NULL AND user_id != ""',
] as $sql) {
    $res = $db->query($sql);
    while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
        $uid = (string)($row['user_id'] ?? '');
        if ($uid !== '') {
            $userIds[$uid] = true;
        }
    }
}

$ins = $db->prepare('INSERT OR IGNORE INTO user_badges (user_id, badge_key, earned_at) VALUES (:id, :k, datetime("now"))');
$awarded = 0;
$usersTouched = 0;

foreach (array_keys($userIds) as $uid) {
    $sig = [];
    foreach ($signalSql as $name => $sql) {
        $st = $db->prepare($sql);
        $st->bindValue(':id', $uid, SQLITE3_TEXT);
        $r = $st->execute()->fetchArray(SQLITE3_ASSOC);
        $sig[$name] = (int)($r['c'] ?? 0);
    }
    $touched = false;
    foreach ($badges as $key => $spec) {
        [$signal, $threshold] = $spec;
        if (($sig[$signal] ?? 0) >= $threshold) {
            $ins->reset();
            $ins->bindValue(':id', $uid, SQLITE3_TEXT);
            $ins->bindValue(':k', $key, SQLITE3_TEXT);
            $ins->execute();
            if ($db->changes() > 0) {
                $awarded++;
                $touched = true;
            }
        }
    }
    if ($touched) {
        $usersTouched++;
    }
}

echo "Created user_badges; backfilled {$awarded} badge(s) across {$usersTouched} user(s).\n";
