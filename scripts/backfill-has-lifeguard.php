<?php
/**
 * Backfill has_lifeguard from beach_amenities.
 *
 * Usage:
 *   php scripts/backfill-has-lifeguard.php
 *   php scripts/backfill-has-lifeguard.php --apply
 *   php scripts/backfill-has-lifeguard.php --limit=25 --apply
 */

require_once __DIR__ . '/../inc/db.php';

$opts = getopt('', ['apply', 'limit::']);
$apply = isset($opts['apply']);
$limit = isset($opts['limit']) ? max(1, intval($opts['limit'])) : 0;

$db = getDb();

$sql = '
    SELECT b.id, b.name, b.municipality, b.slug, b.has_lifeguard
    FROM beaches b
    WHERE b.publish_status = "published"
      AND COALESCE(b.has_lifeguard, 0) = 0
      AND EXISTS (
          SELECT 1
          FROM beach_amenities ba
          WHERE ba.beach_id = b.id
            AND LOWER(ba.amenity) IN ("lifeguard", "lifeguards")
      )
    ORDER BY b.name ASC
';
if ($limit > 0) {
    $sql .= ' LIMIT :limit';
}

$stmt = $db->prepare($sql);
if ($limit > 0) {
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
}
$res = $stmt->execute();

$rows = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $rows[] = $row;
}

echo "Backfill has_lifeguard from amenities\n";
echo "=====================================\n";
echo "Mode: " . ($apply ? 'APPLY' : 'DRY RUN') . "\n";
echo "Rows matched: " . count($rows) . "\n\n";

if (empty($rows)) {
    echo "No rows need backfill.\n";
    exit(0);
}

foreach ($rows as $row) {
    echo "- {$row['name']} ({$row['municipality']}) [{$row['slug']}]\n";
}
echo "\n";

if (!$apply) {
    echo "Dry run only. Re-run with --apply to update has_lifeguard=1 for these rows.\n";
    exit(0);
}

$db->exec('BEGIN');
$update = $db->prepare('UPDATE beaches SET has_lifeguard = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
$updated = 0;

foreach ($rows as $row) {
    $update->bindValue(':id', $row['id'], SQLITE3_TEXT);
    $update->execute();
    if ($db->changes() > 0) {
        $updated++;
    }
}

$db->exec('COMMIT');

echo "Updated rows: {$updated}\n";
