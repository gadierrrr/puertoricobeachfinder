<?php
/**
 * Migration 044: Backfill referral achievement badges.
 *
 * Migration 038 created the badge table and backfilled activity badges. Referral
 * badges were added to the runtime catalog later, so this fills them for users
 * who already had completed referrals. Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: backfill referral badges\n";

$db = getDb();

$db->exec("CREATE TABLE IF NOT EXISTS user_badges (
    user_id TEXT NOT NULL,
    badge_key TEXT NOT NULL,
    earned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_key)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_user ON user_badges(user_id)");

$badges = [
    'first_referral' => 1,
    'ambassador' => 5,
];

$users = $db->query("
    SELECT referrer_user_id AS user_id, COUNT(*) AS referral_count
    FROM user_referrals
    WHERE referrer_user_id IS NOT NULL
      AND referrer_user_id != ''
      AND status = 'completed'
    GROUP BY referrer_user_id
");

$insert = $db->prepare('INSERT OR IGNORE INTO user_badges (user_id, badge_key, earned_at) VALUES (:id, :badge, datetime("now"))');
$awarded = 0;
$usersTouched = 0;

while ($users && ($row = $users->fetchArray(SQLITE3_ASSOC))) {
    $uid = (string)($row['user_id'] ?? '');
    $count = (int)($row['referral_count'] ?? 0);
    $touched = false;

    foreach ($badges as $badgeKey => $threshold) {
        if ($count < $threshold) {
            continue;
        }

        $insert->reset();
        $insert->bindValue(':id', $uid, SQLITE3_TEXT);
        $insert->bindValue(':badge', $badgeKey, SQLITE3_TEXT);
        $insert->execute();

        if ($db->changes() > 0) {
            $awarded++;
            $touched = true;
        }
    }

    if ($touched) {
        $usersTouched++;
    }
}

echo "Backfilled {$awarded} referral badge(s) across {$usersTouched} user(s).\n";
