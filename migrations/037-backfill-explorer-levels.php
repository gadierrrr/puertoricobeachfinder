<?php
/**
 * Migration 037: Backfill explorer levels from existing check-ins
 *
 * updateUserExplorerLevel() (inc/helpers.php) was defined but never called, so
 * users.explorer_level / users.total_beaches_visited were frozen for everyone
 * after migration 004. The app now recomputes these on each check-in
 * (public/api/checkin.php), but users with prior check-ins still hold stale
 * values. This recomputes them once so existing explorers see correct levels
 * and progress immediately. Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Backfill explorer levels from check-ins\n";

$db = getDb();

// Distinct beaches visited per authenticated user (mirrors updateUserExplorerLevel()).
$res = $db->query(
    "SELECT user_id, COUNT(DISTINCT beach_id) AS visited
     FROM beach_checkins
     WHERE user_id IS NOT NULL AND user_id != ''
     GROUP BY user_id"
);

$stmt = $db->prepare(
    'UPDATE users SET explorer_level = :level, total_beaches_visited = :count WHERE id = :id'
);

$updated = 0;
while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
    $visited = (int)($row['visited'] ?? 0);

    // Same thresholds as updateUserExplorerLevel() / getExplorerLevelInfo().
    $level = 'newcomer';
    if ($visited >= 51) {
        $level = 'legend';
    } elseif ($visited >= 26) {
        $level = 'expert';
    } elseif ($visited >= 11) {
        $level = 'guide';
    } elseif ($visited >= 3) {
        $level = 'explorer';
    }

    $stmt->reset();
    $stmt->bindValue(':level', $level, SQLITE3_TEXT);
    $stmt->bindValue(':count', $visited, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $row['user_id'], SQLITE3_TEXT);
    $stmt->execute();
    $updated++;
}

echo "Recomputed explorer levels for {$updated} user(s) with check-ins.\n";
