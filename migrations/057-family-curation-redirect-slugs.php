#!/usr/bin/env php
<?php
/**
 * Migration 057: Re-seed best-family-beaches curation resolving merged slugs.
 *
 * Migration 056 seeded the curated list by exact slug, but prod has already
 * merged several duplicate records (escambron-beach -> balneario-el-escambron),
 * so rank 7 was skipped there. Re-seed resolving each slug through beaches
 * first, then beach_slug_redirects, so both pre- and post-merge databases get
 * the full editorial top 10.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDb();

$curatedSlugs = [
    1  => 'balneario-la-monserrate-luquillo',
    2  => 'balneario-de-carolina',
    3  => 'seven-seas-beach',
    4  => 'flamenco-beach',
    5  => 'sun-bay-beach',
    6  => 'balneario-de-boqueron',
    7  => 'escambron-beach',
    8  => 'buye-beach',
    9  => 'playita-del-condado',
    10 => 'puerto-nuevo-beach',
];

$resolve = $db->prepare(
    'SELECT id FROM beaches WHERE slug = :slug
     UNION
     SELECT beach_id FROM beach_slug_redirects WHERE old_slug = :slug
     LIMIT 1'
);

$db->exec("DELETE FROM collection_curated WHERE collection_key = 'best-family-beaches'");

$insert = $db->prepare(
    'INSERT OR IGNORE INTO collection_curated (collection_key, beach_id, rank)
     VALUES (:key, :beach_id, :rank)'
);

$inserted = 0;
foreach ($curatedSlugs as $rank => $slug) {
    $resolve->bindValue(':slug', $slug, SQLITE3_TEXT);
    $row = $resolve->execute()->fetchArray(SQLITE3_ASSOC);
    $resolve->reset();
    if (!$row || empty($row['id'])) {
        echo "WARN: could not resolve '{$slug}' (rank {$rank}); skipped.\n";
        continue;
    }
    $insert->bindValue(':key', 'best-family-beaches', SQLITE3_TEXT);
    $insert->bindValue(':beach_id', $row['id'], SQLITE3_TEXT);
    $insert->bindValue(':rank', $rank, SQLITE3_INTEGER);
    $insert->execute();
    $inserted += $db->changes();
    $insert->reset();
}
echo "Seeded {$inserted}/" . count($curatedSlugs) . " curated beaches for best-family-beaches.\n";
