<?php
/**
 * Lifeguard Coverage Audit
 *
 * Usage:
 *   php scripts/audit-lifeguards.php
 *   php scripts/audit-lifeguards.php --limit=25
 *   php scripts/audit-lifeguards.php --csv=data/lifeguard-audit.csv
 */

require_once __DIR__ . '/../inc/db.php';

$opts = getopt('', ['limit::', 'csv::']);
$limit = isset($opts['limit']) ? max(1, intval($opts['limit'])) : 25;
$csvPath = isset($opts['csv']) ? trim((string)$opts['csv']) : '';

$db = getDb();

function scalarCount(SQLite3 $db, string $sql): int {
    $row = $db->querySingle($sql, true);
    if (!is_array($row)) {
        return 0;
    }
    return intval($row['cnt'] ?? 0);
}

$publishedTotal = scalarCount($db, 'SELECT COUNT(*) AS cnt FROM beaches WHERE publish_status = "published"');
$flagYes = scalarCount($db, 'SELECT COUNT(*) AS cnt FROM beaches WHERE publish_status = "published" AND COALESCE(has_lifeguard, 0) = 1');
$flagNo = scalarCount($db, 'SELECT COUNT(*) AS cnt FROM beaches WHERE publish_status = "published" AND COALESCE(has_lifeguard, 0) = 0');
$flagNull = scalarCount($db, 'SELECT COUNT(*) AS cnt FROM beaches WHERE publish_status = "published" AND has_lifeguard IS NULL');

$amenityYes = scalarCount($db, '
    SELECT COUNT(DISTINCT b.id) AS cnt
    FROM beaches b
    INNER JOIN beach_amenities ba ON ba.beach_id = b.id
    WHERE b.publish_status = "published"
      AND LOWER(ba.amenity) IN ("lifeguard", "lifeguards")
');

$mismatchFlagYesNoAmenitySql = '
    SELECT b.id, b.name, b.municipality, b.slug, b.has_lifeguard
    FROM beaches b
    WHERE b.publish_status = "published"
      AND COALESCE(b.has_lifeguard, 0) = 1
      AND NOT EXISTS (
          SELECT 1
          FROM beach_amenities ba
          WHERE ba.beach_id = b.id
            AND LOWER(ba.amenity) IN ("lifeguard", "lifeguards")
      )
    ORDER BY b.name ASC
    LIMIT :limit
';
$stmt = $db->prepare($mismatchFlagYesNoAmenitySql);
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$mismatchFlagYesNoAmenityRes = $stmt->execute();
$mismatchFlagYesNoAmenity = [];
while ($row = $mismatchFlagYesNoAmenityRes->fetchArray(SQLITE3_ASSOC)) {
    $mismatchFlagYesNoAmenity[] = $row;
}

$mismatchFlagNoAmenityYesSql = '
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
    LIMIT :limit
';
$stmt = $db->prepare($mismatchFlagNoAmenityYesSql);
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$mismatchFlagNoAmenityYesRes = $stmt->execute();
$mismatchFlagNoAmenityYes = [];
while ($row = $mismatchFlagNoAmenityYesRes->fetchArray(SQLITE3_ASSOC)) {
    $mismatchFlagNoAmenityYes[] = $row;
}

$mismatchFlagYesNoAmenityCount = scalarCount($db, '
    SELECT COUNT(*) AS cnt
    FROM beaches b
    WHERE b.publish_status = "published"
      AND COALESCE(b.has_lifeguard, 0) = 1
      AND NOT EXISTS (
          SELECT 1
          FROM beach_amenities ba
          WHERE ba.beach_id = b.id
            AND LOWER(ba.amenity) IN ("lifeguard", "lifeguards")
      )
');

$mismatchFlagNoAmenityYesCount = scalarCount($db, '
    SELECT COUNT(*) AS cnt
    FROM beaches b
    WHERE b.publish_status = "published"
      AND COALESCE(b.has_lifeguard, 0) = 0
      AND EXISTS (
          SELECT 1
          FROM beach_amenities ba
          WHERE ba.beach_id = b.id
            AND LOWER(ba.amenity) IN ("lifeguard", "lifeguards")
      )
');

echo "Lifeguard Coverage Audit\n";
echo "=======================\n";
echo "Published beaches: {$publishedTotal}\n";
echo "has_lifeguard = 1: {$flagYes}\n";
echo "has_lifeguard = 0: {$flagNo}\n";
echo "has_lifeguard IS NULL: {$flagNull}\n";
echo "Amenities include lifeguard/lifeguards: {$amenityYes}\n";
echo "\n";
echo "Mismatch A (flag=1, no lifeguard amenity): {$mismatchFlagYesNoAmenityCount}\n";
echo "Mismatch B (flag=0, but lifeguard amenity exists): {$mismatchFlagNoAmenityYesCount}\n";
echo "\n";

echo "Sample mismatch A (up to {$limit}):\n";
if (empty($mismatchFlagYesNoAmenity)) {
    echo "  (none)\n";
} else {
    foreach ($mismatchFlagYesNoAmenity as $row) {
        echo "  - {$row['name']} ({$row['municipality']}) [{$row['slug']}]\n";
    }
}
echo "\n";

echo "Sample mismatch B (up to {$limit}):\n";
if (empty($mismatchFlagNoAmenityYes)) {
    echo "  (none)\n";
} else {
    foreach ($mismatchFlagNoAmenityYes as $row) {
        echo "  - {$row['name']} ({$row['municipality']}) [{$row['slug']}]\n";
    }
}
echo "\n";

if ($csvPath !== '') {
    $fullPath = $csvPath;
    if ($csvPath[0] !== '/') {
        $fullPath = APP_ROOT . '/' . $csvPath;
    }
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $fp = fopen($fullPath, 'w');
    if ($fp === false) {
        fwrite(STDERR, "Failed to open CSV path: {$fullPath}\n");
        exit(1);
    }

    fputcsv($fp, ['type', 'id', 'name', 'municipality', 'slug', 'has_lifeguard']);
    foreach ($mismatchFlagYesNoAmenity as $row) {
        fputcsv($fp, ['flag_yes_no_amenity', $row['id'], $row['name'], $row['municipality'], $row['slug'], $row['has_lifeguard']]);
    }
    foreach ($mismatchFlagNoAmenityYes as $row) {
        fputcsv($fp, ['flag_no_amenity_yes', $row['id'], $row['name'], $row['municipality'], $row['slug'], $row['has_lifeguard']]);
    }
    fclose($fp);
    echo "Wrote mismatch sample CSV: {$fullPath}\n";
}
