<?php
/**
 * scripts/seed-local-demo.php
 *
 * LOCAL DEV ONLY. Seeds the local SQLite DB with plausible tags,
 * amenities, and condition values so the redesigned discovery UI
 * renders with realistic content. Idempotent. Not intended to run
 * in production — production data comes from the admin + weather
 * pipeline.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDb();

$tagPool = [
    ['surfing'],
    ['snorkeling'],
    ['family-friendly', 'swimming'],
    ['secluded', 'scenic'],
    ['swimming', 'family-friendly'],
    ['surfing', 'sunset'],
    ['snorkeling', 'scenic'],
    ['party', 'swimming'],
];
$amenityPool = [
    ['parking', 'restrooms', 'shade'],
    ['parking', 'restrooms', 'lifeguard', 'showers'],
    ['parking', 'food-vendors', 'shade'],
    ['parking', 'restrooms', 'shade', 'lifeguard'],
    ['parking'],
    ['parking', 'showers'],
    ['restrooms', 'food-vendors'],
    ['parking', 'food-vendors', 'lifeguard'],
];
$surfPool     = ['calm', 'moderate', 'rough', null];
$windPool     = ['light', 'moderate', 'strong', null];
$sargPool     = ['clear', 'light', 'moderate', 'heavy', null];

$beaches = $db->query('SELECT id FROM beaches ORDER BY name');
$assigned = 0; $conditions = 0;
$insertTag = $db->prepare('INSERT OR IGNORE INTO beach_tags (beach_id, tag) VALUES (:id, :tag)');
$insertAmenity = $db->prepare('INSERT OR IGNORE INTO beach_amenities (beach_id, amenity) VALUES (:id, :amenity)');
$updateBeach = $db->prepare('UPDATE beaches SET sargassum = :s, surf = :su, wind = :w WHERE id = :id');

$i = 0;
while ($row = $beaches->fetchArray(SQLITE3_ASSOC)) {
    $bid = $row['id'];
    $tags = $tagPool[$i % count($tagPool)];
    foreach ($tags as $t) {
        $insertTag->bindValue(':id', $bid, SQLITE3_TEXT);
        $insertTag->bindValue(':tag', $t, SQLITE3_TEXT);
        $insertTag->execute();
        $insertTag->reset();
    }
    $amns = $amenityPool[$i % count($amenityPool)];
    foreach ($amns as $a) {
        $insertAmenity->bindValue(':id', $bid, SQLITE3_TEXT);
        $insertAmenity->bindValue(':amenity', $a, SQLITE3_TEXT);
        $insertAmenity->execute();
        $insertAmenity->reset();
    }
    // Sprinkle conditions on ~60% of beaches
    if ($i % 5 !== 0) {
        $updateBeach->bindValue(':s',  $sargPool[$i % count($sargPool)], $sargPool[$i % count($sargPool)] === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $updateBeach->bindValue(':su', $surfPool[$i % count($surfPool)], $surfPool[$i % count($surfPool)] === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $updateBeach->bindValue(':w',  $windPool[$i % count($windPool)], $windPool[$i % count($windPool)] === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $updateBeach->bindValue(':id', $bid, SQLITE3_TEXT);
        $updateBeach->execute();
        $updateBeach->reset();
        $conditions++;
    }
    $assigned++;
    $i++;
}
echo "Seeded {$assigned} beaches with tags + amenities; {$conditions} with conditions.\n";
